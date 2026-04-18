<?php
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

namespace ReelsWP\domain\repositories;

defined('ABSPATH') || exit;

class TrackingRepo extends BaseRepo
{
    public function increment_click(array $data): void
    {
        $table = $this->p . 'reels_button_clicks';

        // Sanitize data
        $group_id      = (int) ($data['group_id'] ?? 0);
        $story_id      = (int) ($data['story_id'] ?? 0);
        $story_title   = sanitize_text_field($data['story_title'] ?? '');
        $btn_uuid      = sanitize_text_field($data['btn_uuid'] ?? '');
        $button_text   = sanitize_text_field($data['button_text'] ?? '');
        $button_url    = esc_url_raw($data['button_url'] ?? '');
        $campaign_name = sanitize_text_field($data['campaign_name'] ?? '');

        if (!$btn_uuid) {
            // btn_uuid is mandatory
            return;
        }

        // Try to update first
        $updated = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE $table 
                 SET click_count = click_count + 1,
                     story_title  = %s,
                     button_text  = %s,
                     button_url   = %s,
                     campaign_name = %s
                 WHERE btn_uuid = %s",
                $story_title,
                $button_text,
                $button_url,
                $campaign_name,
                $btn_uuid
            )
        );

        // If no row was updated, insert a new one
        if ($updated === 0) {
            $this->wpdb->insert(
                $table,
                [
                    'group_id'      => $group_id,
                    'story_id'      => $story_id,
                    'story_title'   => $story_title,
                    'btn_uuid'      => $btn_uuid,
                    'button_text'   => $button_text,
                    'button_url'    => $button_url,
                    'campaign_name' => $campaign_name,
                    'click_count'   => 1,
                ],
                ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d']
            );
        }
    }


    public function get_group_stats(int $group_id): array
    {
        $button_clicks = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT id, story_id, story_title, btn_uuid, button_text, button_url, campaign_name, click_count
         FROM {$this->p}reels_button_clicks
         WHERE group_id = %d",
            $group_id
        ), ARRAY_A);

        $stories = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT s.id as story_id, s.title, s.view_count
               FROM {$this->p}reels_stories s
               JOIN {$this->p}reels_groups_stories gs ON s.id = gs.story_id
              WHERE gs.group_id = %d
           ORDER BY s.id ASC",
            $group_id
        ), ARRAY_A);

        // Cast results to appropriate types
        $button_clicks_casted = [];
        foreach ($button_clicks as $click) {
            $button_clicks_casted[] = [
                'id' => (int)$click['id'],
                'storyId' => (int)$click['story_id'],
                'storyTitle' => $click['story_title'],
                'btn_uuid' => $click['btn_uuid'],
                'buttonText' => $click['button_text'],
                'buttonUrl' => $click['button_url'],
                'campaignName' => $click['campaign_name'],
                'clickCount' => (int)$click['click_count'],
            ];
        }

        $stories_casted = [];
        foreach ($stories as $story) {
            $stories_casted[] = [
                'story_id' => (int)$story['story_id'],
                'title' => $story['title'],
                'view_count' => (int)$story['view_count'],
            ];
        }

        return [
            'stories' => $stories_casted,
            'buttons' => $button_clicks_casted,
        ];
    }
}
