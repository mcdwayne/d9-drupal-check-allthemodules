<?php

namespace Drupal\govuk_webform_elements\Element;

use Drupal\Component\Utility\Html;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'govuk_webform_elements'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. webform_address)
 *
 * @FormElement("govuk_webform_elements")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\webform_date_composite\Element\WebformDateComposite
 */
class WebformDateComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'govuk_webform_elements'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    // Generate an unique ID that can be used by #states.
    $html_id = Html::getUniqueId('govuk_webform_elements');

    $elements = [];
    $elements['day'] = [
      '#type' => 'number',
      '#title' => t('Day'),
      '#attributes' => ['data-webform-composite-id' => $html_id . '--day', 'class' => ['govuk-webform-elements-day']],
      '#min' => 1,
      '#max' => 31,
    ];
    $elements['month'] = [
      '#type' => 'number',
      '#title' => t('Month'),
      '#attributes' => ['data-webform-composite-id' => $html_id . '--month', 'class' => ['govuk-webform-elements-month']],
      '#min' => 1,
      '#max' => 12,
    ];
    $elements['year'] = [
      '#type' => 'number',
      '#title' => t('Year'),
      '#attributes' => ['data-webform-composite-id' => $html_id . '--year', 'class' => ['govuk-webform-elements-year']],
      '#min' => 1950,
      '#max' => date('Y'),
    ];
    return $elements;
  }

}
