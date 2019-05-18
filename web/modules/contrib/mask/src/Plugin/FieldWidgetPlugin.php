<?php

namespace Drupal\mask\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for field widgets that support Mask's settings.
 */
class FieldWidgetPlugin extends PluginBase implements FieldWidgetPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetType() {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetThirdPartySettings(WidgetInterface $widget) {
    return $widget->getThirdPartySettings('mask') + $this->pluginDefinition['defaults'];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldWidgetThirdPartySettingsForm(WidgetInterface $plugin, FieldDefinitionInterface $field_definition, $form_mode, array $form, FormStateInterface $form_state) {
    $mask_settings = $this->getFieldWidgetThirdPartySettings($plugin);
    $element = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mask settings'),
    ];
    $element['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mask'),
      '#default_value' => $mask_settings['value'],
    ];
    $element['translation_help'] = [
      '#type' => 'details',
      '#title' => $this->t('Available patterns'),
      '#open' => FALSE,
      'table' => $this->translationSymbolsTable(),
      'instructions' => [
        '#markup' => $this->t('Use these symbols to restrict the characters that can be provided. They can be configured at the <a href="@url">module settings</a>.', [
          '@url' => Url::fromRoute('mask.settings')->toString(),
        ]),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ],
    ];
    $element['reverse'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reverse'),
      '#description' => $this->t('Applies the mask in reverse order (from right to left).'),
      '#default_value' => $mask_settings['reverse'],
    ];
    $element['clearifnotmatch'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clear if not match'),
      '#description' => $this->t('Clears the input field when it loses focus if the mask is not complete.'),
      '#default_value' => $mask_settings['clearifnotmatch'],
    ];
    $element['selectonfocus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select on focus'),
      '#description' => $this->t('Selects the input field value when it is focused.'),
      '#default_value' => $mask_settings['selectonfocus'],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldWidgetSettingsSummaryAlter(array &$summary, array $context) {
    $widget = $context['widget'];
    $mask_settings = $this->getFieldWidgetThirdPartySettings($widget);
    if ($mask_settings['value']) {
      $summary[] = $this->t('Mask: @value', ['@value' => $mask_settings['value']]);

      $value = $mask_settings['reverse'] ? $this->t('Yes') : $this->t('No');
      $summary[] = $this->t('Reverse: @value', ['@value' => $value]);

      $value = $mask_settings['clearifnotmatch'] ? $this->t('Yes') : $this->t('No');
      $summary[] = $this->t('Clear if not match: @value', ['@value' => $value]);

      $value = $mask_settings['selectonfocus'] ? $this->t('Yes') : $this->t('No');
      $summary[] = $this->t('Select on focus: @value', ['@value' => $value]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fieldWidgetFormAlter(array &$element, FormStateInterface $form_state, array $context) {
    $widget = $context['widget'];
    $mask_settings = $this->getFieldWidgetThirdPartySettings($widget);
    if (!empty($mask_settings['value'])) {
      $parents = $this->pluginDefinition['element_parents'];
      $parents[] = '#mask';
      NestedArray::setValue($element, $parents, $mask_settings);
    }
  }

  /**
   * Builds a table with configured translation symbols.
   *
   * @return array
   *   The table's render element.
   */
  protected function translationSymbolsTable() {
    $element = [
      '#type' => 'table',
      '#header' => [
        $this->t('Symbol'),
        $this->t('Pattern'),
        $this->t('Fallback'),
        $this->t('Optional'),
        $this->t('Recursive'),
      ],
      '#rows' => [],
    ];

    // Adds rows for each pattern.
    if ($config = $this->configFactory->get('mask.settings')->get('translation')) {
      foreach ($config as $symbol => $options) {
        $element['#rows'][] = [
          $symbol,
          $options['pattern'],
          isset($options['fallback']) ? $options['fallback'] : '',
          empty($options['optional']) ? $this->t('No') : $this->t('Yes'),
          empty($options['recursive']) ? $this->t('No') : $this->t('Yes'),
        ];
      }
    }

    return $element;
  }

}
