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

  static $prefix_map = [
    "view.taxonomy_term.page_applications_with_term" => "/technology-services/application-catalog",
    "view.taxonomy_term.page_services_with_term" => "/technology-services/service-catalog",
  ];

  /*
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $route_name = \Drupal::routeMatch()->getRouteName();

    if( in_array($route_name, ["view.taxonomy_term.page_applications_with_term", "view.taxonomy_term.page_applications_with_term"]) ) {
      // Modify link to taxonomy term pages if the current page is a BTS Catalog page
      if( array_key_exists("entity_type", $options) && $options["entity_type"] == "taxonomy_term") {
        $term = $options["entity"];
        $vocabulary_id = $term->vid[0]->entity->id();
        if( in_array($vocabulary_id, ["bts_business_capability", "bts_business_priority"]) ) {
          $path = PathProcessor::$prefix_map[$route_name] . $path;
        }
      }
      // Modify link to BTS catalog items if the current page is a BTS Catalog page
      else if( array_key_exists("entity_type", $options) && 
            $options["entity_type"] == "node" && 
            $options["entity"]->bundle() == "bts_catalog_item" ) {
        $path = PathProcessor::$prefix_map[$route_name] . $path;
      }
    }
    return $path;
  }
}