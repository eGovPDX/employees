<?php

namespace Drupal\portland_zendesk\Plugin\views\query;

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\portland_zendesk\Client\ZendeskClient;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * Zendesk views query plugin which wraps calls to the Zendesk Tickets API in order to
 * expose the results to views.
 *
 * @ViewsQuery(
 *   id = "zendesk",
 *   title = @Translation("Zendesk"),
 *   help = @Translation("Query against the Zendesk Tickets API.")
 * )
 */
class Zendesk extends QueryPluginBase {

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    $client = new ZendeskClient();

    if ($view->storage->get('base_table') == 'zendesk_ticket') {
      $query = $view->query->options['ticket_query'];
      $response = $client->search()->find($query, ['sort_by' => 'updated_at']);
      $items = $response->results;
    }
    elseif ($view->storage->get('base_table') == 'zendesk_group') {
      $query = $view->query->options['group_query'];
      $response = $client->groups()->findAll(['query' => $query]);
      $items = $response->groups;
    }

    $index = 0;

    foreach($items as $item) {
      if ($view->storage->get('base_table') == 'zendesk_ticket') {
        $row['ticket_id'] = $item->id;
        $row['ticket_status'] = $item->status;
        $row['ticket_subject'] = $item->subject;
        $row['ticket_description'] = $item->description;
        $row['ticket_priority'] = $item->priority;
        $row['ticket_created_date'] = date("U", strtotime($item->created_at));
        $row['ticket_updated_date'] = date("U", strtotime($item->updated_at));

        $row['custom_reported_issue'] = array_column($item->custom_fields, null, 'id')['1500012743981']->value;
        $row['custom_asset_id'] = array_column($item->custom_fields, null, 'id')['1500012801542']->value;
        $row['custom_location_lat'] = array_column($item->custom_fields, null, 'id')['5581480390679']->value;
        $row['custom_location_lon'] = array_column($item->custom_fields, null, 'id')['5581490332439']->value;
        $row['custom_address'] = array_column($item->custom_fields, null, 'id')['1500012743961']->value;
        $row['custom_public_description'] = array_column($item->custom_fields, null, 'id')['7557381052311']->value;
      }
      elseif ($view->storage->get('base_table') == 'zendesk_group') {
        $row['group_id'] = $item->id;
        $row['group_name'] = $item->name;
        $row['group_description'] = $item->description;
      }

      $row['index'] = $index;
      $index = $index + 1;

      $view->result[] = new ResultRow($row);
    }
  }

  public function ensureTable($table, $relationship = NULL) {
    return '';
  }

  public function addField($table, $field, $alias = '', $params = array()) {
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['ticket_query'] = ['default' => 'type:ticket status:open form:6499767163543'];
    $options['group_query'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['ticket_query'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Zendesk Search API query string for tickets'),
      '#default_value' => $this->options['ticket_query'],
      '#description' => $this->t('Use the Zendesk Search API query string needed to display the desired ticket results. This query is used in place of view filters. Example: "type:ticket status:open form:6499767163543"'),
    ];
    $form['group_query'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Zendesk Search API query string for groups'),
      '#default_value' => $this->options['group_query'],
      '#description' => $this->t('Use the Zendesk Search API query string needed to display the desired group results. This query is used in place of view filters.'),
    ];
    parent::buildOptionsForm($form, $form_state);
  }
}
