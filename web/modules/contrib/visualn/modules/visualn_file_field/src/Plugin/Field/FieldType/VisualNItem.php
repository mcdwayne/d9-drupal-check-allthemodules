<?php

namespace Drupal\visualn_file_field\Plugin\Field\FieldType;

//use Drupal\Core\Field\FieldDefinitionInterface;
//use Drupal\Core\Field\FieldItemBase;
//use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'visualn' field type.
 *
 * @FieldType(
 *   id = "visualn_file",
 *   label = @Translation("VisualN file"),
 *   description = @Translation("This field stores the ID of a file as integer value."),
 *   category = @Translation("Reference"),
 *   default_widget = "visualn_file",
 *   default_formatter = "visualn_file",
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class VisualNItem extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    // @todo: check
    return array(
      //'override_style_id' => 0,
      'drawer_config' => [],
      'drawer_fields' => [],
    ) + parent::defaultFieldSettings();
  }

  // @todo: add isEmpty(). at least consider the scenario: user uploads a file and selects custom
  //   visualn style for the field. Then, when editing user deletes the file. Later user updates
  //   the content and uploads a new file but uses default visualn style. So previously
  //   selected visualn style must not be stored in the visualn_style_id column.

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
