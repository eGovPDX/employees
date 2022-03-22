<?php

namespace Drupal\portland_openid_connect\Util;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\File\FileSystemInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * A helper class provides static function to allow both cron jobs
 * and views bulk operations to share common logic.
 */
class PortlandOpenIdConnectUtil
{
  /* @var \GuzzleHttp\ClientInterface $client */
  private static $client;

  /**
   * Helper function to remove a user from a group
   */
  public static function removeUserFromGroup($account, $group_id)
  {
    // Automated removal should only remove the "Employee" role
    $group = \Drupal\group\Entity\Group::load($group_id);
    $membership = $group->getMember($account);
    if (empty($membership)) return;

    $roles = $membership->getRoles();
    $has_employee_role = false;
    $group_content = $membership->getGroupContent();
    $group_content->group_roles = [];
    foreach ($roles as $role) {
      if ($role->id() === 'employee-employee') {
        $has_employee_role = true;
        continue;
      }
      // Remove the "member" role
      else if ($role->id() === 'employee-member') {
        $has_employee_role = true;
        continue;
      }
      $group_content->group_roles->appendItem(['target_id' => $role->id()]);
    }
    // If the user has no role in the group, remove the user completely
      $group = \Drupal\group\Entity\Group::load($group_id);
    if($group_content->group_roles->count() === 0) {
      // Hotfix: comment out to avoid removal of membership
      // $group->removeMember($account);
      // $group->save();
    }
    // Else only remove the Employee roles. Keep roles like Following
    else {
      $group_content->save();
    }
  }

  /**
   * Helper function to add a user to a group as roles
   */
  public static function addUserToGroupWithRoles($account, $group_id, $role_id_array)
  {
    $group = \Drupal\group\Entity\Group::load($group_id);
    $membership = $group->getMember($account);
    // The user is NOT in the group
    if (empty($membership)) {
      $group->addMember($account, ['group_roles' => $role_id_array]);
      $group->save();
    }
    // The user is already in the group, check if she has all roles specified in $role_id_array
    else {
      // https://drupal.stackexchange.com/questions/232530/programmatically-add-new-role-to-group-member/232646#232646
      // Array of Role-name=>Role_entity
      $roles = $membership->getRoles();
      $group_content = $membership->getGroupContent();
      $has_new_role = false;
      foreach ($role_id_array as $role_id) {
        // Check if the user has the new role
        if (!isset($roles[$role_id])) {
          $group_content->group_roles->appendItem(['target_id' => $role_id]);
          $has_new_role = true;
        }
      }
      if ($has_new_role) $group_content->save();
    }
  }

  /**
   * Helper function to remove a user from a group.
   * Will be called by User post-save hooks and this view bulk operation.
   */
  public static function updatePrimaryGroupsForUser($account)
  {
    if (empty($account)) return;

    $new_primary_group_ids = PortlandOpenIdConnectUtil::buildGroupIDlistFromGroupNames($account->field_group_names->value);

    if ($account->isNew()) {
      $current_primary_group_ids = [];
    } else {
      $current_primary_group_ids = PortlandOpenIdConnectUtil::getGroupIdsOfUser($account);
    }

    // If the primary group is empty, remove all group memberships
    if (empty($new_primary_group_ids)) {
      // Remove user from all current groups
      foreach ($current_primary_group_ids as $current_primary_group_id) {
        PortlandOpenIdConnectUtil::removeUserFromGroup($account, $current_primary_group_id);
      }
    } else if (empty($current_primary_group_ids)) {
      // Remove user from all current groups
      foreach ($new_primary_group_ids as $new_primary_group_id) {
        PortlandOpenIdConnectUtil::addUserToGroupWithRoles($account, $new_primary_group_id, ['employee-employee']);
      }
    } else {
      // For any added group, add membership
      foreach ($new_primary_group_ids as $new_primary_group_id) {
        PortlandOpenIdConnectUtil::addUserToGroupWithRoles($account, $new_primary_group_id, ['employee-employee']);
      }

      // Check if any current group is not in the new group list
      foreach ($current_primary_group_ids as $current_primary_group_id) {
        if (in_array($current_primary_group_id, $new_primary_group_ids))
          continue;
        // Remove user from the new group
        PortlandOpenIdConnectUtil::removeUserFromGroup($account, $current_primary_group_id);
      }
    }
  }

  /**
   * Convert a comma separated string of group names into group IDs
   * using taxonomy "Group AD name list"
   */
  private static function buildGroupIDlistFromGroupNames($group_names)
  {
    // Assume the group names have been cleaned by presave hook
    $group_names_array = explode(',', $group_names);

    // Build new primary group ID array with primary group names
    $group_ad_name_list = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadTree('group_ad_name_list', 0, 1, true);
    $group_ids = [];
    foreach ($group_ad_name_list as $group_ad_name) {
      if (in_array($group_ad_name->name->value, $group_names_array)) {
        $drupal_groups = $group_ad_name->field_employee_groups->getValue();
        foreach ($drupal_groups as $drupal_group) {
          $group_ids[] = $drupal_group['target_id'];
        }
      }
    }
    // Remove duplicates
    return array_unique($group_ids);
  }

  /**
   * Return an array of group IDs that the user belongs to
   */
  public static function getGroupIdsOfUser($account)
  {
    $group_ids = [];
    $grp_membership_service = \Drupal::service('group.membership_loader');
    $grps = $grp_membership_service->loadByUser($account);
    foreach ($grps as $grp) {
      $group_ids[] = $grp->getGroup()->id();
    }
    return $group_ids;
  }

  /**
   * Call Microsoft Azure AD OAuth API to retrieve the access token.
   * Need a fresh token for each CRON job run.
   */
  public static function GetAccessToken()
  {
    if (empty(self::$client)) self::$client = new \GuzzleHttp\Client();

    static $token_expire_time = 0;
    static $tokens = null;
    // If the token has not expired, return the previous token
    if (time() < ( $token_expire_time - 300 )) return $tokens;

    $windows_aad_config = \Drupal::config('openid_connect.settings.windows_aad');
    $client_id = $windows_aad_config->get('settings.client_id');
    $tenant_id = '636d7808-73c9-41a7-97aa-8c4733642141';

    $request_options = [
      'form_params' => [
        // 'code' => $authorization_code,
        'client_id' => $client_id,
        'client_secret' => $windows_aad_config->get('settings.client_secret'),
        'grant_type' => 'client_credentials',
        'scope' => 'https://graph.microsoft.com/.default',
      ],
    ];

    try {
      $response = self::$client->post("https://login.microsoftonline.com/$tenant_id/oauth2/v2.0/token", $request_options);
      $response_data = json_decode((string) $response->getBody(), TRUE);

      // Expected result.
      $tokens = [
        // 'id_token' => $response_data['id_token'],
        'access_token' => $response_data['access_token'],
      ];
      if (array_key_exists('expires_in', $response_data)) {
        $tokens['expire'] = \Drupal::time()->getRequestTime() + $response_data['expires_in'];
      }
      $token_expire_time = time() + $response_data['expires_in'];
      return $tokens;
    } catch (RequestException $e) {
      $variables = [
        '@message' => 'Could not retrieve access token',
        '@error_message' => $e->getMessage(),
      ];
      \Drupal::logger('portland OpenID')->error('@message. Details: @error_message', $variables);
      return FALSE;
    }
  }

  /**
   * Look up the AD principal name by email.
   * A user's principal name never changes. But the email may get modified after legal name change.
   */
  public static function GetUserProfile($access_token, $userPrincipalName, $azure_ad_id)
  {
    if (empty($access_token) || empty($userPrincipalName) || empty($azure_ad_id)) return;

    if (empty(self::$client)) self::$client = new \GuzzleHttp\Client();
    // Perform the request.
    $options = [
      'method' => 'GET',
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $access_token,
        'ConsistencyLevel' => 'eventual', // required by Graph search API
      ],
    ];

    try {
      // API Document: https://docs.microsoft.com/en-us/graph/api/resources/profile-example?view=graph-rest-beta
      // Example: https://graph.microsoft.com/beta/users/xinju.wang@portlandoregon.gov/profile
      $response = self::$client->get(
        'https://graph.microsoft.com/beta/users/' . $azure_ad_id . '/profile',
        $options
      );
      $response_data = json_decode((string) $response->getBody(), TRUE);

      $user_info = [];
      if (
        array_key_exists('positions', $response_data) &&
        !empty($response_data["positions"]) &&
        array_key_exists('detail', $response_data["positions"][0])
      ) {
        $user_info['mail'] = $response_data["emails"][0]["address"];
        $user_info['title'] = $response_data["positions"][0]["detail"]["jobTitle"];
        $user_info['division'] = $response_data["positions"][0]["detail"]["company"]["department"];
        $user_info['officeLocation'] = $response_data["positions"][0]["detail"]["company"]["officeLocation"];
        $address = $response_data["positions"][0]["detail"]["company"]["address"];
        $address_parts = [];
        if( !empty($address['street']) ) $address_parts[] = $address['street'];
        if( !empty($address['city']) ) $address_parts[] = $address['city'];
        if( !empty($address['state']) ) $address_parts[] = $address['state'];
        if( !empty($address['postalCode']) ) $address_parts[] = $address['postalCode'];
        $user_info['address'] = implode(', ', $address_parts);

        // Get the user's business phone number
        $user_info['phone'] = '';
        $phones = $response_data["phones"];
        foreach($phones as $phone) {
          if($phone['type'] == 'business') {
            $user_info['phone'] = $phone['number'];
            break;
          }
        }
      }

      // Load the Drupal user with principal name
      $users = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['name' => $userPrincipalName]);

      if (count($users) != 0) {
        $user = array_values($users)[0]; // Assume the lookup returns only one unique user.
        $user->mail = $user_info['mail'];
        $user->field_title = $user_info['title'];
        $user->field_division_name = $user_info['division'];
        $user->field_office_location = $user_info['officeLocation'];
        $user->field_address = $user_info['address'];
        $user->field_phone = $user_info['phone'];
        $user->save();
        // \Drupal::logger('portland OpenID')->notice('User updated: ' . $user->mail->value);
      }
    } catch (RequestException $e) {
      // Do not log 404 errors since some users don't have profile
      if ($e->getCode() == 404) {
        // Load the Drupal user with principal name
        $users = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['name' => $userPrincipalName]);
        if (count($users) != 0) {
          $user = array_values($users)[0]; // Assume the lookup returns only one unique user.
          $user->status->value = false;
          $user->field_title = "";
          $user->field_division_name = "";
          $user->field_office_location = "";
          $user->field_address = "";
          $user->field_phone = "";
          $user->set('field_managers', []);
          $user->save();
        }
      }
      else {
        $variables = [
          '@message' => 'Could not retrieve user information for principal name ' . $userPrincipalName,
          '@error_message' => $e->getMessage(),
        ];
        \Drupal::logger('portland OpenID')->error('@message. Details: @error_message', $variables);
      }
    }
  }

  /**
   * Look up the user's manager.
   * A user's principal name never changes. But the email may get modified after legal name change.
   */
  public static function GetUserManager($access_token, $userPrincipalName, $azure_ad_id)
  {
    if (empty($access_token) || empty($userPrincipalName) || empty($azure_ad_id)) return;

    if (empty(self::$client)) self::$client = new \GuzzleHttp\Client();
    // Perform the request.
    $options = [
      'method' => 'GET',
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $access_token,
        'ConsistencyLevel' => 'eventual', // required by Graph search API
      ],
    ];

    // Load the Drupal user with AD ID
    $users = \Drupal::entityTypeManager()->getStorage('user')
      ->loadByProperties(['field_active_directory_id' => $azure_ad_id]);
    if (count($users) == 0) return;
    $user = array_values($users)[0];

    try {
      // Example: https://graph.microsoft.com/v1.0/users/xinju.wang@portlandoregon.gov/manager
      $response = self::$client->get(
        'https://graph.microsoft.com/v1.0/users/' . $azure_ad_id . '/manager',
        $options
      );
      $response_data = json_decode((string) $response->getBody(), TRUE);
/*
{
    "@odata.context": "https://graph.microsoft.com/v1.0/$metadata#directoryObjects/$entity",
    "@odata.type": "#microsoft.graph.user",
    "id": "d8f3158f-2589-4b3d-86e4-d09c048c6635",
    "businessPhones": [],
    "displayName": "Nixon, Rick",
    "givenName": "Rick",
    "jobTitle": null,
    "mail": "Rick.Nixon@portlandoregon.gov",
    "mobilePhone": null,
    "officeLocation": null,
    "preferredLanguage": null,
    "surname": "Nixon",
    "userPrincipalName": "Rick.Nixon@portlandoregon.gov"
}
*/
      if (
        array_key_exists('@odata.type', $response_data) &&
        $response_data["@odata.type"] === "#microsoft.graph.user"
      ) {
        // Try to load the Drupal user with AD ID
        $manager_ad_id = $response_data['id'];
        $manager_users = \Drupal::entityTypeManager()->getStorage('user')
          ->loadByProperties(['field_active_directory_id' => $manager_ad_id]);

        $manager_user_ids = [];
        if (count($manager_users) > 0) {
          // manager_users is an associate array with user ID as key, user entity as value
          $manager_user_ids[] = key($manager_users);
          // \Drupal::logger('portland OpenID')->notice('Found existing manager: ' . $manager_ad_id);
        } else {
          $manager_stub_user = User::create([
            'name' => $manager_ad_id, // temp name
            'mail' => $manager_ad_id . '@portlandoregon.gov', // temp email
            'pass' => user_password(), // temp password
            'status' => 1,
            'field_active_directory_id' => $manager_ad_id,
          ]);
          $manager_stub_user->save();
          $manager_user_ids[] = $manager_stub_user->id();
        }
        $user->set('field_managers', $manager_user_ids);
        $user->save();
      }
    } catch (RequestException $e) {
      $variables = [
        '@message' => 'Could not retrieve user\'s manager information for principal name ' . $userPrincipalName,
        '@error_message' => $e->getMessage(),
      ];
      \Drupal::logger('portland OpenID')->error('@message. Details: @error_message', $variables);
    }
  }

  /**
   * Get a user's photo.
   */
  public static function GetUserPhoto($access_token, $userPrincipalName, $azure_ad_id)
  {
    if (empty($access_token) || empty($userPrincipalName) || empty($azure_ad_id)) return;

    if (empty(self::$client)) self::$client = new \GuzzleHttp\Client();
    // Perform the request.
    $options = [
      'method' => 'GET',
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $access_token,
      ],
    ];

    try {
      // API Document: https://docs.microsoft.com/en-us/graph/api/resources/profile-example?view=graph-rest-beta
      // Example: https://graph.microsoft.com/v1.0/users/xinju.wang@portlandoregon.gov/photo/$value
      $response = self::$client->get(
        'https://graph.microsoft.com/v1.0/users/' . $azure_ad_id . '/photo/$value',
        $options
      );
      $file_name = str_replace('@', '_', $userPrincipalName);
      $file_name = str_replace('.', '_', $file_name);
      $user_photo_folder_name = "public://user-photo";
      \Drupal::service('file_system')->prepareDirectory(
        $user_photo_folder_name,
        FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
      );
      $user_photo_file = file_save_data(
        (string) $response->getBody(),
        'public://user-photo/' . $file_name . '.jpg',
        FileSystemInterface::EXISTS_REPLACE
      );

      // Load the Drupal user with email
      $users = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['name' => $userPrincipalName]);
      if (count($users) != 0) {
        $user = array_values($users)[0]; // Assume the lookup returns only one unique user.
        $user_display_name = $user->field_first_name->value . ' ' . $user->field_last_name->value;
        $user->user_picture->setValue(
          [
            'target_id' => $user_photo_file->id(),
            'alt'       => $user_display_name . ' profile picture',
            'title'     => $user_display_name,
          ]
        );
        $user->save();
      }
    } catch (RequestException $e) {
      // Do not log 404 errors since some users don't have pictures
      if ($e->getCode() != 404) {
        $variables = [
          '@message' => 'Could not retrieve user picture for principal name ' . $userPrincipalName,
          '@error_message' => $e->getMessage(),
        ];
        \Drupal::logger('portland OpenID')->error('@message. Details: @error_message', $variables);
      }
    }
  }
}
