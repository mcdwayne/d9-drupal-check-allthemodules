<?php

namespace Drupal\bynder_select2\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;

/**
 * Provides a simple bynder_select2 form element.
 *
 * @FormElement("bynder_select2_simple_element")
 */
class BynderSelect2SimpleElement extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);
    $info['#process'] = [
      [$class, 'processBynderSelect2'],
    ];

    return $info;
  }

  /**
   * Processes a bynder_select2 form element.
   */
  public static function processBynderSelect2(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processSelect($element, $form_state, $complete_form);

    $class = 'bynder-select2-' . hash('md5', Html::getUniqueId('bynder-select2-simple-element'));

    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $element['#attributes']['class'][] = $class;
    $select2_settings = [
      'selector' => '.' . $class,
      'placeholder_text' =>  $element['#placeholder_text'],
      'multiple' => $element['#multiple'],
      'base_url' => $base_url
    ];
    if(isset($element['#loadRemoteData'])) {
      $select2_settings['loadRemoteData'] = ['url' => $base_url . $element['#loadRemoteData']];
    }

    $element['#attached']['drupalSettings']['bynder_select2'][$class] = $select2_settings;
    $element['#attached']['library'] = ['bynder_select2/bynder_select2.widget'];

    return $element;
  }


}