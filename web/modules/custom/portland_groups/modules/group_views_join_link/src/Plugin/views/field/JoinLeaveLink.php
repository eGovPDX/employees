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
    // Do not display join/leave link for primary group
    foreach ($account->field_primary_groups as $primary_group) {
      if ($primary_group->getValue()['target_id'] == $group->id()) {
        $build = [
          '#markup' => \Drupal\Core\Render\Markup::create("Primary group"),
        ];
      } else if (empty($group->getMember($user))) {
        if ($group->hasPermission('join group', $user)) {
          $build = Link::createFromRoute('Join to Follow', 'entity.group.join', ['group' => $group->id(), 'destination' => $current_path])->toString();
        }
      } else {
        if ($group->getMember($user) and $group->hasPermission('leave group', $user)) {
          $build = Link::createFromRoute('Leave', 'entity.group.leave', ['group' => $group->id(), 'destination' => $current_path])->toString();
        }
      }
    }

    return $build;
  }
}
