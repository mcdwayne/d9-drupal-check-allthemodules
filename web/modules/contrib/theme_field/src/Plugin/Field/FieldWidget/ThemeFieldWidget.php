<?php

namespace Drupal\theme_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\InfoParser;

/**
 * Plugin implementation of the 'theme_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "theme_field_widget",
 *   label = @Translation("Theme"),
 *   field_types = {
 *     "theme_field_type"
 *   }
 * )
 */
class ThemeFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $default_value = isset($items[$delta]->value) ? $items[$delta]->value : '';

    $options = ['_none' => t('Default theme')];
    $theme_handler = \Drupal::service('theme_handler');
    $themes = $theme_handler->listInfo();
    $parser = new InfoParser();
    foreach ($themes as $key => $theme) {
      $info = $parser->parse(drupal_get_path('theme', $key) . '/' . $key . '.info.yml');
      $options[$key] = $theme_handler->getName($key);
    }
    $element += [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default_value,
      '#multiple' => FALSE,
    ];

    return ['value' => $element];
  }

}
