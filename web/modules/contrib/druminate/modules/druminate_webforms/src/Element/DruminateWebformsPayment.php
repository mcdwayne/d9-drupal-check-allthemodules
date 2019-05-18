<?php

namespace Drupal\druminate_webforms\Element;

use Drupal\Component\Utility\Html;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'druminate_webforms_payment'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. webform_address)
 *
 * @FormElement("druminate_webforms_payment")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\druminate_webforms\Element\WebformExampleComposite
 */
class DruminateWebformsPayment extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    // Generate an unique ID that can be used by #states.
    $html_id = Html::getUniqueId('druminate_webforms_payment');

    $elements = [];
    // Credit Card Information.
    $elements['cc_num'] = [
      '#type' => 'number',
      '#title' => t('Credit Card Number'),
      '#attributes' => ['data-webform-composite-id' => $html_id . '--cc_num'],
    ];

    $elements['exp_month'] = [
      '#type' => 'select',
      '#options' => [
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
        12 => 12,
      ],
      '#title' => t('Expiration Month'),
      '#attributes' => ['data-webform-composite-id' => $html_id . '--exp_month'],
    ];

    $years = [];
    $date_future = date('Y', strtotime('+10 year'));
    $date_year = date('Y');
    for ($year = $date_year; $year < $date_future; $year++) {
      $years[$year] = $year;
    }
    $elements['exp_year'] = [
      '#type' => 'select',
      '#options' => $years,
      '#title' => t('Expiration Year'),
      '#attributes' => ['data-webform-composite-id' => $html_id . '--exp_year'],
    ];
    $elements['cc_cvv'] = [
      '#type' => 'number',
      '#title' => t('CVV Number'),
      '#attributes' => ['data-webform-composite-id' => $html_id . '--cc_cvv'],
      '#maxlength' => 3,
    ];

    return $elements;
  }

}
