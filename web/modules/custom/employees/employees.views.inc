<?php

/**
 * Implements hook_views_data_alter().
 */
function employees_views_data_alter(array &$data) {
  $data['groups']['is_current_user_member'] = [
    'title' => t('Is current user a member'),
    'group' => t('Group'),
    'filter' => [
      'title' => t('Is current user a member'),
      'help' => t('Whether the current user is a member of the group.'),
      'id' => 'group_is_current_user_member',
    ],
  ];
}
