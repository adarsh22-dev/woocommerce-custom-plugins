<?php

namespace ReelsWP\api\controllers;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Controller;
use WP_Error;

use ReelsWP\domain\repositories\StoriesRepo;
use ReelsWP\domain\repositories\SettingsRepo;
use ReelsWP\domain\repositories\TrackingRepo;
use ReelsWP\domain\repositories\GroupsRepo;
use ReelsWP\domain\services\RateLimiter;
// (Optional) use ReelsWP\domain\services\Analytics;

defined('ABSPATH') || exit;

class TrackingController extends WP_REST_Controller
{

  /** @var TrackingRepo */
  private $repo;
  private $stories_repo;
  private $groups_repo;

  public function __construct()
  {
    $this->namespace = 'wp-reels/v1';
    $this->repo = new TrackingRepo();
    $this->stories_repo = new StoriesRepo();
    $this->groups_repo = new GroupsRepo();
  }

  public function register_routes()
  {

    // GET stories for a group
    register_rest_route($this->namespace, '/stories/(?P<group_id>\d+)', [
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => [$this, 'get_stories_by_group'],
      'permission_callback' => '__return_true',
    ]);

    // Public: story view
    register_rest_route($this->namespace, '/group/(?P<group_id>\d+)/story/(?P<id>\d+)/view', [
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => [$this, 'increment_view'],
      'permission_callback' => '__return_true',
      'args'                => [
        // 'id' => [ 'validate_callback' => 'is_numeric' ],
        // 'group_id' => [ 'validate_callback' => 'is_numeric' ],
      ],
    ]);

    // Button click (payload-based)
    register_rest_route($this->namespace, '/button/click', [
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => [$this, 'increment_click'],
      'permission_callback' => '__return_true',
      'args'                => [
        'group_id'      => ['required' => true,  'type' => 'integer'],
        'story_id'      => ['required' => true,  'type' => 'integer'],
        'story_title'   => ['required' => true,  'type' => 'string'],
        'btn_uuid'      => ['required' => true,  'type' => 'string'],
        'button_text'   => ['required' => false, 'type' => 'string'],
        'button_url'    => ['required' => false, 'type' => 'string'],
        'campaign_name' => ['required' => false, 'type' => 'string'],
      ],
    ]);

    // Get group stats
    register_rest_route($this->namespace, '/group/(?P<group_id>\d+)/stats', [
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => [$this, 'get_group_stats'],
      'permission_callback' => function () {
        return current_user_can('manage_options');
      },
      'args'                => [
        // 'group_id' => [
        //   'validate_callback' => 'is_numeric',
        //   'required' => true
        // ],
      ],
    ]);
  }

  public function get_stories_by_group(WP_REST_Request $req)
  {
    try {
      $group_id = (int) $req['group_id'];

      $group = $this->groups_repo->get_by_id($group_id);
      if (!$group) {
        return new WP_Error('group_not_found', 'Invalid group ID', ['status' => 404]);
      }

      $stories = $this->stories_repo->get_stories_by_group($group_id);

      $group['stories'] = $stories;

      return rest_ensure_response(new WP_REST_Response($group, 200));
    } catch (\Throwable $e) {
      return rest_ensure_response(new WP_REST_Response([
        'error'   => 'get_failed',
        'message' => $e->getMessage(),
      ], 500));
    }
  }

  public function increment_view(WP_REST_Request $req)
  {
    try {
      $id = (int) $req['id'];
      $group_id = (int) $req['group_id'];

      $settings = SettingsRepo::get_settings();
      $rate_limit = !empty($settings['rate_limit']) ? (int)$settings['rate_limit'] : 2;
      $time_limit = !empty($settings['time_limit']) ? (int)$settings['time_limit'] * 60 : 60;

      // Basic rate limit: from settings
      $rlKey = 'view:' . $id . ':' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
      if (! RateLimiter::check($rlKey, $rate_limit, $time_limit)) {
        return rest_ensure_response(new WP_REST_Response(['error' => 'rate_limited'], 429));
      }

      StoriesRepo::increment_view($id);

      // (Optional) Analytics::log_event('view', ['story_id'=>$id]);
      return rest_ensure_response(new WP_REST_Response(['ok' => true], 200));
    } catch (\Throwable $e) {
      return rest_ensure_response(new WP_REST_Response([
        'error'   => 'increment_failed',
        'message' => $e->getMessage(),
      ], 500));
    }
  }

  public function increment_click(WP_REST_Request $req)
  {
    try {
      $params = $req->get_params();
      $this->repo->increment_click($params);

      return rest_ensure_response(new WP_REST_Response(['ok' => true], 200));
    } catch (\Throwable $e) {
      return rest_ensure_response(new WP_REST_Response([
        'error'   => 'increment_failed',
        'message' => $e->getMessage(),
      ], 500));
    }
  }

  public function get_group_stats(WP_REST_Request $req)
  {
    try {
      $group_id = (int) $req['group_id'];
      $stats = $this->repo->get_group_stats($group_id);
      return rest_ensure_response(new WP_REST_Response($stats, 200));
    } catch (\Throwable $e) {
      return rest_ensure_response(new WP_REST_Response([
        'error'   => 'stats_failed',
        'message' => $e->getMessage(),
      ], 500));
    }
  }
}
