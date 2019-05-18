<?php

namespace Drupal\radioactivity;

use \Drupal\radioactivity\Incident;

/**
 * Defines the incident storage interface.
 */
interface IncidentStorageInterface {

  /**
   * The key to identify the radioactivity incident storage.
   */
  const STORAGE_KEY = 'radioactivity_incidents';

  /**
   * Adds an incident to the storage.
   *
   * @param \Drupal\radioactivity\Incident $incident
   *   The incident class.
   */
  public function addIncident(Incident $incident);

  /**
   * Gets all incidents from the storage.
   *
   * @return \Drupal\radioactivity\Incident[]
   *   Array of incident objects.
   */
  public function getIncidents();

  /**
   * Gets all incidents from the storage per entity type.
   *
   * @param string $entity_type
   *   Entity type for selection. Default to all entity types.
   *
   * @return \Drupal\radioactivity\Incident[][]
   *   Array of incident objects keyed by entity type (1st) and entity ID (2nd).
   */
  public function getIncidentsByType($entity_type = '');

  /**
   * Clears the incident storage.
   */
  public function clearIncidents();

  /**
   * Add endpoint settings to the page.
   */
  public function injectSettings(&$page);

}
