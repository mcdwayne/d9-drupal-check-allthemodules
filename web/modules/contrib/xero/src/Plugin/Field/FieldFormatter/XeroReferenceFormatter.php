<?php

namespace Drupal\xero\Plugin\Field\FieldFormatter;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TypedDataManagerInterface;

/**
 * Xero Reference field formatter.
 *
 * @FieldFormatter(
 *   id = "xero_reference",
 *   label = @Translation("Xero Reference"),
 *   field_types = {
 *     "xero_reference",
 *   },
 * )
 *
 * @internal
 */
class XeroReferenceFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display' => ['guid' => 'guid', 'label' => 'label', 'type' => 'type'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('typed_data_manager')
    );
  }

  /**
   * Get the typed data manager from \Drupal. This cannot use type hinting
   * because TypedDataManager must be mocked in PHPUnit. DrupalWTF.
   *
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $definition, array $settings, $label, $view_mode, array $third_party_settings, $typed_data_manager) {

    parent::__construct($plugin_id, $plugin_definition, $definition, $settings, $label, $view_mode, $third_party_settings);
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display fields'),
      '#options' => [
        'guid' => $this->t('GUID'),
        'label' => $this->t('Label'),
        'type' => $this->t('Xero type'),
      ],
      '#multiple' => TRUE,
      '#default_value' => $this->getSetting('display'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = array();

    $guid = in_array('guid', $this->getSetting('display')) ? 'Visible' : 'Hidden';
    $type = in_array('type', $this->getSetting('display')) ? 'Visible' : 'Hidden';
    $label = in_array('label', $this->getSetting('display')) ? 'Visible' : 'Hidden';

    $settings[] = $this->t('Guid: @setting', array('@setting' => $guid));
    $settings[] = $this->t('Type: @setting', array('@setting' => $type));
    $settings[] = $this->t('Label: @setting', array('@setting' => $label));

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      try {
        $definition = $this->typedDataManager->getDefinition($item->type);
        $elements[$delta] = [
          '#theme' => 'xero_reference',
          '#item' => $item,
          '#delta' => $delta,
          '#definition' => $definition,
          '#settings' => $this->getSettings(),
          '#attributes' => [
            'class' => [
              'field-item',
              'field-item--xero-reference',
              'field-item--' . str_replace('_', '-', $item->type),
            ],
          ],
        ];
      }
      catch (PluginNotFoundException $e) {
        $elements[$delta] = [
          '#markup' => $this->t('Plugin @name not found.', ['@name' => $item->type]),
          '#attributes' => [
            'class' => ['element-invisible'],
          ],
        ];
      }
    }

    return $elements;
  }

}
