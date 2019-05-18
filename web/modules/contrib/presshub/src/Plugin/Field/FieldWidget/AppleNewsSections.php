<?php

namespace Drupal\presshub\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\presshub\PresshubHelper;

/**
 * Plugin implementation of the 'field_apple_news_sections' widget.
 *
 * @FieldWidget(
 *   id = "field_apple_news_sections",
 *   module = "presshub",
 *   label = @Translation("Apple News Sections"),
 *   field_types = {
 *     "field_apple_news_sections"
 *   }
 * )
 */
class AppleNewsSections extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $section_id = isset($items[$delta]->section_id) ? $items[$delta]->section_id : '';
    $presshub = new PresshubHelper();
    if ($options = $presshub->getAppleNewsSections()) {
      $element += [
        '#type'          => 'select',
        '#title'         => $this->t('Apple News Section'),
        '#options'       => $options,
        '#default_value' => $section_id,
        '#required'      => TRUE,
      ];
    }
    else {
      $element += [
        '#plain_text' => $this->t('Please login to Presshub and enable Apple News integration.'),
      ];
    }
    return [
      'section_id' => $element
    ];
  }

}
