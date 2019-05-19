<?php

namespace Drupal\spamicide\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\spamicide\SpamicideInterface;

/**
 * Defines the spamicide entity type.
 *
 * @ConfigEntityType(
 *   id = "spamicide",
 *   label = @Translation("spamicide"),
 *   label_collection = @Translation("spamicides"),
 *   label_singular = @Translation("spamicide"),
 *   label_plural = @Translation("spamicides"),
 *   label_count = @PluralTranslation(
 *     singular = "@count spamicide",
 *     plural = "@count spamicides",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\spamicide\SpamicideListBuilder",
 *     "form" = {
 *       "add" = "Drupal\spamicide\Form\SpamicideForm",
 *       "edit" = "Drupal\spamicide\Form\SpamicideForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "spamicide",
 *   admin_permission = "administer spamicide",
 *   links = {
 *     "collection" = "/admin/structure/spamicide",
 *     "add-form" = "/admin/structure/spamicide/add",
 *     "edit-form" = "/admin/structure/spamicide/{spamicide}",
 *     "delete-form" = "/admin/structure/spamicide/{spamicide}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Spamicide extends ConfigEntityBase implements SpamicideInterface {

  /**
   * The spamicide ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The spamicide label.
   *
   * @var string
   */
  protected $label;

  /**
   * The spamicide status.
   *
   * @var bool
   */
  protected $status;

  /**
   * The spamicide description.
   *
   * @var string
   */
  protected $description;

  /**
   * Get FormId method.
   *
   * @return int|string|null
   *   Form id.
   */
  public function getFormId() {
    return $this->id();
  }

}
