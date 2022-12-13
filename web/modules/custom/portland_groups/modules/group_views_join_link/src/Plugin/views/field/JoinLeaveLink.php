<?php

namespace Drupal\group_views_join_link\Plugin\views\field;

use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides request membership link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_join_leave_link")
 */
final class JoinLeaveLink extends FieldPluginBase
{

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * RequestMembership constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy()
  {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query()
  {
    // Intentionally override query to do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values)
  {
    $group = $values->_entity;
    $build = NULL;
    $user = \Drupal::currentUser();
    $current_path = \Drupal::service('path.current')->getPath();
    $account = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $membership = $group->getMember($account);
    if (!empty($membership)) {
      // Do not display join/leave link if the user has Employee or Assigned role
      $roles = $membership->getRoles();
      $roles_to_match = ["employee-employee", "employee-assigned", "private-employee", "private-assigned", "employee-admin", "employee-editor"];
      foreach ($roles as $role) {
        if ( in_array($role->id(), $roles_to_match) ) {
          return;
        }
      }
    }

    if (empty($group->getMember($user))) {
      if ($group->hasPermission('join group', $user)) {
        $options = [ 
          'attributes' => [
            'class' => [
              'btn',
              'btn-outline-primary',
            ]
          ]
        ];
        $build = Link::createFromRoute('Follow', 'entity.group.follow', ['group' => $group->id(), 'destination' => $current_path], $options)->toString();
      }
    } else {
      if ($group->getMember($user) and $group->hasPermission('leave group', $user)) {
        $options = [ 
          'attributes' => [
            'class' => [
              'btn',
              'btn-outline-danger',
            ]
          ]
        ];
        $build = Link::createFromRoute('Leave', 'entity.group.leave', ['group' => $group->id(), 'destination' => $current_path], $options)->toString();
      }
    }

    return $build;
  }
}
