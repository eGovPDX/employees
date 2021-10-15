<?php

namespace Drupal\employees\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
// use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

/**
 * Custom drush command file.
 *
 * @package Drupal\employees\Commands
 */
class BatchCommands extends DrushCommands
{

  /**
   * Entity type service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;
  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;

  /**
   * Constructs a new UpdateVideosStatsController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerChannelFactory)
  {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory;
  }

  /**
   * Drush command to import BDS contacts. Some info will be used to update existing user
   * account info. Other will be used to create new Contact nodes.
   *
   * @param string $bds_contacts_file_name
   *   The BDS Contacts file in CSV format to be imported.
   * @command employees:import_bds_contacts
   * @usage employees:import_bds_contacts FILE_NAME
   */
  public function import_bds_contacts($bds_contacts_file_name)
  {
    // The current work folder is the document root "/app/web"
    if (($handle = fopen('./modules/custom/employees/' . $bds_contacts_file_name, "r")) !== FALSE) {
      $csv_columns = [
        "first" => 0,
        "last" => 1,
        "name" => 2,
        "bureau" => 3,
        "section" => 4,
        "email" => 5,
        "telephone" => 6,
        "mobilephone" => 7,
        "workspace" => 8,
        "keywords" => 9,
        "image_large_src" => 10,
      ];

      $user_storage = $this->entityTypeManager->getStorage('user');
      $node_storage = $this->entityTypeManager->getStorage('node');
      while (($row = fgetcsv($handle)) !== FALSE) {
        // Skip the table header
        if( $row[0] == 'first' ) continue;
        $email = $row[$csv_columns['email']];
        // If it's a City email address, try to find the user with email
        if (preg_match('/(?i)portlandoregon.gov$/', $email)) {
          $users = $user_storage->loadByProperties(['mail' => $email]);
          // Print out error message when can't find this user
          if (empty($users)) {
            $this->output()->writeln("Cannot find user $email");
          } else {
            // Update user fields: section, mobile phone, workspace, user photo
            // Other fields should be retrieved from Active Directory
            $user = array_values($users)[0];
            if (!empty($user)) {
              $user->field_section = $row[$csv_columns['section']];
              $user->field_mobile_phone = $row[$csv_columns['mobilephone']];
              $user->field_workspace = $row[$csv_columns['workspace']];
              $user->field_search_keywords = $row[$csv_columns['keywords']];
              // Skip the default image
              preg_match('/\/([^\/]+)\?/', $row[$csv_columns['image_large_src']], $file_names);
              if (!empty($file_names) && strpos($file_names[1], 'images_0') === false) {
                $data = file_get_contents($row[$csv_columns['image_large_src']]);
                $file = file_save_data($data, 'public://user-photo/' . $file_names[1], FileSystemInterface::EXISTS_RENAME);
                $user_display_name = $user->field_first_name->value . ' ' . $user->field_last_name->value;
                $user->user_picture->setValue(
                  [
                    'target_id' => $file->id(),
                    'alt'       => $user_display_name . ' profile picture',
                    'title'     => $user_display_name,
                  ]
                );
              }
              $user->save();
              $this->output()->writeln("Updated user $email");
            }
          }
        } else {
          // If this is not a email addrss, create a new Contact node
          // Remove the whitespaces and comma
          $contact_name = trim($row[$csv_columns['name']], ", \n\r\t\v\0");
          $node_array = [
            'type' => 'contact',
            'title' => $contact_name,
            'field_first_name' => $row[$csv_columns['first']],
            'field_last_name' => $row[$csv_columns['last']],
            'field_contact_work_phone' => $row[$csv_columns['telephone']],
            'field_contact_mobile_phone' => $row[$csv_columns['mobilephone']],
            'field_search_keywords' => $row[$csv_columns['keywords']],
            'field_contact_email' => $row[$csv_columns['email']],
            'field_section' => $row[$csv_columns['section']],
            'field_bureau' => $row[$csv_columns['bureau']],
          ];
          // Skip the default image
          preg_match('/\/([^\/]+)\?/', $row[$csv_columns['image_large_src']], $file_names);
          if (!empty($file_names) && strpos($file_names[1], 'images_0') === false) {
            // Create file object from remote URL.
            $data = file_get_contents($row[$csv_columns['image_large_src']]);
            $file = file_save_data($data, 'public://' . $file_names[1], FileSystemInterface::EXISTS_RENAME);
            $node_array['field_contact_picture'] = [
              'target_id' => $file->id(),
              'alt' => 'Hello world',
            ];
          }
          // Create a new Contact node
          $node = $node_storage->create($node_array);
          $node->save();
          $this->output()->writeln("Created contact " . $contact_name);
        }
      }
      fclose($handle);
    }
  }
}
