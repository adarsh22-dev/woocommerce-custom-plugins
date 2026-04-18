<?php

namespace ReelsWP\api\controllers;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Controller;
use ReelsWP\domain\repositories\GroupsRepo;

defined('ABSPATH') || exit;

class GroupsController extends WP_REST_Controller
{
  /** @var GroupsRepo */
  private $repo;

  public function __construct()
  {
    $this->namespace = 'wp-reels/v1';
    $this->rest_base = 'group';
    $this->repo = new GroupsRepo();
  }

  public function register_routes()
  {

    // (Optional) LIST groups for admin UI
    register_rest_route($this->namespace, '/groups', [
      'methods' => WP_REST_Server::READABLE,
      'callback' => [$this, 'list_items'],
      'permission_callback' => [$this, 'permission_admin'],
    ]);

    // CREATE/UPDATE group
    register_rest_route($this->namespace, '/' . $this->rest_base, [
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [$this, 'upsert_item'],
      'permission_callback' => [$this, 'permission_admin'],
      'args' => [
        'id' => ['required' => false, 'type' => 'integer'],
        'group_name' => ['required' => false, 'type' => 'string'],
        'styles' => ['required' => false, 'type' => 'json'],
      ],
    ]);

    // DELETE group
    register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
      'methods' => WP_REST_Server::DELETABLE,
      'callback' => [$this, 'delete_item'],
      'permission_callback' => [$this, 'permission_admin'],
    ]);
  }

  public function permission_admin($request = null): bool
  {
    return current_user_can('manage_options');
  }

  // (Optional) GET /groups
  public function list_items($req)
  {
    try {
      $args = [
        'per_page' => (int) ($req['per_page'] ?? 50),
        'page' => (int) ($req['page'] ?? 1),
        'orderby' => $req['orderby'] ?? 'id', // allow: id, group_name
        'order' => $req['order'] ?? 'ASC',   // ASC|DESC
        'search' => $req['q'] ?? '',
      ];

      $result = $this->repo->get_list($args);

      // ✅ Return only id and group_name
      $filtered_items = array_map(function ($item) {
        return [
          'id' => $item['id'] ?? null,
          'group_name' => $item['group_name'] ?? null,
        ];
      }, $result['items']);

      $response = new WP_REST_Response($filtered_items, 200);
      $response->header('X-WP-Total', (string) $result['total']);
      $response->header('X-WP-TotalPages', (string) $result['total_pages']);
      return $response;
    } catch (\Throwable $e) {
      return rest_ensure_response(new WP_REST_Response([
        'error' => 'list_failed',
        'message' => $e->getMessage(),
      ], 500));
    }
  }


  public function upsert_item($req)
  {
    try {
      $id = $req->get_param('id');
      $params = $req->get_params();

      if ($id) {
        $data = $this->repo->update((int)$id, $params);
        return rest_ensure_response(new WP_REST_Response($data, 200));
      } else {
        $data = $this->repo->create($params);
        return rest_ensure_response(new WP_REST_Response($data, 201));
      }
    } catch (\Throwable $e) {
      return rest_ensure_response(new WP_REST_Response([
        'error' => 'upsert_failed',
        'message' => $e->getMessage(),
      ], 500));
    }
  }

  public function delete_item($req)
  {
    try {
      $id = (int) $req['id'];
      $deleted_id = $this->repo->delete($id);
      return rest_ensure_response(new WP_REST_Response(['id' => $deleted_id], 200));
    } catch (\Throwable $e) {
      return rest_ensure_response(new WP_REST_Response([
        'error' => 'delete_failed',
        'message' => $e->getMessage(),
      ], 500));
    }
  }
}
