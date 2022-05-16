<?php

namespace Drupal\employees\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Drupal\search_api_solr\Event\PreQueryEvent;

/**
 * Handle Feeds events
 *
 * @package Drupal\employees\EventSubscriber
 */
class SolrEventsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      SearchApiSolrEvents::PRE_QUERY => 'prequery',
    ];
  }

  /**
   * Convert fields before validation.
   * @param Drupal\feeds\Event\EntityEvent $event
   *
   * @return Drupal\feeds\Event\EntityEvent $event
   */
  public function prequery(PreQueryEvent $event) {
    $query = $event->getSearchApiQuery();
    $solarium_query = $event->getSolariumQuery();
    $index = $query->getIndex();

    // Only alter the query if the boost field exists in the index
    $boost_field_name = 'field_search_boost';
    $fields = $index->getServerInstance()->getBackend()->getSolrFieldNames($index);
    $solr_field = !empty($fields[$boost_field_name]) ? $fields[$boost_field_name] : '';
    if ($solr_field) {
      // Multiply the query score with the search boost field value
      $solarium_query->addParam('sort', 'fts_field_search_boost desc');
    }
  }
}
