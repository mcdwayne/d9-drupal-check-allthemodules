<?php

namespace Drupal\flexible_layout\Plugin\Layout;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides a layout plugin with dynamic theme regions.
 */
class FlexibleLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * The flexible_layout.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $flexibleLayoutSettings;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->flexibleLayoutSettings = \Drupal::config('flexible_layout.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'layout' => [
        'flexible_layout' => [
          'name' => 'Wrapper',
          'type' => 'column',
          'classes' => '',
          'children' => [
            'row_1' => [
              'name' => 'Row',
              'type' => 'row',
              'classes' => '',
              'children' => [
                'column_1' => [
                  'name' => 'Content',
                  'type' => 'column',
                  'classes' => '',
                  'children' => [],
                  'wrap' => [],
                ],
              ],
              'wrap' => [],
            ],
          ],
          'wrap' => [
            'enabled' => 0,
            'wrapper' => '',
            'container' => '',
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $bootstrap = $this->flexibleLayoutSettings->get('bootstrap');
    $cssGrid = $this->flexibleLayoutSettings->get('css_grid');

    $form['#attached']['library'][] = 'flexible_layout/form';

    // Main wrapper.
    $form['config'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'fl-config',
        ],
      ],
    ];

    // Preset configuration.
    $presets = $this->flexibleLayoutSettings->get('presets');
    if (!empty($presets)) {
      $preset_options = ['' => $this->t('-- None --')];
      foreach ($presets as $key => $preset) {
        $preset_options[$key] = $preset['name'];
      }
      $form['config']['preset_select'] = [
        '#title' => t('Preset Layout'),
        '#type' => 'select',
        '#options' => $preset_options,
        '#prefix' => '<span id="preset-layout-select">',
        '#suffix' => '</span>',
      ];

      $form['config']['preset_load'] = [
        '#type' => 'button',
        '#value' => $this->t('Load'),
        '#ajax' => [
          'callback' => [$this, 'loadPresetLayout'],
          'progress' => [
            // Cause different is cool.
            'type' => 'bar',
            'message' => t('Loading Preset...'),
          ],
        ],
        '#attributes' => ['class' => ['button--primary']],
      ];

      $form['config']['preset_delete'] = [
        '#type' => 'button',
        '#value' => $this->t('Delete'),
        '#ajax' => [
          'callback' => [$this, 'deletePresetLayout'],
          'wrapper' => 'preset-layout-select',
        ],
        '#attributes' => ['class' => ['remove']],
      ];
    }

    // Row configuration.
    $form['config']['row'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'fl-type-container',
          'fl-row-container',
        ],
      ],
    ];

    $form['config']['row']['label'] = [
      '#title' => t('Row Label'),
      '#type' => 'textfield',
      '#size' => 20,
      '#attributes' => ['class' => ['fl-label', 'fl-row-label']],
    ];

    $form['config']['row']['remove_row'] = [
      '#type' => 'button',
      '#value' => $this->t('Remove Row'),
      '#attributes' => ['class' => ['remove']],
    ];

    $form['config']['row']['show_advanced'] = [
      '#type' => 'button',
      '#value' => $this->t('Advanced Options'),
      '#attributes' => ['class' => ['fl-row-show-advanced']],
    ];

    $form['config']['row']['advanced'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'fl-advanced-container',
          'fl-row-advanced-container',
        ],
      ],
    ];

    $form['config']['row']['advanced']['classes'] = [
      '#title' => t('Classes'),
      '#type' => 'textfield',
      '#attributes' => ['class' => ['fl-row-classes']],
    ];

    $form['config']['row']['advanced']['wrapper'] = [
      '#title' => t('Add Wrapping Divs'),
      '#type' => 'checkbox',
      '#attributes' => ['class' => ['fl-row-add-wrapper']],
    ];

    $form['config']['row']['advanced']['wrapper-container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['fl-row-wrapper-container']],
    ];

    $form['config']['row']['advanced']['wrapper-container']['wrapper_classes'] = [
      '#title' => t('Wrapper Classes'),
      '#type' => 'textfield',
      '#attributes' => ['class' => ['fl-row-wrapper-classes']],
    ];

    $form['config']['row']['advanced']['wrapper-container']['container_classes'] = [
      '#title' => t('Container Classes'),
      '#type' => 'textfield',
      '#attributes' => ['class' => ['fl-row-container-classes']],
    ];

    // Add new column options.
    $form['config']['row']['new'] = [
      '#title' => $this->t('Add New Column(s)'),
      '#type' => 'details',
      '#open' => FALSE,
    ];

    if ($bootstrap['enabled']) {
      $form['config']['row']['new']['style'] = [
        '#title' => $this->t('Bootstrap Style'),
        '#type' => 'select',
        '#options' => [
          '' => $this->t('-- None --'),
          'col-md-3' => $this->t('25%'),
          'col-md-4' => $this->t('33%'),
          'col-md-6' => $this->t('50%'),
          'col-md-8' => $this->t('66%'),
          'col-md-9' => $this->t('75%'),
          'col-md-12' => $this->t('100%'),
        ],
        '#prefix' => '<div class="add fl-column-style">',
        '#suffix' => '</div>',
      ];
    }

    $form['config']['row']['new']['add_column'] = [
      '#type' => 'button',
      '#value' => $this->t('Add Column'),
      '#attributes' => ['class' => ['add', 'fl-add-column', 'button--primary']],
    ];

    // Column configuration.
    $form['config']['column'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'fl-type-container',
          'fl-column-container',
        ],
      ],
    ];

    $form['config']['column']['label'] = [
      '#title' => t('Column Label'),
      '#type' => 'textfield',
      '#size' => 20,
      '#attributes' => ['class' => ['fl-label', 'fl-column-label']],
    ];

    if ($bootstrap['enabled']) {
      $form['config']['column']['style'] = [
        '#title' => $this->t('Bootstrap Style'),
        '#type' => 'select',
        '#options' => [
          '' => $this->t('-- None --'),
          'col-md-3' => $this->t('25%'),
          'col-md-4' => $this->t('33%'),
          'col-md-6' => $this->t('50%'),
          'col-md-8' => $this->t('66%'),
          'col-md-9' => $this->t('75%'),
          'col-md-12' => $this->t('100%'),
        ],
        '#prefix' => '<div class="add fl-column-style">',
        '#suffix' => '</div>',
      ];
    }

    $form['config']['column']['remove_column'] = [
      '#type' => 'button',
      '#value' => $this->t('Remove Column'),
      '#attributes' => ['class' => ['remove']],
    ];

    $form['config']['column']['show_advanced'] = [
      '#type' => 'button',
      '#value' => $this->t('Advanced Options'),
      '#attributes' => ['class' => ['fl-column-show-advanced']],
    ];

    $form['config']['column']['advanced'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'fl-advanced-container',
          'fl-column-advanced-container',
        ],
      ],
    ];

    $form['config']['column']['advanced']['classes'] = [
      '#title' => t('Classes'),
      '#type' => 'textfield',
      '#attributes' => ['class' => ['fl-column-classes']],
    ];

    $form['config']['column']['new'] = [
      '#title' => t('Add New Row(s)'),
      '#type' => 'details',
      '#open' => FALSE,
    ];

    if ($bootstrap['enabled']) {
      // @TODO: would be cool if this was easily pluggable/extendible.
      $form['config']['column']['new']['template'] = [
        '#title' => $this->t('Bootstrap Template'),
        '#type' => 'select',
        '#options' => [
          '' => $this->t('-- None --'),
          'Container' => [
            'container|col-md-3|col-md-9' => $this->t('25% / 75%'),
            'container|col-md-4|col-md-8' => $this->t('33% / 66%'),
            'container|col-md-4|col-md-4|col-md-4' => $this->t('33% / 33% / 33%'),
            'container|col-md-8|col-md-4' => $this->t('66% / 33%'),
            'container|col-md-9|col-md-3' => $this->t('75% / 25%'),
          ],
          'Full Width' => [
            'col-md-3|col-md-9' => $this->t('25% / 75%'),
            'col-md-4|col-md-8' => $this->t('33% / 66%'),
            'col-md-4|col-md-4|col-md-4' => $this->t('33% / 33% / 33%'),
            'col-md-8|col-md-4' => $this->t('66% / 33%'),
            'col-md-9|col-md-3' => $this->t('75% / 25%'),
          ],
        ],
        '#prefix' => '<div class="add row-bs-template">',
        '#suffix' => '</div>',
      ];
    }

    if ($cssGrid['enabled']) {
      // @TODO: would be cool if this was easily pluggable/extendible.
      $form['config']['column']['new']['css_template'] = [
        '#title' => $this->t('CSS Grid Template'),
        '#type' => 'select',
        '#options' => [
          '' => $this->t('-- None --'),
          'gtc-25-75|2' => $this->t('25% / 75%'),
          'gtc-33-66|2' => $this->t('33% / 66%'),
          'gtc-33-33-33|3' => $this->t('33% / 33% / 33%'),
          'gtc-66-33|2' => $this->t('66% / 33%'),
          'gtc-75-25|2' => $this->t('75% / 25%'),
        ],
        '#prefix' => '<div class="add row-css-template">',
        '#suffix' => '</div>',
      ];
    }

    $form['config']['column']['new']['add_row'] = [
      '#type' => 'button',
      '#value' => $this->t('Add Row'),
      '#attributes' => ['class' => ['add', 'fl-add-row', 'button--primary']],
    ];

    // Display Output.
    $form['config']['container']['#markup'] = '<div class="flexible-layout-container"></div>';
    $config = $this->getConfiguration();
    $layout = $config['layout'];

    $form['config']['preset'] = [
      '#title' => t('Save as Preset'),
      '#type' => 'checkbox',
    ];

    $form['config']['preset_name'] = [
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#size' => 15,
    ];

    // @TODO: Figure out some better storage here.
    $form['config']['layout'] = [
      '#type' => 'textfield',
      '#default_value' => Json::encode($layout),
      '#maxlength' => 1000000000,
      '#attributes' => [
        'class' => [
          'flexible-layout-json-field',
          'visually-hidden',
        ],
      ],
    ];

    // Adds in the Bootstrap source if enabled.
    if ($bootstrap['enabled']) {
      $form['#attached']['library'][] = 'flexible_layout/bootstrap';
    }

    // Adds CSS Grid stylesheet if enabled.
    if ($cssGrid['enabled']) {
      $form['#attached']['library'][] = 'flexible_layout/css_grid';
    }

    return $form;
  }

  /**
   * AJAX Callback to load a preset layout.
   */
  public function loadPresetLayout(array $form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    // Get the button that triggered the Ajax.
    $triggering_element = $form_state->getTriggeringElement();

    // Retrieve the unknown parents due to subform.
    $parents = array_slice($triggering_element['#array_parents'], 0, -2);

    $value_parents = array_merge($parents, ['config', 'preset_select']);
    $preset = $form_state->getValue($value_parents);
    $available_presets = $this->flexibleLayoutSettings->get('presets');

    // Check that layout is valid and load into current JS.
    if (isset($available_presets[$preset]['layout'])) {
      $ajax_response->addCommand(new InvokeCommand('.fl-config', 'flexibleLayout', [$available_presets[$preset]['layout']]));
    }

    return $ajax_response;
  }

  /**
   * AJAX callback to remove a preset from the list.
   */
  public function deletePresetLayout(array $form, FormStateInterface $form_state) {
    // Get the button that triggered the Ajax.
    $triggering_element = $form_state->getTriggeringElement();

    // Retrieve the unknown parents due to subform.
    $parents = array_slice($triggering_element['#array_parents'], 0, -2);

    $value_parents = array_merge($parents, ['config', 'preset_select']);
    $preset = $form_state->getValue($value_parents);

    // Get our config root.
    $nested = NestedArray::getValue($form, $parents);

    // Ensure we have a preset at this key.
    $available_presets = $this->flexibleLayoutSettings->get('presets');
    if (isset($available_presets[$preset]['layout'])) {
      unset($nested['config']['preset_select']['#options'][$preset]);

      // Save to main config.
      unset($available_presets[$preset]);
      $this->flexibleLayoutSettings = \Drupal::configFactory()
        ->getEditable('flexible_layout.settings')
        ->set('presets', $available_presets)
        ->save();
    }

    return $nested['config']['preset_select'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('config');

    // Quick check that at least one region exists.
    $layout = JSON::decode($values['layout']);
    $regions = $this->getRegionsFromLayout($layout);
    if (empty($regions)) {
      $form_state->setErrorByName('config', $this->t('You need at least one content column.'));
      // @todo delete after https://www.drupal.org/project/panels/issues/2930835.
      drupal_set_message($this->t("You need at least one content column."), 'error');
    }

    // Presets require a name.
    if (!empty($values['preset'])) {

      if (empty($values['preset_name'])) {
        $form_state->setErrorByName('config][preset_name]', $this->t('A Name is required to save a new preset.'));
        // @todo delete after https://www.drupal.org/project/panels/issues/2930835.
        drupal_set_message($this->t("A Name is required to save a new preset."), 'error');
      }
      else {
        // Make sure the name isn't already in use.
        $presets = $this->flexibleLayoutSettings->get('presets');
        foreach ($presets as $preset) {
          if ($preset['name'] == trim($values['preset_name'])) {
            $form_state->setErrorByName('config][preset_name]', $this->t('This preset name already exists.'));
            // @todo delete after https://www.drupal.org/project/panels/issues/2930835.
            drupal_set_message($this->t("This preset name already exists."), 'error');
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('config');
    $this->configuration['layout'] = JSON::decode($values['layout']);

    if (!empty($values['preset'])) {
      $presets = $this->flexibleLayoutSettings->get('presets');
      $presets[] = [
        'name' => trim($values['preset_name']),
        'layout' => $values['layout'],
      ];
      // Save to main config so can be used in other displays.
      \Drupal::configFactory()
        ->getEditable('flexible_layout.settings')
        ->set('presets', $presets)
        ->save();
    }
  }

  /**
   * Loads regions from custom layout.
   *
   * @param array $current
   *   The current Layout settings.
   *
   * @return array
   *   Renderable regions for layout discover.
   */
  protected function getRegionsFromLayout(array $current) {
    $regions = [];

    foreach ($current as $machine_name => $item) {
      // Rows and main wrapper are skipped, only Columns are considered regions.
      if ($item['type'] != 'row' && $machine_name != 'flexible_layout') {
        $regions[$machine_name] = [
          'label' => $item['name'],
        ];
      }

      if (!empty($item['children'])) {
        $regions = array_merge($regions, $this->getRegionsFromLayout($item['children']));
      }
    }

    return $regions;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionDefinitions() {
    $regions = $this->getRegionsFromLayout($this->configuration['layout']);
    return $regions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $definition = $this->pluginDefinition;
    $definition->setRegions($this->getRegionDefinitions());
    return $definition;
  }

}
