<?php
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

namespace ReelsWP\domain\repositories;

// includes/domain/repositories/class-stories-repo.php
defined('ABSPATH') || exit;

use ReelsWP\domain\repositories\FilesRepo;

class StoriesRepo extends BaseRepo
{
	private $files_repo;

	public function __construct()
	{
		parent::__construct();
		$this->files_repo = new FilesRepo();
	}

	public function create(array $in): array
	{
		$uuid  = !empty($in['story_uuid']) ? sanitize_text_field($in['story_uuid']) : wp_generate_uuid4();
		$title = isset($in['title']) ? sanitize_text_field($in['title']) : null;

		$this->wpdb->insert("{$this->p}reels_stories", [
			'story_uuid'  => $uuid,
			'title'       => $title,
			'view_count'  => 0,
		], ['%s', '%s', '%d']);

		$story_id = (int)$this->wpdb->insert_id;

		if (!empty($in['group_id'])) {
			$this->add_to_group($story_id, (int)$in['group_id']);
		}

		return [
			'id'        => $story_id,
			'story_uuid' => $uuid,
		];
	}


	public function update(int $id, array $in): ?array
	{
		$fields = [];
		$fmts = [];
		$map = ['title' => '%s'];
		foreach ($map as $k => $fmt) {
			if (array_key_exists($k, $in)) {
				$fields[$k] = $in[$k];
				$fmts[] = $fmt;
			}
		}
		if ($fields) {
			$this->wpdb->update("{$this->p}reels_stories", $fields, ['id' => $id], $fmts, ['%d']);
		}
		return $this->get_by_id($id);
	}

	public function delete(int $id): int
	{
		$this->wpdb->delete("{$this->p}reels_groups_stories", ['story_id' => $id], ['%d']);
		$this->wpdb->delete("{$this->p}reels_stories", ['id' => $id], ['%d']);
		return $id;
	}

	public function get_by_id(int $id): ?array
	{
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->p}reels_stories WHERE id = %d",
			$id
		);
		$story = $this->wpdb->get_row($sql, ARRAY_A);
		if ($story) {
			$story['files'] = $this->files_repo->get_files_by_story($id);
		}
		return $story;
	}

	public function add_to_group(int $story_id, int $group_id): void
	{
		$this->wpdb->replace("{$this->p}reels_groups_stories", [
			'story_id' => $story_id,
			'group_id' => $group_id,
		], ['%d', '%d']);
	}

	public function remove_from_group(int $story_id, int $group_id): void
	{
		$this->wpdb->delete("{$this->p}reels_groups_stories", [
			'story_id' => $story_id,
			'group_id' => $group_id,
		], ['%d', '%d']);
	}

	public function remove_all_stories_from_group(int $group_id): void
	{
		$this->wpdb->delete("{$this->p}reels_groups_stories", ['group_id' => $group_id], ['%d']);
	}

	public function get_stories_by_group(int $group_id): array
	{
		$sql = $this->wpdb->prepare(
			"SELECT s.* FROM {$this->p}reels_stories s JOIN {$this->p}reels_groups_stories gs ON s.id = gs.story_id WHERE gs.group_id = %d ORDER BY gs.id ASC",
			$group_id
		);
		$stories = $this->wpdb->get_results($sql, ARRAY_A);

		foreach ($stories as &$story) {
			$story['files'] = $this->files_repo->get_files_by_story($story['id']);
		}

		return $stories;
	}

	public function get_all(?string $search_term = null, array $args = []): array
	{
		$per_page = max(1, (int) ($args['per_page'] ?? 50));
		$page = max(1, (int) ($args['page'] ?? 1));
		$offset = ($page - 1) * $per_page;

		$where_clauses = [];
		$params = [];

		if ($search_term) {
			$where_clauses[] = "title LIKE %s";
			$params[] = '%' . $this->wpdb->esc_like($search_term) . '%';
		}

		$where_sql = '';
		if (!empty($where_clauses)) {
			$where_sql = " WHERE " . implode(' AND ', $where_clauses);
		}

		$total_sql = "SELECT COUNT(*) FROM {$this->p}reels_stories" . $where_sql;
		$total_query = $this->wpdb->prepare($total_sql, ...$params);
		$total = (int) $this->wpdb->get_var($total_query);

		$sql = "SELECT * FROM {$this->p}reels_stories" . $where_sql . " ORDER BY id DESC LIMIT %d OFFSET %d";
		$params[] = $per_page;
		$params[] = $offset;

		$query = $this->wpdb->prepare($sql, ...$params);
		$stories = $this->wpdb->get_results($query, ARRAY_A);

		foreach ($stories as &$story) {
			$story['files'] = $this->files_repo->get_files_by_story($story['id']);
		}

		$total_pages = (int) ceil($total / $per_page);

		return [
			'items' => $stories,
			'total' => $total,
			'total_pages' => $total_pages,
		];
	}

	public function get_all_not_in_group(int $group_id, ?string $search_term = null, array $args = []): array
	{
		$per_page = max(1, (int) ($args['per_page'] ?? 50));
		$page = max(1, (int) ($args['page'] ?? 1));
		$offset = ($page - 1) * $per_page;

		$where_clauses = ["id NOT IN (SELECT story_id FROM {$this->p}reels_groups_stories WHERE group_id = %d)"];
		$params = [$group_id];

		if ($search_term) {
			$where_clauses[] = "title LIKE %s";
			$params[] = '%' . $this->wpdb->esc_like($search_term) . '%';
		}

		$where_sql = " WHERE " . implode(' AND ', $where_clauses);

		$total_sql = "SELECT COUNT(*) FROM {$this->p}reels_stories" . $where_sql;
		$total_query = $this->wpdb->prepare($total_sql, ...$params);
		$total = (int) $this->wpdb->get_var($total_query);

		$sql = "SELECT * FROM {$this->p}reels_stories" . $where_sql . " ORDER BY id DESC LIMIT %d OFFSET %d";
		$params[] = $per_page;
		$params[] = $offset;

		$query = $this->wpdb->prepare($sql, ...$params);
		$stories = $this->wpdb->get_results($query, ARRAY_A);

		foreach ($stories as &$story) {
			$story['files'] = $this->files_repo->get_files_by_story($story['id']);
		}

		$total_pages = (int) ceil($total / $per_page);

		return [
			'items' => $stories,
			'total' => $total,
			'total_pages' => $total_pages,
		];
	}

	public static function increment_view(int $id): void
	{
		global $wpdb;
		$p = $wpdb->prefix;
		$wpdb->query($wpdb->prepare(
			"UPDATE {$p}reels_stories SET view_count = view_count + 1 WHERE id=%d",
			$id
		));
	}

	public function get_next_undefined_name(): string
	{
		$base_name = 'untitled reel';
		$like = $base_name . '%';
		$last_name = $this->wpdb->get_var($this->wpdb->prepare(
			"SELECT title FROM {$this->p}reels_stories WHERE title LIKE %s ORDER BY id DESC LIMIT 1",
			$like
		));

		if (!$last_name) {
			$check_undefined = $this->wpdb->get_var($this->wpdb->prepare("SELECT title FROM {$this->p}reels_stories WHERE title = %s", $base_name));
			if (!$check_undefined) {
				return $base_name;
			} else {
				return $base_name . ' 2';
			}
		}

		if ($last_name === $base_name) {
			return $base_name . ' 2';
		}

		$parts = explode(' ', $last_name);
		$number = (int) end($parts);
		return $base_name . ' ' . ($number + 1);
	}
}
