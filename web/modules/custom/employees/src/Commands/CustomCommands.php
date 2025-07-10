<?php

namespace Drupal\employees\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupType;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\group_content_menu\Entity\GroupContentMenuType;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Custom Drush commands in Employees.
 */
final class CustomCommands extends DrushCommands
{
  /**
   * Entity type service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructs a new command object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager)
  {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Print out media items without file.
   */
  #[CLI\Command(name: 'employees:find_missing_media_files')]
  #[CLI\Usage(name: 'employees:find_missing_media_files', description: 'Print out media items without file')]
  public function find_missing_media_files()
  {
    $site_addr = "https://" . \Drupal::request()->getHost();
    $file_system = \Drupal::service('file_system');
    $media_storage = \Drupal::entityTypeManager()->getStorage('media');
    $ids = \Drupal::entityQuery('media')
      ->accessCheck(FALSE)
      ->condition('bundle', 'image')
      ->execute();
    print "fileID,mediaID,mediaBundle,createdBy,createdOn,updatedBy,updatedOn,URL";
    print PHP_EOL;
    $chunks = array_chunk($ids, 100);
    foreach ($chunks as $chunk_ids) {
      foreach ($chunk_ids as $media_id) {
        $media_obj = Media::load($media_id);
        $fid = $media_obj->get('field_media_image')->target_id;
        $createdBy = empty($media_obj->uid[0]) ? "" : $media_obj->uid[0]->entity->mail->value;
        $created = empty($media_obj->uid[0]) ? "" : date("Y/m/d h:i A", $media_obj->created->value);
        $updatedBy = empty($media_obj->revision_user[0]) ? "" : $media_obj->revision_user[0]->entity->mail->value;
        $updated = empty($media_obj->revision_user[0]) ? "" : date("Y/m/d h:i A", $media_obj->changed->value);
        if (empty($fid)) {
          print "NULL,$media_id,Image,$createdBy,\"$created\",$updatedBy,\"$updated\",$site_addr/media/$media_id";
          print PHP_EOL;
        } else {
          $file_obj = File::load($fid);
          if (empty($file_obj)) {
            print "$fid,$media_id,Image,$createdBy,\"$created\",$updatedBy,\"$updated\",$site_addr/media/$media_id";
            print PHP_EOL;
          }
        }
      }
    }

    $ids = \Drupal::entityQuery('media')
      ->accessCheck(FALSE)
      ->condition('bundle', 'document')
      ->execute();
    $chunks = array_chunk($ids, 100);
    foreach ($chunks as $chunk_ids) {
      foreach ($chunk_ids as $media_id) {
        $media_obj = Media::load($media_id);
        $fid = $media_obj->get('field_media_document')->target_id;
        $createdBy = empty($media_obj->uid[0]) ? "" : $media_obj->uid[0]->entity->mail->value;
        $created = empty($media_obj->uid[0]) ? "" : date("Y/m/d h:i A", $media_obj->created->value);
        $updatedBy = empty($media_obj->revision_user[0]) ? "" : $media_obj->revision_user[0]->entity->mail->value;
        $updated = empty($media_obj->revision_user[0]) ? "" : date("Y/m/d h:i A", $media_obj->changed->value);
        if (empty($fid)) {
          print "NULL,$media_id,Document,$createdBy,\"$created\",$updatedBy,\"$updated\",$site_addr/media/$media_id";
          print PHP_EOL;
        } else {
          $file_obj = File::load($fid);
          if (empty($file_obj)) {
            print "$fid,$media_id,Document,$createdBy,\"$created\",$updatedBy,\"$updated\",$site_addr/media/$media_id";
            print PHP_EOL;
          }
        }
      }
    }
  }

  /**
   * Drush command to delete files with 0 usages
   */
  #[CLI\Command(name: 'employees:mark_unused_files_as_temp')]
  #[CLI\Usage(name: 'employees:mark_unused_files_as_temp', description: 'Delete files with 0 usages')]
  public function mark_unused_files_as_temp()
  {
    $file_storage = $this->entityTypeManager->getStorage('file');
    $db = \Drupal::database();
    $result = $db->query("
        SELECT
          fid
        FROM
          file_managed
        LEFT JOIN file_usage
          USING (fid)
        WHERE
          count IS NULL
          AND status = 1;
      ")->fetchAll();
    $unused_fids = array_column($result, 'fid');
    foreach ($unused_fids as $fid) {
      $file = $file_storage->load($fid);
      $file->setTemporary();
      $file->save();
      print("Marked {$file->getFilename()} {$file->id()} as temp\n");
    }
  }

  /**
   * Drush command to reset the user sync process so the next cron run will restart the sync process.
   */
  #[CLI\Command(name: 'employees:reset_user_sync')]
  #[CLI\Usage(name: 'employees:reset_user_sync', description: 'Reset the user sync process')]
  public function reset_user_sync()
  {
    // Clear all items in the queue
    /** @var QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    /** @var QueueInterface $queue */
    $queue = $queue_factory->get('user_sync');
    if ($queue != null) $queue->deleteQueue();

    // Set the flag to start user sync in the next cron run
    \Drupal::state()->set('epgov.user_sync.sync_now', "true");

    // Delete variables tracking user sync progress
    \Drupal::state()->deleteMultiple([
      'epgov.user_sync.stop',
      'epgov.user_sync.last_sync_date.portlandoregon.gov',
      'epgov.user_sync.last_sync_date.police.portlandoregon.gov',
      'epgov.user_sync.last_check_removals_date.portlandoregon.gov',
      'epgov.user_sync.last_check_removals_date.police.portlandoregon.gov',
      'epgov.user_sync.drupal_user_offset.portlandoregon.gov',
      'epgov.user_sync.drupal_user_offset.police.portlandoregon.gov',
      'epgov.user_sync.resume_url.portlandoregon.gov',
      'epgov.user_sync.resume_url.police.portlandoregon.gov',
    ]);

    echo "The user sync process will start in the next cron run." . PHP_EOL;
  }

  /**
   * Helper function to look up an internal URI in a menu. Return NULL if not found.
   */
  private function find_menu_item_by_internal_uri(string $menu_name, string $internal_uri): ?MenuLinkContent
  {
    $menu_link_ids = \Drupal::entityTypeManager()->getStorage('menu_link_content')
      ->getQuery()
      ->condition('menu_name', $menu_name)
      ->condition('link.uri', $internal_uri)
      ->accessCheck(false)
      ->execute();
    if (!empty($menu_link_ids)) {
      $id = reset($menu_link_ids); // Get the first ID (in case there are duplicates, which shouldn't happen).
      $menu_link = MenuLinkContent::load($id);
      return $menu_link;
    }
    return NULL;
  }

  /**
   * Helper function to create the chain from a leaf menu link to the root level
   */
  private function create_menu_links($menu_name, $page_or_resource): MenuLinkContent
  {
    if($page_or_resource->field_parent->entity && $page_or_resource->id() === $page_or_resource->field_parent->entity->id()) {
      echo "Self as parent: https://employees.lndo.site/node/" . $page_or_resource->id() . PHP_EOL;
    }

    if ($page_or_resource->field_parent->entity && $page_or_resource->id() !== $page_or_resource->field_parent->entity->id()) {
      $parent_link = $this->create_menu_links($menu_name, $page_or_resource->field_parent->entity);
      $menu_link = $this->find_menu_item_by_internal_uri($menu_name, 'internal:/node/' . $page_or_resource->id());
      if (is_null($menu_link)) {
        $menu_link = MenuLinkContent::create([
          'title' => $page_or_resource->field_menu_url_text->value,
          'link' => [
            'uri' => 'internal:/node/' . $page_or_resource->id(),
          ],
          'menu_name' => $menu_name,
          'weight' => $page_or_resource->field_sort_weight->value,
          'parent' => 'menu_link_content:' . $parent_link->uuid(), // Set the parent.
        ]);
        $menu_link->save();
      }
      return $menu_link;
    } else {
      $menu_link = $this->find_menu_item_by_internal_uri($menu_name, 'internal:/node/' . $page_or_resource->id());
      if (is_null($menu_link)) {
        $menu_link = MenuLinkContent::create([
          'title' => $page_or_resource->field_menu_url_text->value,
          'link' => [
            'uri' => 'internal:/node/' . $page_or_resource->id(),
          ],
          'menu_name' => $menu_name,
          'weight' => $page_or_resource->field_sort_weight->value,
        ]);
        $menu_link->save();
      }
      return $menu_link;
    }
  }

  /**
   * Drush command to migrate the page menu.
   */
  #[CLI\Command(name: 'employees:migrate_page_menu')]
  #[CLI\Usage(name: 'employees:migrate_page_menu', description: 'Migrate the page menu')]
  public function migrate_page_menu()
  {
    $groups = Group::loadMultiple();
    foreach ($groups as $group) {
      // Get all the group_content_menu plugins in this group
      // The function is defined in group_content_menu.module
      $plugins = group_content_menu_get_plugins_per_group($group);

      // For each installed group_content_menu, create a new menu
      foreach ($plugins as $plugin) {
        $group_content_type = GroupContentMenuType::load($plugin->getDerivativeId());
        $group_menu = \Drupal::entityTypeManager()->getStorage('group_content_menu')->create([
          'label' => "Page menu for " . $group->label(),
          'bundle' => $plugin->getDerivativeId(),
        ]);
        $group_menu->save();

        /* @var GroupRelationshipStorageInterface $storage */
        $group_content_storage = \Drupal::entityTypeManager()->getStorage('group_content');
        $group_pages = $group_content_storage->loadByGroup($group, "group_node:page");
        $group_resources = $group_content_storage->loadByGroup($group, "group_node:resource");
        $group_pages_or_resources = array_merge($group_pages, $group_resources);
        foreach ($group_pages_or_resources as $group_page_or_resource) {
          $page_or_resource = $group_page_or_resource->getEntity();
          // Skip if the "Show In Menu" field is false
          if (! $page_or_resource->field_show_in_menu->value) continue;

          $menu_name = GroupContentMenuInterface::MENU_PREFIX . $group_menu->id();
          $this->create_menu_links($menu_name, $page_or_resource);
        }

        $relationship_type_storage = \Drupal::entityTypeManager()->getStorage('group_content_type');
        $group_relationship = \Drupal::entityTypeManager()->getStorage('group_content')->create([
          'type' => $relationship_type_storage->getRelationshipTypeId($group->bundle(), $plugin->getPluginId()),
          'gid' => $group->id(),
          'label' => $group_content_type->label(),
          'entity_id' => $group_menu,
        ]);
        $group_relationship->save();
      }
      echo "Finished migrating page menu for " . $group->label() . PHP_EOL;
    }
    return;
  }
  
  /**
   * Drush command to remove deleted references from entity reference fields.
   */
  #[CLI\Command(name: 'employees:remove_deleted_references')]
  #[CLI\Usage(name: 'employees:remove_deleted_references', description: 'Remove deleted references from entity reference fields')]
  public function remove_deleted_references()
  {
    // Iterate through all taxonomy term IDs looking for deleted terms to remove from entity reference fields.
    $field_name = 'field_tags';
    for ($entity_id = 1; $entity_id <= 2800; $entity_id++) {
      $entity = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($entity_id);
      if (empty($entity)) {
        echo "Removing references to deleted entity $entity_id from field $field_name... ";
        _remove_deleted_entity_references($entity_id, $field_name);
        echo "done." . PHP_EOL;
      }
    }

    // Iterate through all group IDs looking for deleted groups to remove from entity reference fields.
    $field_name = 'field_display_in_group';
    for ($entity_id = 1; $entity_id <= 165; $entity_id++) {
      $entity = \Drupal::entityTypeManager()->getStorage('group')->load($entity_id);
      if (empty($entity)) {
        echo "Removing references to deleted entity $entity_id from field $field_name... ";
        _remove_deleted_entity_references($entity_id, $field_name);
        echo "done." . PHP_EOL;
      }
    }
  }

  /**
   * Drush command to set entity uri: link.uri', 'entity:node/1234'
   */
  #[CLI\Command(name: 'employees:set_entity_uri_in_page_menu')]
  #[CLI\Usage(name: 'employees:set_entity_uri_in_page_menu', description: 'Convert internal node URIs in the page menu to entity URIs')]
  public function set_entity_uri_in_menu()
  {
    $query = \Drupal::entityQuery('menu_link_content')
      // ->condition('link.uri', 'entity:node/' . $node->id())
      ->condition('menu_name', GroupContentMenuInterface::MENU_PREFIX, 'STARTS_WITH')
      ->sort('id', 'ASC')
      ->accessCheck(TRUE);
    $menu_link_contents = $query->execute();
    foreach($menu_link_contents as $menu_link_content_id) {
      $menu_link_content = MenuLinkContent::load($menu_link_content_id);

      $uri = $menu_link_content->link?->uri;
      if(empty($uri)) {
        echo "Skipping empty link.uri for menu link " . $menu_link_content->id() . PHP_EOL;
        continue;
      } 
      if ( str_starts_with($uri, 'entity:') || str_starts_with($uri, 'https://')) continue;
      // If the link is an internal node URI, convert it to entity URI.
      if (preg_match('/internal:\/node\/(\d+)/', $uri, $matches)) {
        $menu_link_content->link->uri = 'entity:node/' . $matches[1];
        $menu_link_content->save();
        // echo "Updated menu link " . $menu_link_content->id() . " to use entity URI." . PHP_EOL;
      }
      else {
        echo $uri . PHP_EOL;
        continue;
      }
    }
    return;
  }

  /**
   * Drush command to set module weight.
   */
  #[CLI\Command(name: 'employees:set_module_weight')]
  #[CLI\Usage(name: 'employees:set_module_weight', description: 'Set the weight of a module. Module with higher weight is executed later.')]
  public function set_module_weight(string $module_name, int $weight)
  {
    module_set_weight($module_name, $weight);
    $this->output()->writeln("Set weight of module '{$module_name}' to {$weight}.");
  }
}
