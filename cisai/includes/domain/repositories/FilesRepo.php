<?php
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

namespace ReelsWP\domain\repositories;

defined('ABSPATH') || exit;

class FilesRepo extends BaseRepo
{

	public function create(int $story_id, array $in): array
	{
		$uuid = !empty($in['file_uuid']) ? sanitize_text_field($in['file_uuid']) : wp_generate_uuid4();
		$mid  = array_key_exists('wp_media_id', $in) ? (int)$in['wp_media_id'] : null;
		$url  = isset($in['url']) ? esc_url_raw($in['url']) : '';
		$mime = isset($in['mime_type']) ? sanitize_text_field($in['mime_type']) : 'application/octet-stream';
		$text_properties = isset($in['text_properties']) ? wp_json_encode($in['text_properties']) : null;
		$button_properties = isset($in['button_properties']) ? wp_json_encode($in['button_properties']) : null;
		$image_properties = isset($in['image_properties']) ? wp_json_encode($in['image_properties']) : null;

		$this->wpdb->insert("{$this->p}reels_files", [
			'story_id'    => $story_id,
			'file_uuid'   => $uuid,
			'wp_media_id' => $mid,
			'url'         => $url,
			'mime_type'   => $mime,
			'text_properties' => $text_properties,
			'button_properties' => $button_properties,
			'image_properties' => $image_properties,
		], ['%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s']);

		return [
			'id'       => (int)$this->wpdb->insert_id,
			'file_uuid' => $uuid,
			'story_id' => $story_id,
		];
	}

	public function update(int $id, array $in): array
	{
		$fields = [];
		$fmts = [];
		$map = [
			'wp_media_id' => '%d',
			'url'         => '%s',
			'mime_type'   => '%s',
			'story_id'    => '%d'
		];
		foreach ($map as $k => $fmt) {
			if (array_key_exists($k, $in)) {
				$fields[$k] = $in[$k];
				$fmts[] = $fmt;
			}
		}

		$json_map = [
			'text_properties'   => 'text_properties',
			'button_properties' => 'button_properties',
			'image_properties'  => 'image_properties'
		];
		foreach ($json_map as $in_key => $db_key) {
			if (array_key_exists($in_key, $in)) {
				$fields[$db_key] = wp_json_encode($in[$in_key]);
				$fmts[] = '%s';
			}
		}

		if ($fields) {
			$this->wpdb->update("{$this->p}reels_files", $fields, ['id' => $id], $fmts, ['%d']);
		}

		$file_data = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT file_uuid, story_id FROM {$this->p}reels_files WHERE id = %d",
			$id
		), ARRAY_A);

		return [
			'id' => $id,
			'file_uuid' => $file_data['file_uuid'],
			'story_id' => (int)$file_data['story_id'],
		];
	}

	public function delete(int $id): int
	{
		$this->wpdb->delete("{$this->p}reels_files", ['id' => $id], ['%d']);
		return $id;
	}

	public function delete_files_by_story(int $story_id): void
	{
		$this->wpdb->delete("{$this->p}reels_files", ['story_id' => $story_id], ['%d']);
	}

	public function get_files_by_story(int $story_id): array
	{
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->p}reels_files WHERE story_id = %d ORDER BY id ASC",
			$story_id
		);
		return $this->wpdb->get_results($sql, ARRAY_A);
	}
}
