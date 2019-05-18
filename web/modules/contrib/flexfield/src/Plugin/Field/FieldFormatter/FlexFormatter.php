<?php

namespace Drupal\flexfield\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flexfield\Plugin\FlexFieldTypeManager;
use Drupal\flexfield\Plugin\FlexFieldTypeManagerInterface;
use Drupal\flexfield\Plugin\Field\FieldFormatter\FlexFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'flex_formatter' formatter.
 *
 * Generic formatter, renders the items using the flexfield theme hook
 * implementation.
 *
 * @FieldFormatter(
 *   id = "flex_formatter",
 *   label = @Translation("Flexfield"),
 *   weight = 0,
 *   field_types = {
 *     "flex"
 *   }
 * )
 */
class FlexFormatter extends FlexFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'label_display' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form = parent::settingsForm($form, $form_state);

    $form['#attached']['library'][] = 'flexfield/flexfield-inline';
    $form['label_display'] = [
      '#type' => 'container',
      // '#title' => t('Flexfield Item Label Display'),
      '#attributes' => [
        'class' => ['flexfield-inline']
      ],
    ];

    $label_display = $this->getSetting('label_display');
    foreach ($this->getFlexFieldItems() as $name => $flexitem) {
      $form['label_display'][$name] = [
        '#type' => 'select',
        '#title' => t('@label label', ['@label' => $flexitem->getLabel()]),
        '#options' => $this->fieldLabelOptions(),
        '#default_value' => isset($label_display[$name]) ? $label_display[$name] : 'above',
      ];
      $form['label_display'][$name]['#attributes']['class'][] = 'flexfield-inline__field';
      $form['label_display'][$name]['#wrapper_attributes']['class'][] = 'flexfield-inline__item';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $label_display = $this->getSetting('label_display');
    foreach ($this->getFlexFieldItems() as $name => $flexitem) {
      $summary[] = t('@label label display: @label_display', [
        '@label' => $flexitem->getLabel(),
        '@label_display' => isset($label_display[$name]) ? $this->fieldLabelOptions($label_display[$name]) : 'above',
      ]);
    }

    return $summary;
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

    $output = [
      '#theme' => [
        'flexfield',
        'flexfield__' . $this->fieldDefinition->get('field_name'),
      ],
      '#field_name' => $this->fieldDefinition->get('field_name'),
      '#items' => [],
    ];
    $label_display = $this->getSetting('label_display');

    foreach ($this->getFlexFieldItems() as $name => $flexitem) {
      $output['#items'][] = [
        'name' => $name,
        'value' => $flexitem->value($item),
        'label' => $flexitem->getLabel(),
        'label_display' => isset($label_display[$name]) ? $label_display[$name] : 'above',
      ];
    }
    return $output;
  }

  /**
   * Returns an array of visibility options for flexfield labels.
   *
   * Copied from Drupal\field_ui\Form\EntityViewDisplayEditForm (can't call
   * directly since it's protected)
   *
   * @return array
   *   An array of visibility options.
   */
  protected function fieldLabelOptions($option = NULL) {
    $options = [
      'above' => $this->t('Above'),
      'inline' => $this->t('Inline'),
      'hidden' => '- ' . $this->t('Hidden') . ' -',
      'visually_hidden' => '- ' . $this->t('Visually Hidden') . ' -',
    ];
    if (!is_null($option)) {
      return isset($options[$option]) ? $options[$option] : '';
    }
    return $options;
  }

}
