<?php

namespace Drupal\employees\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Cache\Cache;

/**
 * Processes outbound paths to add a "store" query parameter matching the current URL.
 */
class PathProcessor implements OutboundPathProcessorInterface {
  /*
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (strpos($path, "/technology-services/bts-catalog") === 0) {
      return $path;
    }

    $route_name = \Drupal::routeMatch()->getRouteName();
    if( $route_name == "view.taxonomy_term.page_bts_catalog_by_term" || 
      $route_name == "entity.node.canonical") {
      // Modify link to taxonomy term pages if the current page is a BTS Catalog page
      if( array_key_exists("entity_type", $options) && $options["entity_type"] == "taxonomy_term") {
        $term = $options["entity"];
        $vocabulary_id = $term->vid[0]->entity->id();
        if( in_array($vocabulary_id, ["bts_business_capability", "bts_business_priority", "bureaus"]) ) {
          $path = "/technology-services/bts-catalog" . $path;
        }
      }
    }
    return $path;
  }
}
