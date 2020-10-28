<?php

/**
 * @file
 * Views related hooks.
 */

/**
 * Implements hook_views_data_alter().
 */
function group_views_join_link_views_data_alter(array &$data) {
  $data['groups']['group_join_leave_link'] = [
    'title' => t('Join / Leave link'),
    'help' => t('Provides an Join / Leave link to the Group.'),
    'field' => [
      'id' => 'group_join_leave_link',
    ],
  ];
}
