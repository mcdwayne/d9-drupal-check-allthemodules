<?php

namespace Drupal\chart_js_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Utility\Token;

/**
 * Plugin implementation of the 'html_inject_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "chart_js_field_formatter",
 *   label = @Translation("Chart.js Field Formatter"),
 *   field_types = {
 *     "chart_js_field_type"
 *   }
 * )
 */
class ChartJsFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal LoggerFactory service container.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;
  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;
  /**
   * Drupal token service container.
   *
   * @var Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactory $config_factory, ModuleHandlerInterface $module_handler, Token $token) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->token = $token;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form['width'] = [
      '#title' => $this->t('Width'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('width'),
      '#description' => $this->t("Enter the width of the chart in pixels."),
    ];

    $form['height'] = [
      '#title' => $this->t('Height'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('height'),
      '#description' => $this->t("Enter the height of the chart in pixels."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => NULL,
      'height' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Width: @width', ['@width' => $this->getSetting('width')]);
    $summary[] = $this->t('Height: @height', ['@height' => $this->getSetting('height')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {

      // Get entity.
      $entity = $item->getEntity();

      // If the token module is enabled then do token replacement.
      if ($this->moduleHandler->moduleExists('token')) {
        $token_data = [
          $entity->getEntityTypeId() => $entity,
        ];
        $data = $this->token->replace($item->data, $token_data, ['clear' => FALSE]);
        $options = $this->token->replace($item->options, $token_data, ['clear' => FALSE]);
      } else {
        $data = $item->data;
        $options = $item->options;
      }

      $config = $this->configFactory->get('chart_js_field.settings');

      // Uses the chart-js.thml.twig template.
      $element[$delta] = [
        '#theme' => 'chart_js',
        '#width' => $this->getSetting('width'),
        '#height' => $this->getSetting('height'),
        '#uid' => $entity->uuid(),
        '#type' => $item->type,
        '#data' => $data,
        '#options' => $options,
        '#external' => $config->get('external'),
      ];
    }

    return $element;
  }

}
