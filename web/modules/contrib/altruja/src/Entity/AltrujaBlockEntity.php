<?php

namespace Drupal\altruja\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\altruja\AltrujaBlockInterface;

/**
 * Defines the altruja block entity.
 *
 * @ConfigEntityType(
 *   id = "altruja_block",
 *   label = @Translation("Altruja block"),
 *   handlers = {
 *     "list_builder" = "Drupal\altruja\Controller\AltrujaBlockListBuilder",
 *     "form" = {
 *       "add" = "Drupal\altruja\Form\AltrujaBlockForm",
 *       "edit" = "Drupal\altruja\Form\AltrujaBlockForm",
 *       "delete" = "Drupal\altruja\Form\AltrujaBlockDeleteForm",
 *     }
 *   },
 *   config_prefix = "blocks",
 *   admin_permission = "administer altruja",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "embed_type" = "embed_type",
 *     "code" = "code",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/services/altruja/blocks/{altruja_block}",
 *     "delete-form" = "/admin/config/services/altruja/blocks/{altruja_block}/delete",
 *   }
 * )
 */
class AltrujaBlockEntity extends ConfigEntityBase implements AltrujaBlockInterface {

  /**
   * The altruja block ID.
   *
   * @var string
   */
  public $id;

  /**
   * The altruja block label.
   *
   * @var string
   */
  public $label;

  /**
   * The altruja block embed type.
   *
   * @var string
   */
  public $embed_type;

  /**
   * The altruja block code.
   *
   * @var string
   */
  public $code;

  /**
   * Returns the block id.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Returns the block title.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Returns the block code.
   */
  public function getEmbedType() {
    return $this->embed_type;
  }

  /**
   * Returns the block code.
   */
  public function getCode() {
    return $this->code;
  }

  public function getPlaceholder() {
    return 'ALTRUJA-' . strtoupper($this->embed_type) . '-' . strtoupper($this->code);
  }

}
