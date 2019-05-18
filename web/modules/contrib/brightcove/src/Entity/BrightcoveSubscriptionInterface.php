<?php

namespace Drupal\brightcove\Entity;

/**
 * Provides an interface for defining Brightcove Subscription entities.
 */
interface BrightcoveSubscriptionInterface {

  /**
   * The status of the Subscription.
   *
   * @return bool
   *   It always returns TRUE except for default, which can be FALSE.
   */
  public function isActive();

  /**
   * Whether the Subscription is default or not.
   *
   * @return bool
   *   If the current Subscription is default return TRUE, otherwise FALSE.
   */
  public function isDefault();

  /**
   * Determines whether an entity is new or not.
   *
   * @return bool
   *   If the entity doesn't have an ID then it will be treated as new, so TRUE
   *   will be return, otherwise FALSE will be return if it's an already
   *   existing entity.
   */
  public function isNew();

  /**
   * Returns the API Client ID.
   *
   * @return \Drupal\brightcove\BrightcoveAPIClientInterface
   *   The API Client for this Subscription.
   */
  public function getApiClient();

  /**
   * Returns the Brightcove Subscription ID.
   *
   * @return string|null
   *   Brightcove Subscription ID if exist, NULL otherwise.
   */
  public function getBcSid();

  /**
   * Returns the Subscription endpoint.
   *
   * @return string
   *   The endpoint for the Subscription.
   */
  public function getEndpoint();

  /**
   * Returns subscribed events.
   *
   * @return string[]
   *   Array of events subscribed to.
   */
  public function getEvents();

  /**
   * Gets the Subscription's Drupal ID.
   *
   * @return int|null
   *   ID of the subscription, or NULL if it's a new entity.
   */
  public function getId();

  /**
   * Sets the API Client ID.
   *
   * @param \Drupal\brightcove\Entity\BrightcoveAPIClient|null $api_client
   *   The API Client.
   *
   * @return $this
   */
  public function setApiClient($api_client);

  /**
   * Sets the Brightcove Subscription ID.
   *
   * @param string $bcsid
   *   Brightcove Subscription ID.
   *
   * @return $this
   */
  public function setBcSid($bcsid);

  /**
   * Set the endpoint for the subscription.
   *
   * @param string $endpoint
   *   The Subscription's endpoint.
   *
   * @return $this
   */
  public function setEndpoint($endpoint);

  /**
   * Sets the events for which we want to subscribe.
   *
   * @param string[] $events
   *   Array of events to subscribe to.
   *
   * @return $this
   */
  public function setEvents(array $events);

  /**
   * Set the entity's status.
   *
   * @param bool $status
   *   TRUE if enabled, FALSE if disabled.
   *
   * @return $this
   */
  public function setStatus($status);

}
