<?php

namespace Drupal\employees\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
// use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use Drupal\user\Entity\User;

/**
 * Custom drush command file.
 *
 * @package Drupal\employees\Commands
 */
class BatchCommands extends DrushCommands
{
  public $bureau_name_id_map = [
    'Bureau of Development Services' => 21,
    'City Attorney' => 24,
    'Bureau of Environmental Services' => 19,
    'Bureau of Transportation' => 17,
    // 'Office of Management & Finance' => 1,
    // 'Water Bureau' => 1,
    'Fire Bureau' => 26,
    'Bureau of Technology Services' => 48,
    'Portland Parks and Recreation' => 36,
    'Office of Neighborhood Involvement' => 35,
    'Revenue' => 45,
    'Bureau of Human Resources' => 43,
    'Portland Housing Bureau' => 22,
    'Planning & Sustainability' => 28,
  ];

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
   * Constructs a new command object.
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
   * Drush command to import BDS contacts.
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
      while (($row = fgetcsv($handle)) !== FALSE) {
        // Skip the table header
        if( $row[0] == 'first' ) continue;
        $email = trim($row[$csv_columns['email']]);
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
          $contact_email = $row[$csv_columns['email']];
          $user_info_array = [
            'name' => substr($contact_name, 0, \Drupal\user\UserInterface::USERNAME_MAX_LENGTH),
            'pass' => user_password(),
            'mail' => $contact_email ? $contact_email : 'no.email.'.rand(1000000, 10000000).'@portland.gov',
            'field_first_name' => $row[$csv_columns['first']],
            'field_last_name' => $row[$csv_columns['last']],
            'field_phone' => $row[$csv_columns['telephone']],
            'field_mobile_phone' => $row[$csv_columns['mobilephone']],
            'field_search_keywords' => $row[$csv_columns['keywords']],
            'field_section' => $row[$csv_columns['section']],
            'field_primary_groups' => ['target_id' => $this->bureau_name_id_map[$row[$csv_columns['bureau']]]],
          ];

          $user = User::create($user_info_array);

          // Skip the default image
          preg_match('/\/([^\/]+)\?/', $row[$csv_columns['image_large_src']], $file_names);
          if (!empty($file_names) && strpos($file_names[1], 'images_0') === false) {
            // Create file object from remote URL.
            $data = file_get_contents($row[$csv_columns['image_large_src']]);
            $file = file_save_data($data, 'public://user-photo/' . $file_names[1], FileSystemInterface::EXISTS_RENAME);

            $user->user_picture->setValue(
              [
                'target_id' => $file->id(),
                'alt'       => $contact_name . ' profile picture',
                'title'     => $contact_name,
              ]
            );
          }
          $user->field_is_contact_only->value = true;
          $user->activate();
          $user->save();
          $this->output()->writeln("Created new user " . $contact_name);
        }
      }
      fclose($handle);
    }
  }


  /**
   * Drush command to delete files with 0 usages
   *
   * @command employees:mark_unused_files_as_temp
   * @usage employees:mark_unused_files_as_temp
   */
  public function mark_unused_files_as_temp() {
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
