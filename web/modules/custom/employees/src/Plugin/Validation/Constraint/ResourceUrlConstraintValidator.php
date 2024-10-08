<?php

namespace Drupal\employees\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the PreventAnon constraint.
 */
class ResourceUrlConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity) || 
      $entity->bundle() != 'resource' || 
      empty($entity->field_resource_link[0])) {
      return;
    }

    // Check if any resource has the same value in field_resource_link
    $resources = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'field_resource_link' => $entity->field_resource_link[0]->getValue()['uri'],
    ]);

    // For existing Resource, remove itself from the query result array
    if( !$entity->isNew() ) {
      unset($resources[$entity->id()]);
    }

    // Found duplicates
    if( count($resources) > 0) {
      $resource_urls = [];
      foreach(array_values($resources) as $resource) {
        $resource_urls []= "<a href=\"" . $resource->toUrl()->toString() . "\">" . $resource->getTitle() . "</a>";
      }
      $this->context->addViolation($constraint->message . join(", ", $resource_urls));
    }
  }
}
