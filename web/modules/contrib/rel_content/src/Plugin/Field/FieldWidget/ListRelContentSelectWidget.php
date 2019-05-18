<?php

namespace Drupal\rel_content\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rel_content\RelatedContentInterface;

/**
 * Plugin implementation of the 'plugin_reference_select' widget.
 *
 * @FieldWidget(
 *   id = "list_rel_content_select",
 *   label = @Translation("Select list"),
 *   field_types = {
 *     "list_rel_content"
 *   }
 * )
 */
class ListRelContentSelectWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $options = [];

    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface */
    $settings = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('list_rel_content');

    // TODO DI ??
    /* @var $manager \Drupal\rel_content\RelatedContentPluginManager */
    $manager = \Drupal::getContainer()->get('plugin.manager.rel_content');

    foreach ($settings as $plugin_machine_name) {
      /** @var \Drupal\rel_content\RelatedContentInterface $instance */
      $instance = $manager->createInstance($plugin_machine_name, ['items' => $items, 'delta' => $delta]);
      $result = $instance->getOptions();

      foreach ($result as $key => $value) {
        $options["$plugin_machine_name:$key"] = $value;
      }
    }

    $element['value'] = $element + [
      '#type' => 'radios',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#options' => $options,
    ];

    return $element;
  }

}
