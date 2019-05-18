<?php

namespace Drupal\ad_entity\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'ad_entity_context' field type.
 *
 * @FieldType(
 *   id = "ad_entity_context",
 *   label = @Translation("Advertising context"),
 *   description = @Translation("Contextual settings for Advertising entities being shown on the site"),
 *   default_widget = "ad_entity_context",
 *   default_formatter = "ad_entity_context"
 * )
 */
class ContextItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $definition_plugin_id = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('The chosen Context plugin id'))
      ->setRequired(TRUE);
    $definition_context_settings = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('Context plugin settings'))
      ->setRequired(FALSE);
    $definition_apply_on = ListDataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('The Advertising entities where to apply the given context'))
      ->setRequired(FALSE);
    $properties['context'] = MapDataDefinition::create()
      ->setLabel(new TranslatableMarkup('Advertising context'))
      ->setPropertyDefinition('context_plugin_id', $definition_plugin_id)
      ->setPropertyDefinition('context_settings', $definition_context_settings)
      ->setPropertyDefinition('apply_on', $definition_apply_on)
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'context' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'context';
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $context_value = $this->get('context')->getValue();
    return empty($context_value) || empty($context_value['context_plugin_id']);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (empty($values['context'])) {
      $values = [static::mainPropertyName() => $values];
    }
    // Unserialize the values.
    // @todo The storage controller should take care of this, see
    //   https://www.drupal.org/node/2414835
    if (is_string($values['context'])) {
      $values['context'] = unserialize($values['context']);
    }

    // The context may only contain settings of the chosen context plugin.
    $plugin_id = $values['context']['context_plugin_id'];
    $context_settings = !empty($values['context']['context_settings'][$plugin_id]) ?
      $values['context']['context_settings'][$plugin_id] : [];
    $values['context']['context_settings'] = [$plugin_id => $context_settings];

    parent::setValue($values, $notify);
  }

}
