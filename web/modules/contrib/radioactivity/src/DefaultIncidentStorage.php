<?php

namespace Drupal\radioactivity;

use Drupal\Core\State\StateInterface;

/**
 * Defines a default incident storage.
 */
class DefaultIncidentStorage implements IncidentStorageInterface {

  /**
   * The state key-value storage.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * DefaultIncidentStorage constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key-value storage.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function addIncident(Incident $incident) {
    $incidents = $this->state->get(self::STORAGE_KEY, []);
    $incidents[] = $incident;
    $this->state->set(self::STORAGE_KEY, $incidents);
  }

  /**
   * {@inheritdoc}
   */
  public function getIncidents() {
    return $this->state->get(self::STORAGE_KEY, []);
  }

  /**
   * {@inheritdoc}
   */
  public function getIncidentsByType($entity_type = '') {
    $incidents = [];

    /** @var \Drupal\radioactivity\Incident[] $stored_incidents */
    $stored_incidents = $this->state->get(self::STORAGE_KEY, []);
    foreach ($stored_incidents as $incident) {
      $incidents[$incident->getEntityTypeId()][$incident->getEntityId()][] = $incident;
    }

    if ($entity_type) {
      return isset($incidents[$entity_type]) ? $incidents[$entity_type] : [];
    }
    return $incidents;
  }

  /**
   * {@inheritdoc}
   */
  public function clearIncidents() {
    $this->state->set(self::STORAGE_KEY, []);
  }

  /**
   * {@inheritdoc}
   */
  public function injectSettings(&$page) {
    global $base_url;
    $page['#attached']['drupalSettings']['radioactivity']['type'] = 'default';
    $page['#attached']['drupalSettings']['radioactivity']['endpoint'] = $base_url . '/radioactivity/emit';
  }

}
