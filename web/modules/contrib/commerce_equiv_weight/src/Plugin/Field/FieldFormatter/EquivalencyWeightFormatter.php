<?php

namespace Drupal\commerce_equiv_weight\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\physical\NumberFormatterInterface;
use Drupal\physical\Plugin\Field\FieldFormatter\MeasurementDefaultFormatter;
use Drupal\physical\Weight;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Equivalency Weight' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_equiv_weight",
 *   label = @Translation("Equivalency Weight"),
 *   field_types = {
 *     "physical_measurement"
 *   }
 * )
 */
class EquivalencyWeightFormatter extends MeasurementDefaultFormatter {

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Construct a EquivalencyWeightFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\physical\NumberFormatterInterface $number_formatter
   *   The number formatter.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    NumberFormatterInterface $number_formatter,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $number_formatter);

    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('physical.number_formatter'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Renders the equivalency weight.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $config = $this->configFactory->get('commerce_equiv_weight.order_settings');
    $ew_config = $config->get('equiv_weight');
    $max_ew = new Weight($ew_config['number'], $ew_config['unit']);

    /** @var \Drupal\physical\Plugin\Field\FieldType\MeasurementItem $item */
    foreach ($items as $delta => $item) {
      $values = $item->getValue();
      $weight = new Weight($values['number'], $values['unit']);

      $element[$delta] = [
        '#theme' => 'commerce_equiv_weight_field',
        '#rounded_weight' => commerce_equiv_weight_round($weight->getNumber()) . $weight->getUnit(),
        '#weight' => $weight->getNumber(),
        '#unit' => $weight->getUnit(),
        '#over_limit' => $weight->greaterThan($max_ew),
      ];
    }

    return $element;

  }

}
