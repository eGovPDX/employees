<?php

namespace Drupal\employees\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\KeyRepositoryInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MultcoContactSyncForm extends FormBase {
  protected const BATCH_SIZE = 100;

  protected EntityStorageInterface $nodeStorage;
  protected Client $httpClient;
  protected KeyRepositoryInterface $keyRepository;

  public function __construct(EntityStorageInterface $node_storage,
    Client $http_client,
    KeyRepositoryInterface $key_repository) {
    $this->nodeStorage = $node_storage;
    $this->httpClient = $http_client;
    $this->keyRepository = $key_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('http_client'),
      $container->get('key.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multco_contact_sync_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['help'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Click the button below to initiate a sync from the Multnomah County employee database.'),
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Sync now'),
        '#button_type' => 'primary',
      ],
    ];

    return $form;
  }

  public function mapEmployeeJsonToContactFields($employee) {
    $CONTACT_TO_EMPLOYEE_FIELD_MAP = [
      'field_first_name' => fn($e) => $e->legalFirstName,
      'field_last_name' => fn($e) => $e->legalLastName,
      'field_multco_id' => fn($e) => $e->employeeID,
      'field_title' => fn($e) => $e->jobTitle,
      'field_email' => fn($e) => $e->email,
      'field_mail_stop' => fn($e) => $e->mailStop,
      'field_organization' => fn($e) => join(' > ', [$e->departmentName, $e->divisionName, $e->sectionName]),
      'field_phone' => fn($e) => preg_replace('/[^0-9]/', '', $e->primaryWorkPhone ?: $e->mobilePhone ?: ''),
      'field_phone_extension' => fn($e) => preg_replace('/[^0-9]/', '',$e->primaryWorkPhoneExtension ?: $e->mobilePhoneExtension ?: ''),
      'field_fax' => fn($e) => $e->faxPhone,
      'field_type' => fn() => 1807,
      'field_display_in_group' => fn() => 87,
    ];
    $fields = [];
    foreach ($CONTACT_TO_EMPLOYEE_FIELD_MAP as $field_name => $transform) {
      $fields[$field_name] = $transform($employee);
    }

    return $fields;
  }

  public function markContactForDeletion($contact) {
    $contact->set('field_marked_for_deletion', true);
    $contact->save();
  }

  public function updateExistingContact($contact_to_update, $employee) {
    $needs_save = false;
    $original_contact = $this->nodeStorage->loadUnchanged($contact_to_update->id());
    $fields = $this->mapEmployeeJsonToContactFields($employee);
    foreach ($fields as $field_name => $field_value) {
      $contact_to_update->set($field_name, $field_value);

      if (!$contact_to_update->get($field_name)->equals($original_contact->get($field_name))) $needs_save = true;
    }

    if ($needs_save) $contact_to_update->save();
  }

  public function createNewContact($employee) {
    $fields = $this->mapEmployeeJsonToContactFields($employee);
    $new_contact = $this->nodeStorage->create([
      'type' => 'contact',
      'title' => $fields['field_first_name'] . ' ' . $fields['field_last_name'],
      ...$fields,
    ]);
    $new_contact->save();
  }

  public function processEmployee($employee) {
    $existing_nodes = $this->nodeStorage->loadByProperties([
      'field_multco_id' => $employee->employeeID,
    ]);
    $existing_contact = reset($existing_nodes);
    if ($employee->workerStatus === 'Terminated') {
      if ($existing_contact) $this->markContactForDeletion($existing_contact);
      return;
    }

    if ($existing_contact) {
      $this->updateExistingContact($existing_contact, $employee);
    } else {
      $this->createNewContact($employee);
    }
  }

  public function processEmployeeChunk($chunk) {
    foreach ($chunk as $employee) {
      $this->processEmployee($employee);
    }
  }

  public function syncFinished($success) {
    if ($success) {
      \Drupal::messenger()->addStatus(t('Employees have been synced from Multnomah County.'));
    }
    else {
      \Drupal::messenger()->addError(t('There was an error syncing. Please try again later.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch_builder = (new BatchBuilder())
      ->setTitle(t('Syncing Employees'))
      ->setInitMessage(t('Sync is starting...'))
      ->setProgressMessage(t('Synced @current chunks out of @total.'))
      ->setFinishCallback([$this, 'syncFinished'])
      ->setErrorMessage(t('Sync has encountered an error.'));

    $token = $this->keyRepository->getKey('multco_worker_api_token')->getKeyValues();
    $res = $this->httpClient->get('https://mc-enterprise-ocelot-prd.azurewebsites.net/workers/api/v1/worker', [
      'auth' => [$token['username'], $token['password']],
    ]);
    $body = json_decode($res->getBody());
    $chunked_employees = array_chunk($body, self::BATCH_SIZE);
    foreach ($chunked_employees as $chunk) {
      $batch_builder->addOperation([$this, 'processEmployeeChunk'], [$chunk]);
    }

    batch_set($batch_builder->toArray());
  }
}
