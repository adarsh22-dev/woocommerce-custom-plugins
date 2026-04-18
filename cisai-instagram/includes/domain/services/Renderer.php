<?php

namespace ReelsWP\domain\services;

use ReelsWP\domain\repositories\GroupsRepo;
use ReelsWP\domain\repositories\StoriesRepo;

class Renderer
{

    /** Build render JSON for one group */
    public static function build_render_json_for_group(int $group_id, bool $persist = true): array
    {
        $groupsRepo = new GroupsRepo();
        $storiesRepo = new StoriesRepo();

        $group = $groupsRepo->get_by_id($group_id);
        if (!$group) return [];

        $stories = $storiesRepo->get_stories_by_group($group_id);

        $render = [
            'group_name' => $group['group_name'],
            'stories'    => [],
            'styles'     => $group['styles'], // get_by_id already decodes this
        ];

        foreach ($stories as $story) {
            $storyNode = [
                'id'    => $story['id'],
                'story_uuid'   => $story['story_uuid'],
                'title' => $story['title'],
                'views' => (int)$story['view_count'],
                'files' => [],
            ];

            // get_stories_by_group already fetches the files
            if (!empty($story['files'])) {
                foreach ($story['files'] as $file) {
                    $storyNode['files'][] = [
                        'id'               => $file['id'],
                        'file_uuid'             => $file['file_uuid'],
                        'url'              => $file['url'],
                        'mime_type'             => $file['mime_type'],
                        'text_properties'   => $file['text_properties'] ? json_decode($file['text_properties'], true) : [],
                        'button_properties' => $file['button_properties'] ? json_decode($file['button_properties'], true) : [],
                        'image_properties'  => $file['image_properties'] ? json_decode($file['image_properties'], true) : [],
                    ];
                }
            }

            $render['stories'][] = $storyNode;
        }

        if ($persist) {
            global $wpdb;
            $wpdb->update("{$wpdb->prefix}reels_groups", [
                'render_json' => wp_json_encode($render)
            ], ['id' => $group_id], ['%s'], ['%d']);
        }

        return $render;
    }
}
