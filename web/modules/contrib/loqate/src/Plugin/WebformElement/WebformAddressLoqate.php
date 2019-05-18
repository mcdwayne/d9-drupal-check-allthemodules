<?php

namespace Drupal\loqate\Plugin\WebformElement;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;

/**
 * Provides an 'address' element.
 *
 * @WebformElement(
 *   id = "webform_address_loqate",
 *   label = @Translation("Address Loqate"),
 *   description = @Translation("Loqate API provides a form element to collect
 * address information (street, city, state, zip)."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformAddressLoqate extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $composite_elements = $element['#webform_composite_elements'];
    $location = '';

    // State/Province and Region can't both have values, since Region is only
    // used when S/P is not populatable.
    if (!empty($value['state_province'])) {
      if (!empty($value['city'])) {
        $location .= $value['city'];
      }
      if (!empty($value['state_province'])) {
        $location .= ($location) ? ', ' : '';
        $location .= $this->getValueFromOptions('state_province', $composite_elements, $value);
      }
      if (!empty($value['postal_code'])) {
        $location .= ($location) ? '. ' : '';
        $location .= $value['postal_code'];
      }

      $value['location'] = $location;
      unset($value['city'], $value['region'], $value['postal_code']);
    }

    // Country preprocessing.
    if (!empty($value['country'])) {
      $value['country'] = $this->getValueFromOptions('country', $composite_elements, $value);
    }

    $address_lines = [
      'address',
      'address_2',
      'location',
      'city',
      'region',
      'postal_code',
      'country',
    ];

    $display_lines = [];

    foreach ($address_lines as $line) {
      if (!empty($value[$line])) {
        $display_lines[$line] = $value[$line];
      }
    }

    return $display_lines;
  }

  /**
   * Helper function to return the human readable value of a possible select.
   *
   * @param $field
   *   The field key.
   * @param $composite_elements
   *   The composite element array.
   * @param $values
   *   The saved values array
   * @return string
   *   The human readable value of the field.
   */
  protected function getValueFromOptions($field, $composite_elements, $values) {
    $value = $values[$field];
    $field = $composite_elements[$field];
    $type = $field['#type'];
    $option_value = $type === 'select' ? $field['#options'][$value] : $value;

    if ($option_value instanceof TranslatableMarkup) {
      $option_value = $option_value->__toString();
    }

    return $option_value;
  }

}
