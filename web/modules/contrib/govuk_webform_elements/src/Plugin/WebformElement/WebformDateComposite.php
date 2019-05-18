<?php

namespace Drupal\govuk_webform_elements\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'govuk_webform_elements' element.
 *
 * @WebformElement(
 *   id = "govuk_webform_elements",
 *   label = @Translation("GOV.UK date composite"),
 *   description = @Translation("Provides a GOV.UK element date."),
 *   category = @Translation("GOV.UK elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\govuk_webform_elements\Element\WebformDateComposite
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class WebformDateComposite extends WebformCompositeBase {

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

    $lines = [];
    $lines[] = ($value['day'] ? str_pad($value['day'], 2, '0', STR_PAD_LEFT) : '') .
      ($value['month'] ? '/' . str_pad($value['month'], 2, '0', STR_PAD_LEFT) : '') .
      ($value['year'] ? '/' . $value['year'] : '');
    return $lines;
  }

}
