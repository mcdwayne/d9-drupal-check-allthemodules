<?php

namespace Drupal\webform_cart\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'webform_cart'.
 *
 * Webform composites contain a group of sub-elements.
 *
 * @FormElement("webform_cart")
 *
 */
class WebformCart extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'webform_cart'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $element = parent::getCompositeElements($element);

    $element['name'] = [
      '#type' => 'hidden',
      '#title' => t('name'),
    ];

    $element['quantity'] = [
      '#type' => 'hidden',
      '#title' => t('Quantity'),
    ];

    $element['data1'] = [
      '#type' => 'hidden',
      '#title' => t('data1'),
    ];

    $element['data2'] = [
      '#type' => 'hidden',
      '#title' => t('data2'),
    ];

    return $element;
  }


  /**
   * @param $element
   *
   * @return mixed
   */
  public static function preRenderWebformCompositeFormElement($element) {
    $element = parent::preRenderWebformCompositeFormElement($element);

    $element['name'] = [
      '#type' => 'hidden',
      '#title' => t('name'),
    ];

    $element['quantity'] = [
      '#type' => 'hidden',
      '#title' => t('Quantity'),
    ];

    $element['data1'] = [
      '#type' => 'hidden',
      '#title' => t('Data1'),
    ];
    $element['data2'] = [
      '#type' => 'hidden',
      '#title' => t('Data2'),
    ];
    return $element;
  }

}
