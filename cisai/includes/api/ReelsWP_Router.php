<?php

use ReelsWP\api\controllers\FilesController;
use ReelsWP\api\controllers\GroupsController;
use ReelsWP\api\controllers\StoriesController;
use ReelsWP\api\controllers\TrackingController;
use ReelsWP\api\controllers\SettingsController;

// includes/api/class-router.php
defined('ABSPATH') || exit;

class ReelsWP_Router
{
  public static function init()
  {
    add_action('rest_api_init', function () {
      (new GroupsController())->register_routes();
      (new StoriesController())->register_routes();
      (new FilesController())->register_routes();
      (new TrackingController())->register_routes();
      (new SettingsController())->register_routes();
    });
  }
}
