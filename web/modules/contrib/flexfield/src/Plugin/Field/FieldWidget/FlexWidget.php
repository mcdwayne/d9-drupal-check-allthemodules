<?php

namespace Drupal\flexfield\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flexfield\Plugin\FlexFieldTypeManager;
use Drupal\flexfield\Plugin\FlexFieldTypeManagerInterface;
use Drupal\flexfield\Plugin\Field\FieldWidget\FlexWidgetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'flex_default' widget.
 *
 * @FieldWidget(
 *   id = "flex_default",
 *   label = @Translation("Flexfield"),
 *   weight = 0,
 *   field_types = {
 *     "flex"
 *   }
 * )
 */
class FlexWidget extends FlexWidgetBase {

  protected $flexFieldManager = null;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'customize' => FALSE,
      'breakpoint' => '',
      'proportions' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements = parent::settingsForm($form, $form_state);
    $elements['#tree'] = TRUE;
    $elements['#attached']['library'][] = 'flexfield/flexfield-inline';
    $elements['#attached']['library'][] = 'flexfield/flexfield-inline-admin';

    $id = Html::getUniqueId('flexfield-inline-customize');
    $elements['customize'] = [
      '#type' => 'checkbox',
      '#title' => t('Customize Flexfield item proportions'),
      '#description' => t('By default the items will automatically resize to the most optimal size based on their content. Check this box to give specific proportions to the field items.'),
      '#default_value' => $this->getSetting('customize'),
      '#attributes' => [
        'data-id' => $id,
      ],
    ];

    $elements['proportions'] = [
      '#type' => 'fieldset',
      '#title' => t('Proportions'),
      '#description' => t('The size of the item relative to the other items. Example: If you had three items and gave respective proportions of 1/1/2, the resulting fields would be 25%/25%/50%. The above drop downs will resize as you change the values to reflect how the items will be output.'),
      '#states' => array(
         'visible' => array(
           ':input[data-id="' . $id . '"]' => ['checked' => TRUE],
         ),
       ),
    ];

    $elements['proportions']['prefix'] = [
      '#markup' => '<div class="flexfield-inline flexfield-inline--widget-settings">',
    ];

    $proportions = $this->getSettings()['proportions'];
    foreach ($this->getFlexFieldItems() as $name => $item) {
      $elements['proportions'][$name] = [
        '#type' => 'select',
        '#title' => $item->getLabel(),
        '#options' => $this->proportionOptions(),
        '#wrapper_attributes' => [
          'class' => ['flexfield-inline__item']
        ],
        '#attributes' => [
          'class' => ['flexfield-inline__field']
        ],
      ];
      if (isset($proportions[$name])) {
        $elements['proportions'][$name]['#default_value'] = $proportions[$name];
        $elements['proportions'][$name]['#wrapper_attributes']['class'][] = 'flexfield-inline__item--' . $proportions[$name];
      }
    }

    $elements['proportions']['suffix'] = [
      '#markup' => '</div>',
    ];

    $elements['breakpoint'] = [
      '#type' => 'select',
      '#title' => t('Stack items on:'),
      '#description' => t('The device width below which we stack the inline flexfield items.'),
      '#options' => $this->breakpointOptions(),
      '#default_value' => $this->getSetting('breakpoint'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $proportions = 'Automatic';
    if (!empty($this->getSetting('customize')) && !empty($this->getSettings()['proportions'])) {
      $proportions = implode(' | ', $this->getSettings()['proportions']);
    }
    $summary[] = t('Inline Flexfield items.');
    $summary[] = t('Item Proportions: @proportions', ['@proportions' => $proportions]);
    $summary[] = t('Stack on: @breakpoint', ['@breakpoint' => $this->breakpointOptions($this->getSetting('breakpoint'))]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#attached']['library'][] = 'flexfield/flexfield-inline';
    $classes = ['flexfield-inline'];
    if ($this->getSetting('breakpoint')) {
      $classes[] = 'flexfield-inline--stack-' . $this->getSetting('breakpoint');
    }
    // Using markup since we can't nest values because the field api expects
    // subfields to be at the top-level
    $element['wrapper_prefix']['#markup'] = '<div class="' . implode(' ', $classes) . '">';

    $proportions = $this->getSettings()['proportions'];
    foreach ($this->getFlexFieldItems() as $name => $item) {
      $element[$name] = $item->widget($items, $delta, $element, $form, $form_state);
      $element[$name]['#attributes']['class'][] = 'flexfield-inline__field';
      $element[$name]['#wrapper_attributes']['class'][] = 'flexfield-inline__item';
      if ($this->getSetting('customize') && isset($proportions[$name])) {
        $element[$name]['#wrapper_attributes']['class'][] = 'flexfield-inline__item--' . $proportions[$name];
      }
    }

    $element['wrapper_suffix']['#markup'] = '</div>';

    return $element;
  }

  /**
   * Get the field storage definition.
   */
  public function getFieldStorageDefinition() {
    return $this->fieldDefinition->getFieldStorageDefinition();
  }

  /**
   * The options for proportions.
   */
  public function proportionOptions($option = NULL) {
    $options = [
      'one' => t('One'),
      'two' => t('Two'),
      'three' => t('Three'),
      'four' => t('Four'),
    ];
    if (!is_null($option)) {
      return isset($options[$option]) ? $options[$option] : '';
    }
    return $options;
  }

  /**
   * The options for proportions.
   */
  public function breakpointOptions($option = NULL) {
    $options = [
      '' => t('Don\'t stack'),
      'medium' => t('Medium (less than 769px)'),
      'small' => t('Small (less than 601px)'),
    ];
    if (!is_null($option)) {
      return isset($options[$option]) ? $options[$option] : '';
    }
    return $options;
  }

}
