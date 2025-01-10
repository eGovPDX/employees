<?php

namespace Drupal\employees\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a custom selection method for entity references to only include Page and Resource content within a group.
 *
 * @EntityReferenceSelection(
 *   id = "default:employees_selection",
 *   label = @Translation("Employees Selection"),
 *   entity_types = {"node"},
 *   group = "employees",
 *   weight = 0,
 *   description = @Translation("Provides a custom selection method for entity references to only include Page and Resource content within a group."),
 * )
 */
class PageMenuLinkSelection extends DefaultSelection
{

  /**
   * The group ID to filter on.
   */
  public $group_id = "-1";

  /**
   * The bundles to filter on.
   */
  public $target_bundles = ['resource' => 'resource', 'page' => 'page'];

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = [], $match_operator = 'CONTAINS', $limit = 0)
  {
    $this->setConfiguration([
      'target_type' => 'node',
      'target_bundles' => $this->target_bundles
    ]);

    $target_type = $this->getConfiguration()['target_type'];
    $query = $this->buildEntityQuery($match, $match_operator);
    if ($this->group_id !== "-1") {
      $query->condition("field_display_in_group", $this->group_id);
    }
    if ($limit > 0) {
      $query->range(0, $limit);
    }
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }
    $options = [];
    $entities = $this->entityTypeManager
      ->getStorage($target_type)
      ->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $options[$bundle][$entity_id] = Html::escape($this->entityRepository
        ->getTranslationFromContext($entity)
        ->label() ?? '');
    }
    return $options;
  }
}
