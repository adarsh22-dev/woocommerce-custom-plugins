<?php

namespace ReelsWP\api\controllers;

use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;
use ReelsWP\domain\repositories\FilesRepo;
use ReelsWP\domain\repositories\StoriesRepo;

defined('ABSPATH') || exit;

class FilesController extends WP_REST_Controller
{
  /** @var FilesRepo */
  private $repo;
  private $stories_repo;

  public function __construct()
  {
    $this->namespace = 'wp-reels/v1';
    $this->rest_base = 'files';
    $this->repo      = new FilesRepo();
    $this->stories_repo = new StoriesRepo();
  }

  public function register_routes()
  {

    // CREATE or UPDATE file
    register_rest_route($this->namespace, '/' . $this->rest_base, [
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => [$this, 'create_or_update_item'],
      'permission_callback' => [$this, 'permission_admin'],
      'args'                => [
        'story_id'    => ['required' => false, 'type' => 'integer'],
        'story_title' => ['required' => false, 'type' => 'string'],
        'files'       => [
          'required' => true,
          'type' => 'array',
        ]
      ],
    ]);

    // DELETE file
    register_rest_route($this->namespace, '/file' . '/(?P<id>\d+)', [
      'methods'             => WP_REST_Server::DELETABLE,
      'callback'            => [$this, 'delete_item'],
      'permission_callback' => [$this, 'permission_admin'],
      'args'                => [
        // 'id' => [ 'validate_callback' => 'is_numeric' ],
      ],
    ]);
  }

  public function permission_admin($request = null): bool
  {
    return current_user_can('manage_options');
  }

  public function create_or_update_item($req)
  {
    global $wpdb;
    $wpdb->query('START TRANSACTION');

    try {
      $params = $req->get_params();
      $story_id = (int) ($params['story_id'] ?? 0);
      $story_title = $params['story_title'] ?? '';

      if (!$story_id) {
        if (empty($story_title)) {
          $story_title = $this->stories_repo->get_next_undefined_name();
        }
        $story_data = [
          'title' => $story_title,
        ];
        $new_story = $this->stories_repo->create($story_data);
        $story_id = $new_story['id'];
        $status_code = 201;
      } else {
        if (!empty($story_title)) {
          $this->stories_repo->update($story_id, ['title' => $story_title]);
        }
        $this->repo->delete_files_by_story($story_id);
        $status_code = 200;
      }

      $files = $params['files'] ?? [];

      foreach ($files as $file_data) {
        $this->repo->create($story_id, $file_data);
      }

      $wpdb->query('COMMIT');

      return rest_ensure_response(new WP_REST_Response([
        'story_id' => $story_id,
      ], $status_code));
    } catch (\Throwable $e) {
      $wpdb->query('ROLLBACK');
      return rest_ensure_response(new WP_REST_Response([
        'error'   => 'create_or_update_failed',
        'message' => $e->getMessage(),
      ], 500));
    }
  }

  public function delete_item($req)
  {
    try {
      $id  = (int) $req['id'];
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
