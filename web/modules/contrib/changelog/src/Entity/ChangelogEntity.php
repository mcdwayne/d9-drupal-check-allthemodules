<?php

namespace Drupal\changelog\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Changelog entity.
 *
 * @ConfigEntityType(
 *   id = "changelog",
 *   label = @Translation("Changelog"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\changelog\ChangelogEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\changelog\Form\ChangelogEntityForm",
 *       "edit" = "Drupal\changelog\Form\ChangelogEntityForm",
 *       "delete" = "Drupal\changelog\Form\ChangelogEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\changelog\ChangelogEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "changelog",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/changelog/{changelog}",
 *     "add-form" = "/admin/config/changelog/add",
 *     "edit-form" = "/admin/config/changelog/{changelog}/edit",
 *     "delete-form" = "/admin/config/changelog/{changelog}/delete",
 *     "collection" = "/admin/config/changelog"
 *   }
 * )
 */
class ChangelogEntity extends ConfigEntityBase implements ChangelogEntityInterface {

  /**
   * The Changelog ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Changelog Label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Changelog version.
   *
   * @var string
   */
  protected $version;

  /**
   * The changelog text.
   *
   * @var string
   */
  protected $value;

  /**
   * The changelog input format.
   *
   * @var string
   */
  protected $format;

  /**
   * The changelog input format.
   *
   * @var string
   */
  protected $created;

  /**
   * Fetch the change label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Set the change label.
   */
  public function setCreatedTime($timestamp) {
    $this->created = (int) $timestamp;
  }

  /**
   * Set the change label.
   */
  public function getCreatedTime() {
    return $this->created;
  }

  /**
   * Fetch the version text.
   */
  public function getLogVersion() {
    return $this->version;
  }

  /**
   * Fetch the message format.
   */
  public function getLogFormat() {
    if (empty($this->format)) {
      return 'full_html';
    }
    return $this->format;
  }

  /**
   * Set the message format.
   *
   * @param string $format
   *   A valid message format.
   */
  public function setLogFormat($format) {
    $this->format = $format;
  }

  /**
   * Set the message format.
   *
   * @param string $value
   *   The HTML message.
   */
  public function setLogValue($value) {
    $this->value = $value;
  }

  /**
   * Fetch the message value.
   */
  public function getLogValue() {
    return $this->value;
  }

  /**
   * Fetch log entries.
   *
   * @return array
   *   The entities, as returned by entityQuery.
   */
  public static function getLogEntries() {
    return \Drupal::entityQuery('changelog')
      ->sort('version', 'DESC')
      ->execute();
  }

}
