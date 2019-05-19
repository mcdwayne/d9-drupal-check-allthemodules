<?php

namespace Drupal\tracking_number\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\tracking_number\Plugin\TrackingNumberTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'tracking_number_plain' formatter.
 *
 * @FieldFormatter(
 *   id = "tracking_number_plain",
 *   label = @Translation("Plain-text tracking number"),
 *   field_types = {
 *     "tracking_number",
 *   }
 * )
 */
class TrackingNumberPlainFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The tracking number type manager service.
   *
   * @var \Drupal\tracking_number\Plugin\TrackingNumberTypeManager
   */
  protected $trackingNumberTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, TrackingNumberTypeManager $tracking_number_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->trackingNumberTypeManager = $tracking_number_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['view_mode'], $configuration['third_party_settings'], $container->get('plugin.manager.tracking_number_type'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'type_display' => TRUE,
    ] + parent::defaultSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['type_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display the type'),
      '#default_value' => $this->getSetting('type_display'),
      '#description' => $this->t("Display the tracking number's type after the number itself (eg. \"ABC123 (United States Postal Service)\")."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->getSetting('type_display') ? t('Type displayed') : t('Type not displayed');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      // If the item isn't empty and is of a valid type.
      if (!$item->isEmpty() && $this->trackingNumberTypeManager->getDefinition($item->type, FALSE)) {
        $type_plugin = $this->trackingNumberTypeManager->createInstance($item->type);
        $elements[$delta]['item'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['tracking-number']],
        ];
        $elements[$delta]['item']['number'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $item->value,
          '#attributes' => [
            'class' => [
              'number',
              'number-plain',
            ],
          ],
        ];

        // Respect number type display setting.
        if ($this->getSetting('type_display')) {
          $elements[$delta]['item']['type'] = [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => '(' . $type_plugin->getLabel() . ')',
            '#attributes' => [
              'class' => ['type'],
            ],
            '#prefix' => ' ',
          ];
        }
      }
    }

    return $elements;
  }

}
