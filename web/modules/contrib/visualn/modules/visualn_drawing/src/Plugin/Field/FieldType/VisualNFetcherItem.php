<?php

namespace Drupal\visualn_drawing\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\visualn\WindowParametersInterface;
use Drupal\visualn\WindowParametersTrait;

/**
 * Plugin implementation of the 'visualn_fetcher' field type.
 *
 * @FieldType(
 *   id = "visualn_fetcher",
 *   label = @Translation("VisualN fetcher"),
 *   description = @Translation("Stores info about Drawing Fetcher plugin configuration"),
 *   default_widget = "visualn_fetcher",
 *   default_formatter = "visualn_fetcher",
 * )
 */
class VisualNFetcherItem extends FieldItemBase implements WindowParametersInterface {

  use WindowParametersTrait;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // @todo: do the same for other formatters
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['fetcher_id'] = DataDefinition::create('string')
      //->setLabel(t('Drawing fetcher plugin'));
      ->setLabel(new TranslatableMarkup('Drawing fetcher plugin'));
    // @todo: what is available length for the config?
    $properties['fetcher_config'] = DataDefinition::create('string')
      //->setLabel(t('Drawing fetcher config'));
      ->setLabel(new TranslatableMarkup('Drawing fetcher config'));

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

    $schema['columns']['fetcher_id'] = [
      'description' => 'The ID of the drawing fetcher plugin used if overridden.',
      'type' => 'varchar_ascii',
      'length' => 255,
    ];
    // @todo: use fetcher_data if there should be not only fetcher_config (as it is done for visualn_data)
    $schema['columns']['fetcher_config'] = [
      'type' => 'text',
      'mysql_type' => 'blob',
      'description' => 'Serialized drawing fetcher config.',
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
    $value = $this->get('fetcher_id')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   *
   * @todo: add to interface
   */
  public function buildDrawing() {
    // @todo: also see VisualNFetcherFormatter::viewValue()

    // get fetcher plugin and create a drawing
    if (!$this->isEmpty()) {
      $fetcher_id = $this->get('fetcher_id')->getValue();
      $fetcher_config = $this->get('fetcher_config')->getValue();
      $fetcher_config = !empty($fetcher_config) ? unserialize($fetcher_config) : [];
      // @todo: instantiate at calss create
      $fetcher_plugin = \Drupal::service('plugin.manager.visualn.drawing_fetcher')
                          ->createInstance($fetcher_id, $fetcher_config);

      // Set reference to the entity since fetcher plugin generally may need all entity fields.

      // @todo: replace "any" context type with an appropriate one
      // Set "current_entity" context
      $context_current_entity = new Context(new ContextDefinition('any', NULL, TRUE), $this->getEntity());
      $fetcher_plugin->setContext('current_entity', $context_current_entity);
      // @todo: see the note regarding setting context in VisualNResourceProviderItem class


      $fetcher_plugin->setWindowParameters($this->getWindowParameters());
      $drawing_markup = $fetcher_plugin->fetchDrawing();
    }
    else {
      $drawing_markup = ['#markup' => ''];
    }
    return $drawing_markup;
  }

}
