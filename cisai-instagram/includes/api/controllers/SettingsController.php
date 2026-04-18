<?php

namespace ReelsWP\api\controllers;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use ReelsWP\domain\repositories\SettingsRepo;

defined('ABSPATH') || exit;

class SettingsController extends WP_REST_Controller
{
    /** @var SettingsRepo */
    private $repo;

    public function __construct()
    {
        $this->namespace = 'wp-reels/v1';
        $this->rest_base = 'settings';
        $this->repo      = new SettingsRepo();
    }

    public function register_routes()
    {
        // GET settings
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_item'],
            'permission_callback' => [$this, 'permission_admin'],
        ]);

        // UPDATE settings (or create if not exists)
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => [$this, 'update_item'],
            'permission_callback' => [$this, 'permission_admin'],
            'args'                => [
                'rate_limit' => ['required' => true, 'type' => 'integer'],
                'time_limit' => ['required' => true, 'type' => 'integer'],
            ],
        ]);
    }

    public function permission_admin($request = null): bool
    {
        return current_user_can('manage_options');
    }

    public function get_item($req)
    {
        try {
            $settings = $this->repo->get();
            if (!$settings) {
                return rest_ensure_response(new WP_REST_Response([
                    'rate_limit' => null,
                    'time_limit' => null,
                ], 200));
            }
            return rest_ensure_response(new WP_REST_Response($settings, 200));
        } catch (\Throwable $e) {
            return rest_ensure_response(new WP_REST_Response([
                'error'   => 'get_failed',
                'message' => $e->getMessage(),
            ], 500));
        }
    }

    public function update_item($req)
    {
        try {
            $params = [];
            if ($req->offsetExists('rate_limit')) {
                $params['rate_limit'] = (int) $req['rate_limit'];
            }
            if ($req->offsetExists('time_limit')) {
                $params['time_limit'] = (int) $req['time_limit'];
            }

            $id = $this->repo->save($params);

            return rest_ensure_response(new WP_REST_Response([
                'ok' => true,
                'id' => $id,
            ], 200));
        } catch (\Throwable $e) {
            return rest_ensure_response(new WP_REST_Response([
                'error'   => 'update_failed',
                'message' => $e->getMessage(),
            ], 500));
        }
    }
}
