<?php

namespace Drupal\pluginreference\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of the 'plugin_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "plugin_reference_autocomplete",
 *   label = @Translation("Autocomplete"),
 *   field_types = {
 *     "plugin_reference"
 *   }
 * )
 */
class PluginReferenceAutocompleteWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
        '#type' => 'plugin_autocomplete',
        '#default_value' => \Drupal::getContainer()->has('plugin.manager.' . $this->getFieldSetting('target_type')) ? \Drupal::getContainer()
          ->get('plugin.manager.' . $this->getFieldSetting('target_type'))
          ->getDefinition($items[$delta]->value) : NULL,
        '#target_type' => $this->getFieldSetting('target_type')
      ];

    return $element;
  }

}
