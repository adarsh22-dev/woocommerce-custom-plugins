<?php
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

namespace ReelsWP\domain\repositories;
// includes/domain/repositories/class-groups-repo.php
defined('ABSPATH') || exit;

class GroupsRepo extends BaseRepo
{

	public function get_list(array $args = []): array
	{
		$per_page = max(1, (int) ($args['per_page'] ?? 50));
		$page = max(1, (int) ($args['page'] ?? 1));
		$offset = ($page - 1) * $per_page;

		$allowed_orderby = ['id', 'group_name', 'created_at'];
		$orderby = in_array($args['orderby'] ?? 'id', $allowed_orderby, true)
			? $args['orderby'] : 'id';

		$order = (strtoupper($args['order'] ?? 'ASC') === 'DESC') ? 'DESC' : 'ASC';

		// Get the search term from args and sanitize it
		$search = isset($args['search']) ? '%' . $this->wpdb->esc_like($args['search']) . '%' : '';

		// Total count query with optional search filter
		$total_query = "SELECT COUNT(*) FROM {$this->p}reels_groups";
		if ($search) {
			$total_query .= " WHERE group_name LIKE %s";
		}
		$total = (int) $this->wpdb->get_var($this->wpdb->prepare($total_query, $search));

		// Select query with optional search filter
		$sql = "SELECT id, slug, group_name, styles_json, created_by, created_at
            FROM {$this->p}reels_groups";
		if ($search) {
			$sql .= " WHERE group_name LIKE %s";
		}
		$sql .= " ORDER BY {$orderby} {$order}
              LIMIT %d OFFSET %d";

		// Prepare and execute the query
		$sql = $this->wpdb->prepare($sql, $search, $per_page, $offset);
		$items = $this->wpdb->get_results($sql, ARRAY_A);

		foreach ($items as &$r) {
			$r['styles'] = $r['styles_json'] ? json_decode($r['styles_json'], true) : null;
			unset($r['styles_json']);
		}

		// Calculate total pages
		$total_pages = (int) ceil($total / $per_page);

		return [
			'items' => $items,
			'total' => $total,
			'total_pages' => $total_pages,
			'page' => $page,
			'per_page' => $per_page,
		];
	}


	public function get_by_id(int $id): ?array
	{
		$sql = $this->wpdb->prepare(
			"SELECT id, slug, group_name, styles_json, created_by, created_at
		 FROM {$this->p}reels_groups
		 WHERE id = %d",
			$id
		);
		$item = $this->wpdb->get_row($sql, ARRAY_A);

		if ($item) {
			$item['styles'] = $item['styles_json'] ? json_decode($item['styles_json'], true) : null;
			unset($item['styles_json']);
		}

		return $item;
	}

	public function create(array $in): array
	{
		$name = sanitize_text_field($in['group_name'] ?: $this->get_next_undefined_name());
		$base_slug = sanitize_title($name);
		$slug = $this->generate_unique_slug($base_slug);
		$styles = isset($in['styles']) ? wp_json_encode($in['styles']) : null;
		$created_by = get_current_user_id() ?: null;

		$this->wpdb->insert("{$this->p}reels_groups", [
			'slug' => $slug,
			'group_name' => $name,
			'styles_json' => $styles,
			'created_by' => $created_by,
		], ['%s', '%s', '%s', '%d']);

		return ['id' => (int) $this->wpdb->insert_id, 'group_name' => $name];
	}

	public function update(int $id, array $in): ?array
	{
		$fields = [];
		$fmts = [];
		$map = ['group_name' => '%s'];
		foreach ($map as $k => $fmt) {
			if (array_key_exists($k, $in)) {
				$fields[$k] = $in[$k];
				$fmts[] = $fmt;
			}
		}
		if (array_key_exists('styles', $in)) {
			$fields['styles_json'] = wp_json_encode($in['styles']);
			$fmts[] = '%s';
		}
		if ($fields) {
			$this->wpdb->update("{$this->p}reels_groups", $fields, ['id' => $id], $fmts, ['%d']);
		}
		return $this->get_by_id($id);
	}

	public function delete(int $id): int
	{
		$this->wpdb->delete("{$this->p}reels_groups", ['id' => $id], ['%d']);
		return $id;
	}

	public function get_next_undefined_name(): string
	{
		$base_name = 'Untitled group';
		$like = $base_name . '%';
		$last_name = $this->wpdb->get_var($this->wpdb->prepare(
			"SELECT group_name FROM {$this->p}reels_groups WHERE group_name LIKE %s ORDER BY id DESC LIMIT 1",
			$like
		));

		if (!$last_name) {
			$check_undefined = $this->wpdb->get_var($this->wpdb->prepare("SELECT group_name FROM {$this->p}reels_groups WHERE group_name = %s", $base_name));
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

	/**
	 * Generate a unique slug by appending -2, -3, etc. if needed.
	 */
	private function generate_unique_slug(string $slug): string
	{
		$unique_slug = $slug;
		$i = 2;

		while (true) {
			$exists = $this->wpdb->get_var(
				$this->wpdb->prepare("SELECT COUNT(*) FROM {$this->p}reels_groups WHERE slug = %s", $unique_slug)
			);

			if (!$exists) {
				return $unique_slug;
			}

			$unique_slug = $slug . '-' . $i;
			$i++;
		}
	}

	// For consistency with controllers; groups are top-level.
	public function group_id_for(int $group_id): int
	{
		return $group_id;
	}
}
