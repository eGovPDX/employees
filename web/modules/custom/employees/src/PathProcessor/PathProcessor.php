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
    if (strpos($path, "/technology-services/catalog") === 0) {
      return $path;
    }

    $route_name = \Drupal::routeMatch()->getRouteName();
    if( in_array($route_name, ["view.taxonomy_term.page_bts_catalog_by_term" , 
      "entity.node.canonical",
      "view.search_index.page_bts_catalog"]) ) {
      // Modify link to taxonomy term pages if the current page is a BTS Catalog page
      if( array_key_exists("entity_type", $options) && $options["entity_type"] == "taxonomy_term") {
        $term = $options["entity"];
        $vocabulary_id = $term->vid[0]->entity->id();
        if( in_array($vocabulary_id, ["bts_business_capability", "bts_business_priority", "bureaus"]) ) {
          $path = "/technology-services/catalog" . $path;
        }
      }
    }
    return $path;
  }
}
