<?php

namespace Drupal\pluginreference\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'plugin_reference' entity field type.
 *
 * @FieldType(
 *   id = "plugin_reference",
 *   label = @Translation("Plugin reference"),
 *   description = @Translation("This field stores a soft reference to a plugin"),
 *   default_widget = "plugin_reference_select",
 *   default_formatter = "plugin_reference_id",
 * )
 */
class PluginReferenceItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'target_type' => '',
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Text value'))
      ->addConstraint('Length', array('max' => 255))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 255,
        ),
      ),
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = NULL;
    if (\Drupal::getContainer()->has('plugin.manager.' . $field_definition->getSetting('target_type'))) {
      $definitions = \Drupal::getContainer()
        ->get('plugin.manager.' . $field_definition->getSetting('target_type'))
        ->getDefinitions();
      shuffle($definitions);
      $values['value'] = key($definitions);
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];
    $options = [];

    $container = \Drupal::getContainer();
    foreach ($container->getServiceIds() as $serviceId) {
      if (strpos($serviceId, 'plugin.manager.') === 0) {
        $service = $container->get($serviceId);
        $typeName = substr($serviceId, 15);
        $class = get_class($service);
        $class_parts =  preg_split('/\\\/', $class);
        $provider = $class_parts[1];
        if ($provider === 'Core') {
          $provider = 'system';
        }
        $options[\Drupal::moduleHandler()->getName($provider)][$typeName] = $class;
      }
    }

    $elements['target_type'] = array(
      '#type' => 'select',
      '#title' => t('Type of plugin to reference'),
      '#options' => $options,
      '#default_value' => $this->getSetting('target_type'),
      '#required' => TRUE,
      '#disabled' => $has_data,
      '#size' => 1,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
