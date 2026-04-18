<?php

namespace ReelsWP\domain\repositories;

defined('ABSPATH') || exit;

abstract class BaseRepo
{
	protected $wpdb;
	protected $p;

	public function __construct()
	{
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->p    = $wpdb->prefix;
	}

	protected function esc_bool($v)
	{
		return $v ? 1 : 0;
	}
}
