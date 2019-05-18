<?php

namespace Drupal\md_fontello\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "md_icon",
 *   label = @Translation("Fontello Icon"),
 *   field_types = {
 *     "md_icon"
 *   }
 * )
 */
class MDIconWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $packages = $this->getFieldSetting('packages');
    $options = [];

    foreach ($packages as $index => $package) {
      if ($package !== 0) {
        $options[] = $package;
      }
    }

    $element['value'] = $element + [
      '#type' => 'mdicon',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#packages' => $options,
    ];

    return $element;
  }
}
