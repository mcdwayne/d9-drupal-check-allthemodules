<?php

namespace Drupal\visualn_dataset\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Plugin implementation of the 'visualn_resource_provider' field type.
 *
 * @FieldType(
 *   id = "visualn_resource_provider",
 *   label = @Translation("VisualN resource provider"),
 *   description = @Translation("Stores info about VisualN Resource Provider plugin configuration"),
 *   default_widget = "visualn_resource_provider",
 *   default_formatter = "visualn_resource_provider"
 * )
 */
class VisualNResourceProviderItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      //'max_length' => 255,
      //'is_ascii' => FALSE,
      //'case_sensitive' => FALSE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['resource_provider_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Resource provider plugin'));
    // @todo: maybe there is a way to store config without serializing it
    // @todo: what is available length for the config?
    $properties['resource_provider_config'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Resource provider config'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
      ],
    ];

    $schema['columns']['resource_provider_id'] = [
      'description' => 'The ID of the resource provider plugin used if overridden.',
      'type' => 'varchar_ascii',
      'length' => 255,
    ];
    // @todo: use resource_provider_data if there should be not only resource_provider (as it is done for visualn_data)
    $schema['columns']['resource_provider_config'] = [
      'type' => 'text',
      'mysql_type' => 'blob',
      'description' => 'Serialized resource provider config.',
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  /*public function getConstraints() {
    $constraints = parent::getConstraints();
    return $constraints;
  }*/


  /**
   * {@inheritdoc}
   */
  /*public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];
    return $elements;
  }*/

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('resource_provider_id')->getValue();
    return $value === NULL || $value === '';
  }


  /**
   * {@inheritdoc}
   *
   * @todo: add to interface
   * @todo: maybe rename the method
   */
  // @todo: In a similar manner fetcher field for the drawing should return instantiated and configured
  //    fetcher plugin instead of drawing markup
  public function getResourceProviderPlugin() {
    $resource_provider_plugin = NULL;
    if (!$this->isEmpty()) {
      $resource_provider_id = $this->get('resource_provider_id')->getValue();
      $resource_provider_config = $this->get('resource_provider_config')->getValue();
      $resource_provider_config = !empty($resource_provider_config) ? unserialize($resource_provider_config) : [];
      // @todo: instantiate at calss create
      $resource_provider_plugin = \Drupal::service('plugin.manager.visualn.resource_provider')
                          ->createInstance($resource_provider_id, $resource_provider_config);

      // Set reference to the entity since resource provider plugin generally may need all entity fields.

      // @todo: replace "any" context type with an appropriate one
      // Set "current_entity" context
      $context_current_entity = new Context(new ContextDefinition('any', NULL, TRUE), $this->getEntity());
      $resource_provider_plugin->setContext('current_entity', $context_current_entity);
      // @todo: or just setContextValue() here, the context itself could be initialized in the $provider_plugin,
      //    also context values could be set as part of $resource_provider_config['context'], that will
      //    be used by ContextAwarePluginBase::_consturct() to initialize context values
    }

    return $resource_provider_plugin;
  }

}
