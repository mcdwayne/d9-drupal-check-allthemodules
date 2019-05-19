<?php

namespace Drupal\stacks\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Url;
use Drupal\stacks\Entity\WidgetEntityType;
use Drupal\stacks\Entity\WidgetInstanceEntity;
use Drupal\stacks\Widget\WidgetData;

/**
 * Plugin implementation of the 'form_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "form_widget_type",
 *   label = @Translation("Form Stacks"),
 *   field_types = {
 *     "stacks_type"
 *   }
 * )
 */
class FormWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'bundles' => [],
      'bundles_required_pos_locked' => [],
      'bundles_required_pos_optional' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $widget_type_manager = \Drupal::service('plugin.manager.stacks_widget_type');
    $widget_entity_types = WidgetEntityType::loadMultiple();
    $bundles = [];
    foreach ($widget_entity_types as $widget_entity_type) {
      if ($widget_type_manager->hasDefinition($widget_entity_type->getPlugin())) {
        $bundles[$widget_entity_type->id()] = $widget_entity_type->label();
      }
    }

    $elements['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => t('Enabled Widget Types'),
      '#options' => $bundles,
      '#default_value' => $this->getSetting('bundles'),
      '#required' => TRUE,
      '#description' => t('The widgets that are available for this field. Note that all widget bundles are automatically added to all widget fields.'),
    ];

    $elements['bundles_required_pos_locked'] = [
      '#type' => 'checkboxes',
      '#title' => t('Required Widget Bundles: Position Locked'),
      '#options' => $bundles,
      '#default_value' => $this->getSetting('bundles_required_pos_locked'),
      '#required' => FALSE,
      '#description' => t('The widgets that are auto added to this field, where the position cannot be changed per node (displayed at the top).'),
    ];

    $elements['bundles_required_pos_optional'] = [
      '#type' => 'checkboxes',
      '#title' => t('Required Widget Bundles: Position Optional'),
      '#options' => $bundles,
      '#default_value' => $this->getSetting('bundles_required_pos_optional'),
      '#required' => FALSE,
      '#description' => t('The widgets that are auto added to this field, where the position can be changed per node.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $enable_stacks = $this->displayBundlesAsList($this->getSetting('bundles'), t('Enabled Stacks'));
    if (!empty($enable_stacks)) {
      $summary[] = $enable_stacks;
    }

    $required_stacks = $this->displayBundlesAsList($this->getSetting('bundles_required_pos_locked'), t('Required Stacks - Position Locked'));
    if (!empty($required_stacks)) {
      $summary[] = $required_stacks;
    }

    $required_stacks_pos_optional = $this->displayBundlesAsList($this->getSetting('bundles_required_pos_optional'), t('Required Stacks - Position Optional'));
    if (!empty($required_stacks_pos_optional)) {
      $summary[] = $required_stacks_pos_optional;
    }

    return $summary;
  }

  /**
   * Takes an array of bundles and returns then in a string, separate by a comma.
   *
   * @param array $bundles
   * @param string $label Label for what to label this list as.
   *
   * @return string
   */
  public function displayBundlesAsList($bundles, $label) {
    if (empty($bundles)) {
      return '';
    }

    $bundles_array = [];
    foreach ($bundles as $value) {
      if (!empty($value)) {
        $bundles_array[] = $value;
      }
    }

    $bundles_html = implode(', ', $bundles_array);
    $return = '';
    if (!empty($bundles_html)) {
      $return = t('@label: @bundles', [
        '@label' => $label,
        '@bundles' => $bundles_html,
      ]);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [];

    // Attach the js/css.
    $element['#attached']['library'][] = 'stacks/admin';

    // Send to a custom method to add the correct buttons, display title, etc...
    $this->widgetRow($items, $delta, $element, $form, $form_state);

    return $element;
  }

  /**
   * Outputs the html for a widget row.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   * @param $delta
   * @param array $element
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $required_options
   */
  public function widgetRow(FieldItemListInterface $items, $delta, array &$element, array &$form, FormStateInterface $form_state, $required_options = []) {
    // Default options for the query parameters for the buttons.
    $field_name = $items->getFieldDefinition()->getName();
    $link_options = [
      'query' => [
        'entity-type' => $items->getEntity()->getEntityTypeId(),
        'entity-id' => $items->getEntity()->id(),
        'content-type' => $items->getEntity()->bundle(),
        'field' => $field_name,
        'delta' => $delta,
      ]
    ];

    $wrapper_id = "widget-form-{$delta}";
    $element['form_wrapper_start'] = ['#markup' => '<div id="' . $wrapper_id . '" class="widget-form">'];
    $element['data'] = [];


    // We first try to load the widget_instance_id property. If that is not set
    // we then try to grab it from the form, which happens when the add new row
    // is called. If both of those are false, we know we need to display the add
    // new row button.
    $widget_instance_id = isset($items[$delta]->widget_instance_id) ? $items[$delta]->widget_instance_id : FALSE;
    if (!$widget_instance_id) {
      $form_values = $form_state->getValue($field_name);
      $widget_instance_id = isset($form_values[$delta]['widget_instance_id']) ? $form_values[$delta]['widget_instance_id'] : FALSE;
    }

    // Is this an edit row?
    if ($widget_instance_id) {
      // Update: calling static function to build widget fields with existing data.
      $element = array_merge($element, $this->getWidgetInstanceField($wrapper_id, $widget_instance_id, $link_options));
    }
    else {
      $element['form_wrapper_start'] = ['#markup' => '<div id="' . $wrapper_id . '" class="add-widget-row widget-form">'];
      // Add new row.
      $element['data']['link'] = [
        '#type' => 'link',
        '#title' => t('Add a New Widget'),
        '#url' => Url::fromRoute('stacks.admin.ajax', [], $link_options),
        '#attributes' => [
          'class' => ['use-ajax', 'button', 'add-widget', 'button--primary'],
        ],
        '#prefix' => '<div class="add-widget-wrapper">',
        '#suffix' => '</div>',
      ];
    }

    $element['form_wrapper_end'] = ['#markup' => '</div>'];

    $element['widget_instance_id'] = $element + [
        '#type' => 'hidden',
        '#default_value' => isset($items[$delta]->widget_instance_id) ? $items[$delta]->widget_instance_id : '',
        '#placeholder' => t('Widget Instance ID'),
        '#prefix' => '<div id="widget-instance-' . $delta . '" class="widget_instance">',
        '#suffix' => '</div>',
      ];
  }

  /**
   * Static function to build existing widget field.
   */
  public static function getWidgetInstanceField($wrapper_id, $widget_instance_id, $link_options) {
    // Setting some general placeholder messages first for the rare case
    // in which a widget instance is not found
    $element['data']['stacks_begin'] = ['#markup' => '<div class="stacks">'];
    $element['data']['title'] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'widget_type_list',
          ],
        ],
        '#value' => 'Widget instance ' . $widget_instance_id . ' missing',
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#attributes' => [
          'class' => [
            'widget-title',
          ],
        ],
        '#value' => 'Widget missing',
      ],
    ];

    // Load the widget instance.
    $widget_instance = WidgetInstanceEntity::load($widget_instance_id);
    if (!$widget_instance) {
      return $element;
    }

    // Set the widget type div.
    $element['data']['title'][0]['#value'] = WidgetData::getWidgetType($widget_instance);

    // Set the widget title. If the node hasn't been saved with new widgets yet,
    // the title will not be set.
    $widget_title = $widget_instance->getTitle();
    if (empty($widget_title)) {
      $widget_title = WidgetData::getWidgetType($widget_instance) . ' (' . $widget_instance_id . ')';
    }

    $element['data']['title'][1]['#value'] = $widget_title;

    $edit_button_label = 'Edit';
    $link_options['query']['widget_instance_id'] = $widget_instance->id();

    // Is this is a required widget?
    $is_required = $widget_instance->getIsRequired();
    if ($is_required) {
      $required_type = $widget_instance->getRequiredType();
      $required_bundle = $widget_instance->getRequiredBundle();
      $bundles_get = \Drupal::entityManager()->getBundleInfo('widget_entity');

      $edit_button_label = $bundles_get[$required_bundle]['label'];
      unset($element['data']['title']);

      // Add special classes to the wrapper.
      $element['form_wrapper_start'] = ['#markup' => '<div id="' . $wrapper_id . '" class="required required_' . $required_type . ' widget-form">'];
    }

    if (!$is_required) {
      // Handle Delete Button.
      $element['data']['delete'] = [
        '#type' => 'link',
        '#title' => t('Delete'),
        '#url' => Url::fromRoute('stacks.admin.ajax_delete', [], $link_options),
        '#attributes' => [
          'class' => ['use-ajax', 'link--gray', 'remove-widget'],
          'data-dialog-type' => 'modal',
        ],
        '#prefix' => '<div class="widget-collapsed__buttons">'
      ];
    }

    // Handle Edit Button.
    $element['data']['link'] = [
      '#type' => 'link',
      '#title' => t($edit_button_label),
      '#url' => Url::fromRoute('stacks.admin.ajax', [], $link_options),
      '#attributes' => [
        'class' => ['use-ajax', 'button', 'edit-widget', 'button--primary'],
        'data-dialog-type' => 'modal',
      ],
      '#suffix' => '</div>',
    ];

    $element['data']['stacks_end'] = ['#markup' => '</div>'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $array = parent::form($items, $form, $form_state, $get_delta);
    return $array;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $element = parent::formMultipleElements($items, $form, $form_state);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $element = parent::addMoreAjax($form, $form_state);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the stacks container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Increment the items count. Only if the last value is not empty.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $prev_delta = $field_state['items_count'];
    if (!empty($element[$prev_delta]['widget_instance_id']['#value'])) {
      $field_state['items_count']++;
    }

    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
  }

}
