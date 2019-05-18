<?php

namespace Drupal\commerce_affirm\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_affirm\MinorUnitsInterface;

/**
 * Plugin implementation of the 'commerce_affirm_messaging' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_affirm_messaging",
 *   label = @Translation("Affirm"),
 *   field_types = {
 *     "commerce_price"
 *   }
 * )
 */
class AffirmFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The minor units converter service.
   *
   * @var \Drupal\commerce_affirm\MinorUnitsInterface
   */
  protected $minorUnits;

  /**
   * Constructs a new AffirmFormatter object.
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
   *   Any third party settings settings.
   * @param \Drupal\commerce_affirm\MinorUnitsInterface $minor_units
   *   The minor units service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MinorUnitsInterface $minor_units) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->minorUnits = $minor_units;
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
      $container->get('commerce_affirm.minor_units')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = ['page_type' => 'product'];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['page_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Page type'),
      '#default_value' => $this->getSetting('page_type'),
      '#options' => _commerce_affirm_page_types(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Page type: @page_type', [
      '@page_type' => $this->getSetting('page_type'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      if ($item->currency_code !== 'USD') {
        $elements[$delta] = [];
      }
      $entity = $item->getEntity();
      /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
      switch ($entity->getEntityTypeId()) {
        case 'commerce_product_variation':
          /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $entity */
          $price = $entity->getPrice();
          break;

        case 'commerce_order':
          /** @var \Drupal\commerce_order\Entity\OrderInterface $entity */
          $price = $entity->getTotalPrice();
          break;
      }
      if ($price) {
        $number = $this->minorUnits->toMinorUnits($price);
        $elements[$delta] = [
          '#theme' => 'commerce_affirm_monthly_payment_message',
          '#page_type' => $this->getSetting('page_type'),
          '#number' => $number,
          '#variation' => $entity,
        ];
      }
    }
    return $elements;
  }

}
