<?php

namespace Drupal\rel_content\Plugin\Field\FieldType;

use Drupal\Core\Field\AllowedTagsXssTrait;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;

/**
 * List rel content field type.
 *
 * @FieldType(
 *   id = "list_rel_content",
 *   label = @Translation("List rel content"),
 *   default_widget = "list_rel_content_select",
 *   default_formatter = "list_rel_content_id",
 * )
 */
class ListRelContent extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'list_rel_content' => [],
    ] + parent::defaultStorageSettings();
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
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $list_rel_content = $this->getSetting('list_rel_content');
    $options = [];

    /* @var $manager \Drupal\rel_content\RelatedContentPluginManager */
    if ($manager = \Drupal::getContainer()->get('plugin.manager.rel_content')) {
      foreach ($manager->getDefinitions() as $plugin_type_id => $plugin_definition) {
        $options[$plugin_type_id] = isset($plugin_definition['description']) ? (string) $plugin_definition['description'] : $plugin_type_id;
      }
    }

    $element['list_rel_content'] = [
      '#type' => 'checkboxes',
      '#default_value' => $list_rel_content,
      '#options' => $options,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
