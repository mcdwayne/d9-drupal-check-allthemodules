<?php

namespace Drupal\entity_switcher\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines a switcher settings configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "entity_switcher_setting",
 *   label = @Translation("Switcher"),
 *   label_singular = @Translation("Switcher"),
 *   label_plural = @Translation("Switchers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count switcher",
 *     plural = "@count switchers"
 *   ),
 *   admin_permission = "administer entity switchers",
 *   handlers = {
 *     "list_builder" = "Drupal\entity_switcher\SwitcherListBuilder",
 *     "access" = "Drupal\entity_switcher\SwitcherAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\entity_switcher\Form\SwitcherForm",
 *       "edit" = "Drupal\entity_switcher\Form\SwitcherForm",
 *       "delete" = "Drupal\entity_switcher\Form\SwitcherDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider"
 *     },
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/switchers/add",
 *     "edit-form" = "/admin/structure/switchers/{entity_switcher_setting}",
 *     "delete-form" = "/admin/structure/switchers/{entity_switcher_setting}/delete",
 *     "collection" = "/admin/structure/switchers"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "data_off",
 *     "data_on",
 *     "default_value",
 *     "container_classes",
 *     "slider_classes"
 *   }
 * )
 */
class Switcher extends ConfigEntityBase implements SwitcherInterface {

  /**
   * The ID of the switcher settings.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the switcher settings.
   *
   * @var string
   */
  protected $label;

  /**
   * The description of the switcher settings.
   *
   * @var string
   */
  protected $description;

  /**
   * The off value of the switcher settings.
   *
   * @var string
   */
  protected $data_off;

  /**
   * The on value of the switcher settings.
   *
   * @var string
   */
  protected $data_on;

  /**
   * The default value of the switcher settings.
   *
   * @var string
   */
  protected $default_value;

  /**
   * The container classes of the switcher settings.
   *
   * @var string
   */
  protected $container_classes;

  /**
   * The slider classes of the switcher settings.
   *
   * @var string
   */
  protected $slider_classes;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;

    return $this;
  }

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
  public function getDataOff() {
    return $this->data_off;
  }

  /**
   * {@inheritdoc}
   */
  public function setDataOff($data_off) {
    $this->data_off = $data_off;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataOn() {
    return $this->data_on;
  }

  /**
   * {@inheritdoc}
   */
  public function setDataOn($data_on) {
    $this->data_on = $data_on;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return $this->default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue($default_value) {
    $this->default_value = $default_value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerClasses() {
    return $this->container_classes;
  }

  /**
   * {@inheritdoc}
   */
  public function setContainerClasses($container_classes) {
    $this->container_classes = $container_classes;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSliderClasses() {
    return $this->slider_classes;
  }

  /**
   * {@inheritdoc}
   */
  public function setSliderClasses($slider_classes) {
    $this->slider_classes = $slider_classes;

    return $this;
  }

}
