<?php

namespace Drupal\employees;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;

class EntityAutocompleteMatcher extends \Drupal\Core\Entity\EntityAutocompleteMatcher {

  /**
   * Gets matched labels based on a given search string.
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    $referer = \Drupal::request()->headers->get('referer');
    $group_id = "-1";
    if(!empty($referer)) {
      $url_parts = parse_url($referer);
      if(array_key_exists("path", $url_parts)) {
        $path_parts = explode("/", $url_parts["path"]);
        if(count($path_parts) >= 2) {
          // When the referer path is like /group/19/menu/6/add-link
          if($path_parts[1] == "group" && $path_parts[3] == "menu" && in_array($path_parts[5], ["add-link", "edit", "link"])) {
            $group_id = (count($path_parts) >= 3) ? $path_parts[2] : "-1";
          }
          // When the referer path is like /bes/menu/6/add-link
          else if( $path_parts[2] == "menu" && in_array($path_parts[4], ["add-link", "edit", "link"]) ){
            // Convert alias into the cannonical path
            $group_path = \Drupal::service('path_alias.manager')->getPathByAlias("/".$path_parts[1]);
            if (strpos($group_path, '/group/') === 0) {
              $group_path_parts = explode('/', $group_path);
              $group_id = (count($group_path_parts) >= 3) ? $group_path_parts[2] : "-1";
            }
          }
        }
      }
    }
    // If the group ID is not found in the referer path, use the default selection handler
    if($group_id === "-1") {
      return parent::getMatches($target_type, $selection_handler, $selection_settings, $string);
    }

    $matches = [];

    $options = [
      'target_type'      => $target_type,
      'handler'          => "default:employees_selection",
      'handler_settings' => $selection_settings,
    ];

    $handler = $this->selectionManager->getInstance($options);
    $handler->group_id = $group_id;

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {

          $entity = \Drupal::entityTypeManager()->getStorage($target_type)->load($entity_id);
          $entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity);

          $type = !empty($entity->type->entity) ? $entity->type->entity->label() : $entity->bundle();
          $status = '';
          if ($entity->getEntityType()->id() == 'node') {
            $status = ($entity->isPublished()) ? ", Published" : ", Unpublished";
          }

          $key = $label . ' (' . $entity_id . ')';
          // Strip things like starting/trailing white spaces, line breaks and tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);
          $label = $label . ' (' . $entity_id . ') [' . $type . $status . ']';
          $matches[] = ['value' => $key, 'label' => $label];
        }
      }
    }

    return $matches;
  }
}