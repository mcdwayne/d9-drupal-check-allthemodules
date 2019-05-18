<?php

namespace Drupal\colorapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * The Color API Color entity definition.
 *
 * @ConfigEntityType(
 *   id = "colorapi_color",
 *   label = @Translation("Color"),
 *   handlers = {
 *     "list_builder" = "Drupal\colorapi\Entity\Builder\ColorListBuilder",
 *     "form" = {
 *       "add" = "Drupal\colorapi\Form\ColorForm",
 *       "edit" = "Drupal\colorapi\Form\ColorForm",
 *       "delete" = "Drupal\colorapi\Form\ColorDeleteForm"
 *     }
 *   },
 *   config_prefix = "colorapi_color",
 *   admin_permission = "administer color configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/colors/{colorapi_color}",
 *     "delete-form" = "/admin/config/system/colors/{colorapi_color}/delete"
 *   }
 * )
 */
class Color extends ConfigEntityBase implements ColorInterface {

  /**
   * The Color ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Color label.
   *
   * @var string
   */
  public $label;

  /**
   * The Color data.
   *
   * @var \Drupal\colorapi\Plugin\DataType\ColorInterface
   */
  public $color;

  /**
   * {@inheritdoc}
   */
  public function getHexadecimal() {
    if ($color = $this->get('color')) {
      return $color;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRgb() {
    if ($color = $this->get('color')) {
      $service = \Drupal::service('colorapi.service');
      return [
        'red' => $service->hexToRgb($color, 'red'),
        'green' => $service->hexToRgb($color, 'green'),
        'blue' => $service->hexToRgb($color, 'blue'),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRed() {
    if ($color = $this->get('color')) {
      return \Drupal::service('colorapi.service')->hexToRgb($color, 'red');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGreen() {
    if ($color = $this->get('color')) {
      return \Drupal::service('colorapi.service')->hexToRgb($color, 'green');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBlue() {
    if ($color = $this->get('color')) {
      return \Drupal::service('colorapi.service')->hexToRgb($color, 'blue');
    }
  }

}
