<?php

/**
 * @file
 * Contains Drupal\hubspot_forms\Plugin\Field\FieldFormatter\HubspotFormFormatter.
 */

namespace Drupal\hubspot_forms\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\hubspot_forms\HubspotFormsCore;

/**
 * Plugin implementation of the 'field_hubspot_form_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_hubspot_form_formatter",
 *   module = "hubspot_forms",
 *   label = @Translation("Display Hubspot form"),
 *   field_types = {
 *     "field_hubspot_form"
 *   }
 * )
 */
class HubspotFormFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $HubspotFormsCore = new HubspotFormsCore();

    foreach ($items as $delta => $item) {
      list($portal_id, $form_id) = explode('::', $item->form_id);
      if ($HubspotFormsCore->isConnected()) {
        $elements[$delta] = [
          '#theme'     => 'hubspot_form',
          '#portal_id' => $portal_id,
          '#form_id'   => $form_id,
          '#locale'    => $langcode,
        ];
      }
      else {
        $elements[$delta] = [
          '#markup' => $this->t('Please provide a valid Hubspot API key.'),
        ];
      }
    }

    return $elements;
  }

}
