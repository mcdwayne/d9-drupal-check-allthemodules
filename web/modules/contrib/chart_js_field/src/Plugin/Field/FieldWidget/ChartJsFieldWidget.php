<?php

namespace Drupal\chart_js_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'chart_js_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "chart_js_field_widget",
 *   module = "chart_js_field_type",
 *   label = @Translation("Chart.js Field Widget"),
 *   field_types = {
 *     "chart_js_field_type"
 *   }
 * )
 */
class ChartJsFieldWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal LoggerFactory service container.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigFactory $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory')
    );
  }

  protected $types = [
    'line' => 'Line',
    'bar' => 'Bar',
    'radar' => 'Radar',
    'pie' => 'Pie',
    'doughnut' => 'Doughnut',
    'polarArea' => 'Polar Area',
    'bubble' => 'Bubble',
    'scatter' => 'Scatter',
  ];

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form['#prefix'] = '<div>Visit <a href="https://www.chartjs.org/docs/latest/" target="_blank">Chart.js documentation</a></div>';

    $form['types'] = [
      '#title' => $this->t('Types of Charts'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $this->types,
      '#default_value' => $this->getSetting('types'),
      '#description' => $this->t("Select the kinds of charts allowed."),
    ];

    $form['data'] = [
      '#type' => 'details',
      '#title' => $this->t('Data Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['data']['allow_labels'] = [
      '#title' => $this->t('Allow label options'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('data')['allow_labels'],
    ];
    $form['data']['allow_type'] = [
      '#title' => $this->t('Allow data type option'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('data')['allow_type'],
    ];
    $form['data']['type'] = [
      '#title' => $this->t('Default data type'),
      '#type' => 'select',
      '#options' => ['value' => 'Value', 'point' => 'Point'],
      '#default_value' => $this->getSetting('data')['type'],
    ];

    $form['data']['allow_brdw'] = [
      '#title' => $this->t('Allow border width option'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('data')['allow_brdw'],
    ];
    $form['data']['brdw'] = [
      '#title' => $this->t('Default border width'),
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#default_value' => $this->getSetting('data')['brdw'],
    ];

    $form['data']['allow_bgc'] = [
      '#title' => $this->t('Allow background color option'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('data')['allow_bgc'],
    ];
    $form['data']['bgc'] = [
      '#title' => $this->t('Default background color'),
      '#type' => 'color',
      '#default_value' => $this->getSetting('data')['bgc'],
    ];

    $form['data']['allow_bgo'] = [
      '#title' => $this->t('Allow background opacity option'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('data')['allow_bgo'],
    ];
    $form['data']['bgo'] = [
      '#title' => $this->t('Default background opacity'),
      '#type' => 'number',
      '#min' => 0.00,
      '#max' => 1.00,
      '#step' => 0.01,
      '#default_value' => $this->getSetting('data')['bgo'],
    ];

    $form['data']['allow_brdc'] = [
      '#title' => $this->t('Allow border color option'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('data')['allow_brdc'],
    ];
    $form['data']['brdc'] = [
      '#title' => $this->t('Default border color'),
      '#type' => 'color',
      '#default_value' => $this->getSetting('data')['brdc'],
    ];

    $form['data']['allow_brdo'] = [
      '#title' => $this->t('Allow border opacity option'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('data')['allow_brdo'],
    ];
    $form['data']['brdo'] = [
      '#title' => $this->t('Default border opacity'),
      '#type' => 'number',
      '#min' => 0.00,
      '#max' => 1.00,
      '#step' => 0.01,
      '#default_value' => $this->getSetting('data')['brdo'],
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Options Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['options']['display_title'] = [
      '#title' => $this->t('Default value for displaying title'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['display_title'],
    ];
    $form['options']['allow_display_title'] = [
      '#title' => $this->t('Allowing option for displaying title'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_display_title'],
    ];

    $form['options']['allow_x_axis_display'] = [
      '#title' => $this->t('Allow label display option for x axis'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_x_axis_display'],
    ];
    $form['options']['x_axis_display'] = [
      '#title' => $this->t('Default value for x axis label display'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['x_axis_display'],
    ];
    $form['options']['allow_x_axis_label'] = [
      '#title' => $this->t('Allow label option for x axis'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_x_axis_label'],
    ];
    $form['options']['x_axis_label'] = [
      '#title' => $this->t('Default value for x axis label'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('options')['x_axis_label'],
    ];
    $form['options']['allow_zero_x'] = [
      '#title' => $this->t('Allow start at zero option for x axis'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_zero_x'],
    ];
    $form['options']['default_zero_x'] = [
      '#title' => $this->t('Default value for start at zero for x axis'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['default_zero_x'],
    ];
    $form['options']['allow_x_grid'] = [
      '#title' => $this->t('Allow grid option for x axis'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_x_grid'],
    ];
    $form['options']['x_grid'] = [
      '#title' => $this->t('Default value for displaying grid for x axis'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['x_grid'],
    ];

    $form['options']['allow_x_min'] = [
      '#title' => $this->t('Allow option for x axis min'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_x_min'],
    ];
    $form['options']['x_min'] = [
      '#title' => $this->t('Default value for x axis min'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('options')['x_min'],
    ];
    $form['options']['allow_x_max'] = [
      '#title' => $this->t('Allow option for x axis max'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_x_max'],
    ];
    $form['options']['x_max'] = [
      '#title' => $this->t('Default value for x axis max'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('options')['x_max'],
    ];
    $form['options']['allow_x_step'] = [
      '#title' => $this->t('Allow option for x axis step'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_x_step'],
    ];
    $form['options']['x_step'] = [
      '#title' => $this->t('Default value for x axis step'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('options')['x_step'],
    ];

    $form['options']['allow_y_axis_display'] = [
      '#title' => $this->t('Allow label display option for y axis'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_y_axis_display'],
    ];
    $form['options']['y_axis_display'] = [
      '#title' => $this->t('Default value for y axis label display'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['y_axis_display'],
    ];
    $form['options']['allow_y_axis_label'] = [
      '#title' => $this->t('Allow label option for y axis'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_y_axis_label'],
    ];
    $form['options']['y_axis_label'] = [
      '#title' => $this->t('Default value for y axis label'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('options')['y_axis_label'],
    ];
    $form['options']['allow_zero_y'] = [
      '#title' => $this->t('Allow start at zero option for y axis'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_zero_y'],
    ];
    $form['options']['default_zero_y'] = [
      '#title' => $this->t('Default value for start at zero for y axis'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['default_zero_y'],
    ];
    $form['options']['allow_y_grid'] = [
      '#title' => $this->t('Allow grid option for y axis'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_y_grid'],
    ];
    $form['options']['y_grid'] = [
      '#title' => $this->t('Default value for displaying grid for y axis'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['y_grid'],
    ];

    $form['options']['allow_y_min'] = [
      '#title' => $this->t('Allow option for y axis min'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_y_min'],
    ];
    $form['options']['y_min'] = [
      '#title' => $this->t('Default value for y axis min'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('options')['y_min'],
    ];
    $form['options']['allow_y_max'] = [
      '#title' => $this->t('Allow option for y axis max'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_y_max'],
    ];
    $form['options']['y_max'] = [
      '#title' => $this->t('Default value for y axis max'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('options')['y_max'],
    ];
    $form['options']['allow_y_step'] = [
      '#title' => $this->t('Allow option for y axis step'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_y_step'],
    ];
    $form['options']['y_step'] = [
      '#title' => $this->t('Default value for y axis step'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('options')['y_step'],
    ];

    $form['options']['allow_maintain_aspect_ratio'] = [
      '#title' => $this->t('Allow maintaining aspect ratio option'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_maintain_aspect_ratio'],
    ];
    $form['options']['maintain_aspect_ratio'] = [
      '#title' => $this->t('Default value for maintaining aspect ratio'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['maintain_aspect_ratio'],
    ];
    $form['options']['allow_responsive'] = [
      '#title' => $this->t('Allow responsive option'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['allow_responsive'],
    ];
    $form['options']['responsive'] = [
      '#title' => $this->t('Default value for responsive'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('options')['responsive'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'types' => [
        'line',
        'bar',
        'radar',
        'pie',
        'doughnut',
        'polarArea',
        'bubble',
        'scatter'
      ],
      'data' => [
        'allow_labels' => TRUE,
        'allow_type' => TRUE,
        'type' => 'value',
        'allow_brdw' => TRUE,
        'brdw' => 1,
        'allow_bgc' => TRUE,
        'bgc' => '#000000',
        'allow_bgo' => TRUE,
        'bgo' => '0.8',
        'allow_brdc' => TRUE,
        'brdc' => '#000000',
        'allow_brdo' => TRUE,
        'brdo' => '1.0',
      ],
      'options' => [
        'display_title' => TRUE,
        'allow_display_title' => TRUE,

        'x_axis_display' => FALSE,
        'allow_x_axis_display' => TRUE,
        'x_axis_label' => '',
        'allow_x_axis_label' => TRUE,
        'allow_zero_x' => TRUE,
        'default_zero_x' => TRUE,
        'allow_x_grid' => FALSE,
        'x_grid' => TRUE,

        'x_min' => NULL,
        'allow_x_min' => FALSE,
        'x_max' => NULL,
        'allow_x_max' => FALSE,
        'x_step' => NULL,
        'allow_x_step' => FALSE,

        'y_axis_display' => FALSE,
        'allow_y_axis_display' => TRUE,
        'y_axis_label' => '',
        'alow_y_axis_label' => TRUE,
        'allow_zero_y' => TRUE,
        'default_zero_y' => TRUE,
        'allow_y_grid' => FALSE,
        'y_grid' => TRUE,

        'y_min' => NULL,
        'allow_y_min' => FALSE,
        'y_max' => NULL,
        'allow_y_max' => FALSE,
        'y_step' => NULL,
        'allow_y_step' => FALSE,

        'allow_maintain_aspect_ratio' => FALSE,
        'maintain_aspect_ratio' => TRUE,
        'allow_responsive' => FALSE,
        'responsive' => TRUE,
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    $summary[] = $this->t('Types: @types', ['@types' => implode(', ', $settings['types'])]);

    $setting_summary = str_replace(':', ": ", json_encode($settings));
    $summary[] = $this->t('Settings: @settings', ['@settings' => $setting_summary]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getLabel();
    $settings = $this->getSettings();

    $config = $this->configFactory->get('chart_js_field.settings');
    if ($config->get('external')){
      $element['#attached']['library'][] = 'chart_js_field/ext_chart_ux';
    }else{
      $element['#attached']['library'][] = 'chart_js_field/chart_ux';
    }

    $element['#attached']['drupalSettings']['chart_ux'] =  $settings;

    $element['#prefix'] = '<details class="chart-js-field"><summary>' . $field_name .'</summary><div class="chart-js-field-form-wrapper">';
    $element['#suffix'] = '</div></details>';

    $options = [];
    foreach ($this->getSetting('types') as $value) {
      $options[$value] = $this->types[$value];
    }
    $element['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Chart Type'),
      '#default_value' => isset($items[$delta]->type) ?
      $items[$delta]->type : NULL,
      '#options' => $options,
      '#attributes' => [
        'class' => ['chart-type-select'],
      ],
    ];

    $element['data'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Data'),
      '#default_value' => isset($items[$delta]->data) ?
      $items[$delta]->data : NULL,
      '#prefix' => '<div class="chart-js-data-field-wrapper">',
      '#suffix' => '</div>',
    ];

    $element['options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Options'),
      '#default_value' => isset($items[$delta]->options) ?
      $items[$delta]->options : NULL,
      '#prefix' => '<div class="chart-js-options-field-wrapper">',
      '#suffix' => '</div>',
    ];

    return $element;
  }

}
