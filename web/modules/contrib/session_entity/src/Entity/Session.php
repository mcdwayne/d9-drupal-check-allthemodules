<?php

namespace Drupal\session_entity\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the session entity class.
 *
 * @ContentEntityType(
 *   id = "session",
 *   label = @Translation("Session"),
 *   label_singular = @Translation("session"),
 *   label_plural = @Translation("sessions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count session",
 *     plural = "@count sessions"
 *   ),
 *   bundle_label = @Translation("Session type"),
 *   handlers = {
 *     "storage" = "Drupal\session_entity\SessionStorage",
 *     "form" = {
 *       "default" = "Drupal\session_entity\SessionForm",
 *       "edit" = "Drupal\session_entity\SessionForm"
 *     },
 *   },
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "sid",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/session/{session}",
 *     "edit-form" = "/session/{session}/edit",
 *   },
 *   field_ui_base_route = "session_entity.settings"
 * )
 */
class Session extends ContentEntityBase {

}
