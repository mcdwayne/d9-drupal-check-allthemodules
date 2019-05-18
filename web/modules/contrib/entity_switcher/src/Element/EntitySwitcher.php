<?php

namespace Drupal\entity_switcher\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Checkbox;

/**
 * Provides a switch to toggle between two referenced entities.
 *
 * Usage example:
 * @code
 * $form['switcher'] = [
 *   '#type' => 'entity_switcher',
 *   '#data_off' => 'Off',
 *   '#data_on' => 'On',
 *   '#default_value' => 'data_off',
 *   '#entity_off' => $entity_off,
 *   '#entity_on' => $entity_on,
 * ];
 * @endcode
 *
 * @FormElement("entity_switcher")
 */
class EntitySwitcher extends Checkbox {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#data_off' => NULL,
      '#data_on' => NULL,
      '#default_value' => NULL,
      '#entity_off' => NULL,
      '#entity_on' => NULL,
      '#return_value' => 1,
      '#access_switcher' => TRUE,
      '#process' => [
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderCheckbox'],
        [$class, 'preRenderGroup'],
      ],
      '#input' => TRUE,
      '#theme' => 'entity_switcher',
      '#theme_wrappers' => ['entity_switcher_wrapper'],
      '#attached' => [
        'library' => ['entity_switcher/element.entity_switcher'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderCheckbox($element) {
    $element['#attributes']['type'] = 'checkbox';
    Element::setAttributes($element, ['id', 'name', '#return_value' => 'value']);

    // Unchecked checkbox has #value of integer 0.
    if ($element['#default_value'] == 'data_on') {
      $element['#attributes']['checked'] = 'checked';
    }

    return $element;
  }

}
