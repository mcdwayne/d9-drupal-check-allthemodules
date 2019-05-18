<?php

namespace Drupal\flexiform\FormComponent;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\flexiform\FlexiformEntityFormDisplay;
use Drupal\flexiform\FormEntity\FlexiformFormEntityManager;

/**
 * Base class for form component types.
 */
class FormComponentTypeBase extends PluginBase implements FormComponentTypeInterface {

  /**
   * The form entity manager.
   *
   * @var \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   */
  protected $formEntityManager = NULL;

  /**
   * The form display.
   *
   * @var \Drupal\flexiform\FlexiformEntityFormDisplay
   */
  protected $formDisplay = NULL;

  /**
   * A list of components that have been constructed.
   *
   * @var \Drupal\flexiform\FormComponent\FormComponentInterface[]
   */
  protected $components = [];

  /**
   * {@inheritdoc}
   */
  public function getComponent($name, array $options = []) {
    $class = $this->getPluginDefinition()['component_class'];
    if (!class_exists($class)) {
      throw new \Exception("No Component class for Form Component Type " . $this->getPluginId());
    }

    if (empty($options)) {
      $options = [];
    }

    if (is_subclass_of($class, 'Drupal\\flexiform\\FormComponent\\ContainerFactoryFormComponentInterface')) {
      return $class::create(\Drupal::getContainer(), $name, $options, $this->getFormDisplay());
    }

    return new $class($name, $options, $this->getFormDisplay());
  }

  /**
   * {@inheritdoc}
   */
  public function getFormEntityManager() {
    return $this->formEntityManager;
  }

  /**
   * @param FlexiformFormEntityManager $manager
   */
  public function setFormEntityManager(FlexiformFormEntityManager $manager) {
    $this->formEntityManager = $manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormDisplay(FlexiformEntityFormDisplay $form_display) {
    $this->formDisplay = $form_display;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplay() {
    return $this->formDisplay;
  }

  /**
   * {@inheritdoc}
   */
  public function componentRows(EntityDisplayFormBase $form_object, array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitComponentRow($component_name, $values, array $form, FormStateInterface $form_state) {
    $options = $this->getFormDisplay()->getComponent($component_name);

    // Update settings only if the submid handler told us to.
    if ($form_state->get('plugin_settings_update') == $component_name) {
      $options = $this->getComponent($component_name, $options)->settingsFormSubmit($values['settings_edit_form'], $form, $form_state) + $options;
      $form_state->set('plugin_settings_update', NULL);
    }
    $options['type'] = $values['type'];
    $options['weight'] = $values['weight'];
    $options['region'] = $values['region'];

    return $options;
  }

  /**
   * Get applicable renderer plugin options.
   *
   * By default return array with 'default' as the only key.
   *
   * @param string $component_name
   *   The component name.
   *
   * @return array
   *   The options.
   */
  protected function getApplicableRendererPluginOptions($component_name) {
    return ['default' => t('Default')];
  }

  /**
   * Get the default renderer plugin.
   *
   * @param string $component_name
   *   The component name.
   *
   * @return string
   *   The default region.
   */
  protected function getDefaultRendererPlugin($component_name) {
    return 'default';
  }

  /**
   * Build a component row for a component of this type.
   *
   * @param \Drupal\field_ui\Form\EntityDisplayFormBase $form_object
   *   The form object building the configuration form.
   * @param string $component_name
   *   The component name.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An form array representing the row for the given component.
   */
  protected function buildComponentRow(EntityDisplayFormBase $form_object, $component_name, array $form, FormStateInterface $form_state) {
    $form_display = $this->getFormDisplay();
    $display_options = $form_display->getComponent($component_name);

    $component = $this
      ->getComponent($component_name, $display_options ?: [])
      ->setFormEntityManager($this->getFormEntityManager());
    $label = $component->getAdminLabel();

    $regions = array_keys($form_object->getRegions());
    $row = [
      '#attributes' => ['class' => ['draggable', 'tabledrag-leaf']],
      '#row_type' => 'field',
      '#region_callback' => [$form_object, 'getRowRegion'],
      '#js_settings' => [
        'rowHandler' => 'field',
        'defaultPlugin' => $this->getDefaultRendererPlugin($component_name),
      ],
      'human_name' => [
        '#plain_text' => $label,
      ],
      'weight' => [
        '#type' => 'textfield',
        '#title' => t('Weight for @title', ['@title' => $label]),
        '#title_display' => 'invisible',
        '#default_value' => $display_options ? $display_options['weight'] : '0',
        '#size' => 3,
        '#attributes' => ['class' => ['field-weight']],
      ],
      'parent_wrapper' => [
        'parent' => [
          '#type' => 'select',
          '#title' => t('Label display for @title', ['@title' => $label]),
          '#title_display' => 'invisible',
          '#options' => array_combine($regions, $regions),
          '#empty_value' => '',
          '#attributes' => ['class' => ['js-field-parent', 'field-parent']],
          '#parents' => ['fields', $component_name, 'parent'],
        ],
        'hidden_name' => [
          '#type' => 'hidden',
          '#default_value' => $component_name,
          '#attributes' => ['class' => ['field-name']],
        ],
      ],
      'region' => [
        '#type' => 'select',
        '#title' => t('Region for @title', ['@title' => $label]),
        '#title_display' => 'invisible',
        '#options' => $form_object->getRegionOptions(),
        '#default_value' => $display_options ? $display_options['region'] : 'hidden',
        '#attributes' => [
          'class' => [
            'field-region',
          ],
        ],
      ],
    ];

    $row['plugin'] = [
      'type' => [
        '#type' => 'select',
        '#title' => t('Plugin for @title', ['@title' => $label]),
        '#title_display' => 'invisible',
        '#options' => $this->getApplicableRendererPluginOptions($component_name),
        '#default_value' => $display_options ? (!empty($display_options['type']) ? $display_options['type'] : $this->getDefaultRendererPlugin($component_name)) : 'hidden',
        '#parents' => ['fields', $component_name, 'type'],
        '#attributes' => ['class' => ['field-plugin-type']],
      ],
      'settings_edit_form' => [],
    ];

    // Base button element for the various plugin settings actions.
    $base_button = [
      '#submit' => ['::multistepSubmit'],
      '#ajax' => [
        'callback' => '::multistepAjax',
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ],
      '#field_name' => $component_name,
    ];

    $settings_form_base = [
      '#parents' => ['fields', $component_name, 'settings_edit_form'],
    ];
    if ($form_state->get('plugin_settings_edit') == $component_name) {
      // We are currently editing this field's plugin settings. Display the
      // settings form and submit buttons.
      $row['plugin']['settings_edit_form'] = [];

      // Generate the settings form and allow other modules to alter it.
      if ($settings_form = $component->settingsForm($settings_form_base, $form_state)) {
        $row['plugin']['#cell_attributes'] = ['colspan' => 3];
        $row['plugin']['settings_edit_form'] = $settings_form + [
          '#type' => 'container',
          '#attributes' => ['class' => ['field-plugin-settings-edit-form']],
          '#parents' => ['fields', $component_name, 'settings_edit_form'],
          'label' => [
            '#markup' => t('Plugin settings'),
          ],
          'actions' => [
            '#type' => 'actions',
            'save_settings' => $base_button + [
              '#type' => 'submit',
              '#button_type' => 'primary',
              '#name' => $component_name . '_plugin_settings_update',
              '#value' => t('Update'),
              '#op' => 'update',
            ],
            'cancel_settings' => $base_button + [
              '#type' => 'submit',
              '#name' => $component_name . '_plugin_settings_cancel',
              '#value' => t('Cancel'),
              '#op' => 'cancel',
              // Do not check errors for the 'Cancel' button, but make sure we
              // get the value of the 'plugin type' select.
              '#limit_validation_errors' => [
                ['fields', $component_name, 'type'],
              ],
            ],
          ],
        ];
        $row['#attributes']['class'][] = 'field-plugin-settings-editing';
      }
    }
    else {
      $row['settings_summary'] = [];
      $row['settings_edit'] = [];

      // Display a summary of the current plugin settings, and (if the
      // summary is not empty) a button to edit them.
      $summary = $component->settingsSummary();

      if (!empty($summary)) {
        $row['settings_summary'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="field-plugin-summary">{{ summary|safe_join("<br />") }}</div>',
          '#context' => ['summary' => $summary],
          '#cell_attributes' => ['class' => ['field-plugin-summary-cell']],
        ];
      }

      // Check selected plugin settings to display edit link or not.
      $settings_form = $component->settingsForm($settings_form_base, $form_state);
      if (!empty($settings_form)) {
        $row['settings_edit'] = $base_button + [
          '#type' => 'image_button',
          '#name' => $component_name . '_settings_edit',
          '#src' => 'core/misc/icons/787878/cog.svg',
          '#attributes' => ['class' => ['field-plugin-settings-edit'], 'alt' => t('Edit')],
          '#op' => 'edit',
          // Do not check errors for the 'Edit' button, but make sure we get
          // the value of the 'plugin type' select.
          '#limit_validation_errors' => [['fields', $component_name, 'type']],
          '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
          '#suffix' => '</div>',
        ];
      }
    }

    return $row;
  }

}
