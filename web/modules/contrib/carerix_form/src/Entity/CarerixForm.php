<?php

namespace Drupal\carerix_form\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Carerix form entity.
 *
 * @ConfigEntityType(
 *   id = "carerix_form",
 *   label = @Translation("Carerix form"),
 *   handlers = {
 *     "access" = "Drupal\carerix_form\CarerixFormAccessControlHandler",
 *     "list_builder" = "Drupal\carerix_form\Controller\CarerixFormListBuilder",
 *     "form" = {
 *       "add" = "Drupal\carerix_form\Form\CarerixFormEditForm",
 *       "edit" = "Drupal\carerix_form\Form\CarerixFormEditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     }
 *   },
 *   config_prefix = "form",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "description" = "description",
 *     "confirmation" = "confirmation",
 *     "settings" = "settings",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/carerix_form/{carerix_form}",
 *     "delete-form" = "/admin/config/system/carerix_form/{carerix_form}/delete",
 *   }
 * )
 */
class CarerixForm extends ConfigEntityBase implements CarerixFormInterface {

  /**
   * The Carerix form ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Carerix form label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Carerix form description.
   *
   * @var string
   */
  protected $description;

  /**
   * The Carerix form settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
    return $this;
  }

}
