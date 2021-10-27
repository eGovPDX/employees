<?php

namespace Drupal\employees\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\Core\URL;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Cache\Cache;
use Drupal\search_api\Entity\Index;

class ContactListController extends ControllerBase
{

  public function deleteContactFromContactList(NodeInterface $node, $contact_list_id)
  {
    return $this->deleteFromContactList($node, $contact_list_id);
  }

  public function deleteUserFromContactList($user, $contact_list_id)
  {
    return $this->deleteFromContactList($user, $contact_list_id);
  }

  private function deleteFromContactList($entity, $contact_list_id) {
    if ($entity->hasField('field_parent_contact_lists')) {
      $contact_lists = $entity->get('field_parent_contact_lists')->getValue();
      $index_to_remove = array_search(['target_id' => $contact_list_id], $contact_lists);
      $entity->get('field_parent_contact_lists')->removeItem($index_to_remove);
      $entity->save();
      \Drupal::messenger()->addStatus("Item removed from this contact list");
    }

    // Force reindex the newly updated entity. Without this, the contact list will show 
    // stale content.
    $entity_id = $entity->id();
    $index = Index::load('full_index');
    $entity_type = $entity->getEntityTypeId();
    $items = $index->loadItemsMultiple(["entity:$entity_type/$entity_id:en"]);
    if ($items) {
      $index->indexSpecificItems($items);
    }

    $response = new RedirectResponse("/node/$contact_list_id");
    return $response;
  }
}
