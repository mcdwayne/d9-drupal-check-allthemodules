<?php

namespace Drupal\landingpage\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'paragraph_donate_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "paragraph_donate_field_formatter",
 *   label = @Translation("Paragraph donate field formatter"),
 *   field_types = {
 *     "paragraph_donate_field_type"
 *   }
 * )
 */
class ParagraphDonateFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      // Implement default settings.
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return array(
      // Implement settings form.
    ) + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $elements['#attached']['library'][] = 'landingpage/donate.bitcoin';
    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return '<div style="font-size:16px;margin:0 auto;width:300px" class="blockchain-btn"
      data-address="' . nl2br(Html::escape($item->value)) . '"
      data-shared="false">
      <div class="blockchain stage-begin">
      <img src="https://blockchain.info/Resources/buttons/donate_64.png"/>
      </div>
      <div class="blockchain stage-loading" style="text-align:center">
      <img src="https://blockchain.info/Resources/loading-large.gif"/>
      </div>
      <div class="blockchain stage-ready">
      <p align="center">Please Donate To Bitcoin Address: <b>[[address]]</b></p>
      <p align="center" class="qr-code"></p>
      </div>
      <div class="blockchain stage-paid">
      Donation of <b>[[value]] BTC</b> Received. Thank You.
      </div>
      <div class="blockchain stage-error">
      <font color="red">[[error]]</font>
      </div>
      </div>';
  }

}
