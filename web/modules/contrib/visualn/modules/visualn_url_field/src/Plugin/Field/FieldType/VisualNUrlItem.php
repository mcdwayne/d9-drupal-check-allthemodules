<?php

namespace Drupal\visualn_url_field\Plugin\Field\FieldType;

//use Drupal\Core\Field\FieldDefinitionInterface;
//use Drupal\Core\Field\FieldItemBase;
//use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'visualn_url' field type.
 *
 * @FieldType(
 *   id = "visualn_url",
 *   label = @Translation("VisualN url"),
 *   description = @Translation("Stores a URL string that points to a resource for visualization"),
 *   default_widget = "visualn_url",
 *   default_formatter = "visualn_url",
 *   constraints = {"LinkType" = {}, "LinkAccess" = {}, "LinkExternalProtocols" = {}, "LinkNotExistingInternal" = {}}
 * )
 */
class VisualNUrlItem extends LinkItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    // @todo: check
    // the default settings are used when attaching a field to an entity bundle
    // here only override_style_id seems to have sense
    return array(
      //'override_style_id' => 0,
      'drawer_config' => [],
      'drawer_fields' => [],
      'resource_format' => '',
    ) + parent::defaultFieldSettings();
  }

  // @todo: check comment regarding isEmpty() in visualn_file_field submodule field type

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // @todo:
    $properties = parent::propertyDefinitions($field_definition);
    $properties['visualn_style_id'] = DataDefinition::create('string')
      ->setLabel(t('VisualN Style'));
    $properties['visualn_data'] = DataDefinition::create('string')
      ->setLabel(t('VisualN Data'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    // @todo: check the keys. also it seems to behave like an entityreference that
    //   references a visualn style
    $schema['columns']['visualn_style_id'] = [
      'description' => 'The ID of the visualn style used if overridden.',
      'type' => 'varchar_ascii',
      'length' => 255,
    ];
    $schema['columns']['visualn_data'] = [
      'type' => 'text',
      'mysql_type' => 'blob',
      'description' => 'Serialized visualn drawer config and fields mapping data.',
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);

    $settings = $this->getSettings();

    // @todo: doesn't work yet
/*
    $element['override_style_id'] = array(
      '#type' => 'checkbox',
      '#title' => t('Override style'),
      '#default_value' => $settings['override_style_id'],
      '#description' => t('This allows to override default VisualN Style that is set in field formatter settings.'),
    );
*/

    return $element;
  }

}
