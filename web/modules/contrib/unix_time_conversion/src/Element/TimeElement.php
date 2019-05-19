<?php

/**
 * @file
 * Contains \Drupal\unix_time_conversion\Element\TimeElement.
 */

namespace Drupal\unix_time_conversion\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\Element;

/**
 * Provides an example element.
 *
 * @RenderElement("time_element")
 */
class TimeElement extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#tree' => TRUE,
      '#process' => array(array($class, 'processTime')),
      '#theme_wrappers' => array('form_element'),
    );
  }

  /**
   * Process callback for time field defined in hook_element_info().
   */
  public static function processTime(&$element, FormStateInterface $form_state, &$complete_form) {
    // Include the helper functions file.
    module_load_include('inc', 'unix_time_conversion', 'unix_time_conversion.helper_functions');
    // Container element.
    $element['#tree'] = TRUE;
    $element['time_container'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline unix_time_conversion_time_field_container'),
      ),
    );
    // Hours element.
    $element['time_container']['hours'] = array(
      '#title' => t('Hours'),
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => unix_time_conversion_get_time_in_range(0, 23),
    );
    // Minutes element.
    $element['time_container']['minutes'] = array(
      '#title' => t('Minutes'),
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => unix_time_conversion_get_time_in_range(0, 59),
    );
    // Seconds element.
    $element['time_container']['seconds'] = array(
      '#title' => t('Seconds'),
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => unix_time_conversion_get_time_in_range(0, 59),
    );

    // Return element.
    return $element;
  }

}
