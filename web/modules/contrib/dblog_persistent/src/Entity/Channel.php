<?php

namespace Drupal\dblog_persistent\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Persistent Log Message Type entity.
 *
 * @ConfigEntityType(
 *   id = "dblog_persistent_channel",
 *   label = @Translation("Persistent Log Channel"),
 *   label_collection = @Translation("Persistent log channels"),
 *   handlers = {
 *     "list_builder" = "Drupal\dblog_persistent\ChannelListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dblog_persistent\Form\ChannelForm",
 *       "edit" = "Drupal\dblog_persistent\Form\ChannelForm",
 *       "delete" = "Drupal\dblog_persistent\Form\ChannelDeleteForm",
 *       "clear" = "Drupal\dblog_persistent\Form\ChannelClearForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "channel",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/reports/persistent-log/{dblog_persistent_channel}",
 *     "add-form" = "/admin/reports/persistent-log/add-channel",
 *     "edit-form" = "/admin/reports/persistent-log/{dblog_persistent_channel}/edit",
 *     "delete-form" = "/admin/reports/persistent-log/{dblog_persistent_channel}/delete",
 *     "clear-form" = "/admin/reports/persistent-log/{dblog_persistent_channel}/clear",
 *     "collection" = "/admin/reports/persistent-log"
 *   }
 * )
 */
class Channel extends ConfigEntityBase implements ChannelInterface {

  /**
   * The Persistent Log Message Type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Persistent Log Message Type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The list of types.
   *
   * @var string[]
   */
  protected $types = [];

  /**
   * The minimum severity.
   *
   * @var int[]
   */
  protected $levels = [];

  /**
   * Message substring.
   *
   * @var string
   */
  protected $message = '';

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    return $this->types;
  }

  /**
   * {@inheritdoc}
   */
  public function getLevels(): array {
    return $this->levels;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage(): string {
    return $this->message;
  }

  /**
   * {@inheritdoc}
   */
  public function matches(int $level, string $type, string $message): bool {
    return (
      (!$this->levels || !empty($this->levels[$level])) &&
      (!$this->types || !empty($this->types[$type])) &&
      (!$this->message || \strpos($message, $this->message) !== FALSE)
    );
  }

}
