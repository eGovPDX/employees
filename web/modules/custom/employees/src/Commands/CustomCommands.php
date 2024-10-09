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
  public function commandName()
  {
    $site_addr = "https://" . \Drupal::request()->getHost();
    $file_system = \Drupal::service('file_system');
    $media_storage = \Drupal::entityTypeManager()->getStorage('media');
    $ids = \Drupal::entityQuery('media')
      ->accessCheck(FALSE)
      ->condition('bundle', 'image')
      ->execute();
    $chunks = array_chunk($ids, 100);
    foreach ($chunks as $chunk_ids) {
      foreach ($chunk_ids as $media_id) {
        $media_obj = Media::load($media_id);
        $fid = $media_obj->get('field_media_image')->target_id;
        if (empty($fid)) {
          print "Missing File (id:$fid) in Image (id:$media_id). $site_addr/media/$media_id";
          print PHP_EOL;
        } else {
          $file_obj = File::load($fid);
          if (empty($file_obj)) {
            print "Missing File (id:$fid) in Image (id:$media_id). $site_addr/media/$media_id";
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
        if (empty($fid)) {
          print "Missing File (id:$fid) in Document (id:$media_id). $site_addr/media/$media_id";
          print PHP_EOL;
        } else {
          $file_obj = File::load($fid);
          if (empty($file_obj)) {
            print "Missing File (id:$fid) in Document (id:$media_id). $site_addr/media/$media_id";
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
}
