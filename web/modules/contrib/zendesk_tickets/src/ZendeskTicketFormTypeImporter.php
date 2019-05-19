<?php

namespace Drupal\zendesk_tickets;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\zendesk_tickets\Zendesk\ZendeskAPI;

/**
 * Provides tests for Zendesk API.
 */
class ZendeskTicketFormTypeImporter implements ContainerInjectionInterface {

  /**
   * Zendesk API object.
   *
   * @var ZendeskAPI
   */
  protected $api;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type storage manager for zendesk_ticket_form_type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityTypeStorage;

  /**
   * The state manager.
   *
   * @var StateInterface
   */
  protected $state;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param ZendeskAPI $api
   *   The Zendesk API handler.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The $entity_type_manager service.
   * @param StateInterface $state
   *   The persistent state manager service.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger factory object.
   */
  public function __construct(ZendeskAPI $api, EntityTypeManagerInterface $entity_type_manager, StateInterface $state, LoggerChannelFactoryInterface $logger_factory) {
    $this->api = $api;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeStorage = $entity_type_manager->getStorage('zendesk_ticket_form_type');
    $this->state = $state;
    $this->logger = $logger_factory->get('zendesk_tickets');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('zendesk_tickets.zendesk_api'),
      $container->get('entity_type.manager'),
      $container->get('state'),
      $container->get('logger.factory')
    );
  }

  /**
   * Get the Zendesk API object.
   *
   * @return ZendeskAPI
   *   The Zendesk API object.
   */
  public function getApi() {
    return $this->api;
  }

  /**
   * Get the logger object.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger object.
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * Get the state service object.
   *
   * @return StateInterface
   *   The state object.
   */
  public function getState() {
    return $this->state;
  }

  /**
   * Get the last imported time.
   *
   * @return int
   *   The last imported timestamp.
   */
  public function getLastImportedTime() {
    return $this->state->get('zendesk_tickets.last_import', 0);
  }

  /**
   * Import all the ticket forms.
   *
   * @return array
   *   An array with the following keys:
   *   - 'import': Form data to be imported.
   *   - 'entity': The new or existing entity if it could be created.
   *   - 'is_new': TRUE if the entity is new.
   */
  public function importAll() {
    $return = [];
    $forms = $this->api->fetchTicketForms();
    if (empty($forms)) {
      return $return;
    }

    $existing_entities = $this->entityTypeStorage->loadMultiple();
    $updated_entities = [];
    foreach ($forms as $form_id => $form) {
      $import = ['form' => $form];

      // Build the entity.
      $entity = NULL;
      $is_new = TRUE;
      if (isset($existing_entities[$form_id])) {
        // Update an existing form.
        $is_new = FALSE;
        $entity = $existing_entities[$form_id];
        $updated_entities[$form_id] = $form_id;
      }
      else {
        // Create a new form.
        $entity = $this->entityTypeStorage->create([
          'id' => $form_id,
          'status' => FALSE,
        ]);
      }

      // Set the form object and save.
      if ($entity) {
        $entity->setTicketFormObject($form);
        $import['status'] = $entity->ticketFormStatus();

        // Update status.
        // New: Set per form status.
        // Existing without status set locally in Drupal: Set per form status.
        // Existing with status set locally in Drupal: Allow Disabling a
        // locally Enabled form.
        if ($is_new || !$entity->hasLocalStatus() || ($entity->status() && !$import['status'])) {
          $entity->setStatus($import['status']);
        }

        $entity->setImportedTime(REQUEST_TIME);
        $entity->save();
      }

      // Update return.
      $return[$form_id] = [
        'import' => $import,
        'entity' => $entity,
        'is_new' => $is_new,
      ];
    }

    // Disable existing forms that were not updated.
    // These most likely were removed in Zendesk.
    if ($updated_entities) {
      $removed_entities = array_diff_key($existing_entities, $updated_entities);
      foreach ($removed_entities as $removed_entity) {
        $removed_entity->disable()->save();
      }
    }

    // Update state.
    $this->state->set('zendesk_tickets.last_import', REQUEST_TIME);

    if ($return) {
      // Log the import.
      $this->logger->notice('Zendesk ticket form import completed for @count forms.', [
        '@count' => count($return),
      ]);
    }

    return $return;
  }

}
