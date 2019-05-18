<?php

namespace Drupal\inline_ad_entity\Plugin\Field\FieldFormatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;

/**
 * Plugin implementation of the 'Inline Ad Entity' formatter.
 *
 * @FieldFormatter(
 *  id = "inline_ad_entity",
 *  label = @Translation("Content with Inline Ads"),
 *  field_types = {
 *    "text_with_summary",
 *    "text_long",
 *    "text"
 *  },
 * )
 */
class InlineAdEntityFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * The storage for Advertising entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $adEntityStorage;

  /**
   * A list of all existent Advertising entities.
   *
   * @var \Drupal\ad_entity\Entity\AdEntityInterface[]
   */
  protected $adEntities;

  /**
   * The view builder for Display configs for Advertisement.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $adDisplayViewBuilder;

  /**
   * Constructor function.
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
   * @param Drupal\Core\Entity\EntityStorageInterface $ad_entity_storage
   *   Storage variable for ad entities.
   * @param Drupal\Core\Entity\EntityViewBuilderInterface $ad_display_view_builder
   *   Allows for access to Ad Entity module build function.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityStorageInterface $ad_entity_storage, EntityViewBuilderInterface $ad_display_view_builder) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->adEntityStorage = $ad_entity_storage;
    $this->adEntities = $this->adEntityStorage->loadMultiple();
    $this->adDisplayViewBuilder = $ad_display_view_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $type_manager = $container->get('entity_type.manager');
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $type_manager->getStorage('ad_display'),
      $type_manager->getViewBuilder('ad_display')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Ads Ad Entity ads into content.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'ad_frequency' => '3',
      'ad_display' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['ad_frequency'] = [
      '#title' => $this->t('Ad Frequency'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('ad_frequency'),
    ];

    $element['ad_display'] = [
      '#title' => $this->t('Ad Display'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('ad_display'),
      '#options' => $this->getAdDisplayOptions(),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [];
      $split = $this->splitValue($item->value, $this->getSetting('ad_frequency'));
      $splitLength = count($split) - 1;

      foreach ($split as $index => $value) {
        if (trim($value)) {
          $element[$delta][] = [
            '#type' => 'processed_text',
            '#text' => $value,
            '#format' => $item->format,
            '#langcode' => $item->getLangcode(),
          ];
          // Avoids adding ad as very last item.
          $displayEntity = $this->adEntities[$this->getSetting('ad_display')];
          $renderedEntity = $this->adDisplayViewBuilder->view($displayEntity, 'default');
          if (!($index === $splitLength)) {
            $element[$delta][] = $renderedEntity;
          }
        }
      }
    }

    return $element;
  }

  /**
   * Splits markup into an array every n instances for a given deliminator.
   *
   * @param string $value
   *   The string of text/markup to be split.
   * @param int $frequency
   *   The number of instances of the given deliminator.
   * @param string $deliminator
   *   The string value to split on (defaults to '</p>').
   *
   * @return array
   *   An exploded array of the string's split portions
   */
  private function splitValue($value, $frequency, $deliminator = '</p>') {
    $clumps = [];
    $exploded = explode($deliminator, $value);

    // Append the deliminator back onto each chunk.
    $count = count($exploded);
    foreach ($exploded as &$chunk) {
      if (--$count <= 0) {
        // Do not append deliminator to last element.
        break;
      }
      $chunk = $chunk . $deliminator;
    }
    unset($chunk);

    // Group by frequency.
    $clumpIndex = 0;
    foreach ($exploded as $position => $piece) {
      if ($position % $frequency === 0) {
        $clumpIndex++;
        $clumps[$clumpIndex] = $piece;
      }
      else {
        $clumps[$clumpIndex] .= $piece;
      }
    }

    return $clumps;
  }

  /**
   * Builds a list of Ad Entity Display display options.
   *
   * @return array
   *   Human readable names for ad entity displays.
   */
  private function getAdDisplayOptions() {
    // Get all Advertising entities to choose from.
    $options = [];
    foreach ($this->adEntities as $entity) {
      $options[$entity->id()] = $entity->label();
    }

    return $options;
  }

}
