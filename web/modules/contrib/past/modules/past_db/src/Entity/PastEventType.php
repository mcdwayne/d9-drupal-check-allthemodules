<?php

namespace Drupal\past_db\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Use a separate class for past_event types so we can specify some defaults
 * modules may alter.
 *
 * @ConfigEntityType(
 *   id = "past_event_type",
 *   label = @Translation("Past event type"),
 *   bundle_label = @Translation("Type"),
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "weight",
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\past_db\EventTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\past_db\Form\PastEventTypeForm",
 *     },
 *   },
 *   bundle_of = "past_event",
 * )
 */
class PastEventType extends ConfigEntityBase {
  public $id;
  public $label;
  public $weight = 0;

  /**
   * Returns whether the past_event type is locked.
   *
   * A locked event type may not be deleted or renamed.
   *
   * PastEvent types provided in code are automatically treated as locked, as
   * well as any fixed past_event type.
   */
  public function isLocked() {
    return isset($this->status) && empty($this->is_new) /* && (($this->status & ENTITY_IN_CODE) || ($this->status & ENTITY_FIXED)) @todo Is this concept obsolete? */;
  }
}
