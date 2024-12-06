<?php

namespace Drupal\employees\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
        $created = empty($media_obj->uid[0]) ? "" : date( "Y/m/d h:i A", $media_obj->created->value);
        $updatedBy = empty($media_obj->revision_user[0]) ? "" : $media_obj->revision_user[0]->entity->mail->value;
        $updated = empty($media_obj->revision_user[0]) ? "" : date( "Y/m/d h:i A", $media_obj->changed->value);
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
        $created = empty($media_obj->uid[0]) ? "" : date( "Y/m/d h:i A", $media_obj->created->value);
        $updatedBy = empty($media_obj->revision_user[0]) ? "" : $media_obj->revision_user[0]->entity->mail->value;
        $updated = empty($media_obj->revision_user[0]) ? "" : date( "Y/m/d h:i A", $media_obj->changed->value);
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
    if( $queue != null ) $queue->deleteQueue();

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
}
