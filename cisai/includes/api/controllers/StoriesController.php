<?php

namespace ReelsWP\api\controllers;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Controller;
use WP_Error;
use ReelsWP\domain\repositories\StoriesRepo;
use ReelsWP\domain\repositories\FilesRepo;
use ReelsWP\domain\repositories\GroupsRepo;

defined('ABSPATH') || exit;

class StoriesController extends WP_REST_Controller
{
  /** @var StoriesRepo */
  private $repo;
  private $groups_repo;

  public function __construct()
  {
    $this->namespace = 'wp-reels/v1';
    $this->rest_base = 'story';
    $this->repo      = new StoriesRepo();
    $this->groups_repo = new GroupsRepo();
  }

  public function register_routes()
  {

    // GET single story
    register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => [$this, 'get_item'],
      'permission_callback' => [$this, 'permission_admin'],
    ]);

    // DELETE story
    register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
      'methods'             => WP_REST_Server::DELETABLE,
      'callback'            => [$this, 'delete_item'],
      'permission_callback' => [$this, 'permission_admin'],
    ]);

    // SET stories for a group (or create new group)
    register_rest_route($this->namespace, '/stories/manage-group-stories', [
      'methods'             => WP_REST_Server::EDITABLE,
      'callback'            => [$this, 'set_stories_for_group'],
      'permission_callback' => [$this, 'permission_admin'],
      'args'                => [
        'group_id' => [
          'required' => false,
          'type' => 'integer',
        ],
        'group_name' => [
          'required' => false,
          'type' => 'string',
        ],
        'styles' => [
          'required' => false,
          'type' => 'json'
        ],
        'story_ids' => [
          'required' => true,
          'type' => 'array',
          'items' => [
            'type' => 'integer',
          ],
        ],
      ],
    ]);

    // GET all stories
    register_rest_route($this->namespace, '/stories', [
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => [$this, 'get_items'],
      'permission_callback' => [$this, 'permission_admin'],
      'args'                => [
        'group_id' => [
          'required' => false,
          'type' => 'integer',
        ],
        'search' => [
          'required' => false,
          'type' => 'string',
        ]
      ],
    ]);
  }

  public function get_items($req)
  {
    try {
      $group_id = !empty($req['group_id']) ? (int) $req['group_id'] : null;
      $search = !empty($req['search']) ? sanitize_text_field($req['search']) : null;

      $args = [
        'per_page' => (int) ($req['per_page'] ?? 50),
        'page' => (int) ($req['page'] ?? 1),
        'orderby' => $req['orderby'] ?? 'id', // allow: id, group_name
        'order' => $req['order'] ?? 'DESC',   // ASC|DESC
      ];

      if ($group_id) {
        $result = $this->repo->get_all_not_in_group($group_id, $search, $args);
      } else {
        $result = $this->repo->get_all($search, $args);
      }

      $response = new WP_REST_Response($result['items'], 200);
      $response->header('X-WP-Total', (string) $result['total']);
      $response->header('X-WP-TotalPages', (string) $result['total_pages']);
      return $response;
    } catch (\Throwable $e) {
      return rest_ensure_response(new WP_REST_Response([
        'error'   => 'get_failed',
        'message' => $e->getMessage(),
      ], 500));
    }
  }

  public function get_item($req)
  {
    try {
      $id = (int) $req['id'];
      $story = $this->repo->get_by_id($id);
      if (!$story) {
        return new WP_Error('story_not_found', 'Invalid story ID', ['status' => 404]);
      }
      return rest_ensure_response(new WP_REST_Response($story, 200));
    } catch (\Throwable $e) {
      return rest_ensure_response(new WP_REST_Response([
        'error'   => 'get_failed',
        'message' => $e->getMessage(),
      ], 500));
    }
  }

  public function set_stories_for_group($req)
  {
    try {
      $group_id = (int) ($req['group_id'] ?? null);
      $story_ids = $req['story_ids'];
      $group_name = $req['group_name'] ?? '';
      $styles = $req['styles'] ?? '';

      if (!$group_id) {
        $group_data = [
          'group_name' => $group_name,
          'styles' => $styles,
        ];
        $new_group = $this->groups_repo->create($group_data);
        $group_id = $new_group['id'];
      } else {
        $group_data = [
          'group_name' => $group_name,
          'styles' => $styles,
        ];
        $this->groups_repo->update($group_id, $group_data);
      }

      // If group_id is still 0, something went wrong or no stories to add
      if (!$group_id) {
        return new WP_Error('group_creation_failed', 'Could not create or find a group.', ['status' => 500]);
      }

      $this->repo->remove_all_stories_from_group($group_id);

      if (!empty($story_ids)) {
        foreach ($story_ids as $story_id) {
          $this->repo->add_to_group((int) $story_id, $group_id);
        }
      }

      return rest_ensure_response(new WP_REST_Response(['status' => 'ok', 'group_id' => $group_id], 200));
    } catch (\Throwable $e) {
      return rest_ensure_response(new WP_REST_Response([
        'error'   => 'set_stories_failed',
        'message' => $e->getMessage(),
      ], 500));
    }
  }

  public function permission_admin($request = null): bool
  {
    return current_user_can('manage_options');
  }

  public function delete_item($req)
  {
    try {
      $id  = (int) $req['id'];
      $files_repo = new FilesRepo();
      $files_repo->delete_files_by_story($id);
      $deleted_id = $this->repo->delete($id);

      return rest_ensure_response(new WP_REST_Response(['id' => $deleted_id], 200));
    } catch (\Throwable $e) {
      return rest_ensure_response(new WP_REST_Response([
        'error'   => 'delete_failed',
        'message' => $e->getMessage(),
      ], 500));
    }
  }
}
