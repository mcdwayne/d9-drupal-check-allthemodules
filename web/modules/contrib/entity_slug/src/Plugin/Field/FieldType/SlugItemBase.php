<?php

namespace Drupal\entity_slug\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\entity_slug\Plugin\Slugifier\SlugifierInterface;
use Drupal\entity_slug\SlugifierManager;

/**
 * Abstract base class SlugItemBase.
 *
 * @package Drupal\entity_slug\Plugin\Field\FieldType
 */
abstract class SlugItemBase extends FieldItemBase implements SlugItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Slug'));

    $properties['input'] = DataDefinition::create('string')
      ->setLabel(t('Input'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'input' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('input')->getValue();

    return $value === NULL || $value === '';
  }

  public static function defaultFieldSettings() {
    return [
        'slugifier_plugins' => ['token' => 'token', 'pathauto' => 'pathauto'],
        'force_default' => FALSE,
      ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $settings = $this->getSettings();

    $element['slugifier_plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Slugifier plugins'),
      '#description' => $this->t('Select the slugifier plugins to use for this field.'),
      '#options' => $this->getSlugifierOptions(),
      '#default_value' => $settings['slugifier_plugins'],
    ];

    $element['force_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force default value'),
      '#default_value' => $settings['force_default'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if ($this->getSetting('force_default')) {
      $this->set('input', $this->getFieldDefinition()->getDefaultValueLiteral()[0]['input']);
    }

    $this->set('value', $this->slugify($this->get('input')->getValue()));

    parent::preSave();
  }

  /**
   * {@inheritdoc}
   */
  public function slugify($input) {
    $slug = $input;

    foreach ($this->getSlugifiers() as $slugifier) {
      $slug = $slugifier->slugify($slug, $this->getEntity());
    }

    return $slug;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlugifiers() {
    /** @var SlugifierManager $manager */
    $manager = \Drupal::service('plugin.manager.slugifier');

    $settings = $this->getSettings();
    $slugifiers = array_filter($settings['slugifier_plugins']);

    $plugins = [];

    foreach ((array) $slugifiers as $pluginId => $name) {
      /** @var SlugifierInterface $slugifier */
      $slugifier = $manager->createInstance($pluginId);

      $plugins[$pluginId] = $slugifier;
    }

    uasort($plugins, ['Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);

    return $plugins;
  }

  /**
   * Gets a list of options of Slugifier plugins
   */
  protected function getSlugifierOptions() {
    /** @var SlugifierManager $manager */
    $manager = \Drupal::service('plugin.manager.slugifier');

    $definitions = $manager->getDefinitions();

    uasort($definitions, function ($a, $b) {
      $aWeight = !empty($a['weight']) ? $a['weight'] : 0;
      $bWeight = !empty($b['weight']) ? $b['weight'] : 0;

      if ($aWeight == $bWeight) {
        return 0;
      }

      return ($aWeight < $bWeight) ? -1 : 1;
    });

    $options = [];

    foreach ($definitions as $pluginId => $definition) {
      $options[$pluginId] = $definition['name'];
    }

    return $options;
  }
}
