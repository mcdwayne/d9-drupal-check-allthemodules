<?php

namespace Drupal\ga\AnalyticsCommand;

/**
 * Class Create.
 */
class Create extends Generic {

  const DEFAULT_PRIORITY = 300;

  /**
   * The Google Analytics property ID.
   *
   * @var string
   */
  protected $trackingId;

  /**
   * The analytics cookie domain.
   *
   * @var string
   */
  protected $cookieDomain;

  /**
   * Create constructor.
   *
   * @param string $tracking_id
   *   A Google Analytics property ID.
   * @param string $cookie_domain
   *   The cookie domain.
   * @param string $tracker_name
   *   The tracker name.
   * @param array $fields_object
   *   A set of additional options for the command.
   * @param int $priority
   *   The command priority.
   */
  public function __construct($tracking_id, $cookie_domain = 'auto', $tracker_name = NULL, array $fields_object = [], $priority = self::DEFAULT_PRIORITY) {
    parent::__construct('create', $fields_object, $tracker_name, $priority);
    $this->trackingId = $tracking_id;
    $this->cookieDomain = $cookie_domain;
  }

  /**
   * Get the tracking id.
   *
   * @return string
   *   The tracking id.
   */
  public function getTrackingId() {
    return $this->trackingId;
  }

  /**
   * Get the cookie domain.
   *
   * @return string
   *   The cookie domain.
   */
  public function getCookieDomain() {
    return $this->cookieDomain;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingCommands() {
    $command = [
      $this->command,
      $this->trackingId,
      $this->cookieDomain,
    ];

    if (!empty($this->trackerName)) {
      $command[] = $this->trackerName;
    }
    if (!empty($this->fieldsObject)) {
      $command[] = $this->fieldsObject;
    }

    return [$command];
  }

}
