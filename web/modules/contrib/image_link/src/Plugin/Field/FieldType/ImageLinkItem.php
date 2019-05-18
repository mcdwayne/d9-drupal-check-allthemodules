<?php

namespace Drupal\image_link\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

/**
 * Plugin implementation of the 'image_link' field type.
 *
 * @FieldType(
 *   id = "image_link",
 *   label = @Translation("Image Link"),
 *   description = @Translation("Image link."),
 *   default_widget = "image_link",
 *   default_formatter = "image_link"
 * )
 */
class ImageLinkItem extends ImageItem implements ImageLinkItemInterface, LinkItemInterface {

  /**
   * The link item field.
   *
   * @var \Drupal\link\Plugin\Field\FieldType\LinkItem
   */
  protected $linkItemField;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $image_default_field_settings = parent::defaultFieldSettings();
    $link_default_field_settings = LinkItem::defaultFieldSettings();

    //NOTE: they both share the "title" column, this is a hack to work around.
    $link_default_field_settings['link_title'] = $link_default_field_settings['title'];
    unset($link_default_field_settings['title']);

    return array_merge($image_default_field_settings, $link_default_field_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $image_properties = parent::propertyDefinitions($field_definition);
    $link_properties = LinkItem::propertyDefinitions($field_definition);

    //NOTE: they both share the "title" column, this is a hack to work around.
    $link_properties['link_title'] = $link_properties['title'];
    unset($link_properties['title']);
    $properties = array_merge(
      $image_properties,
      $link_properties
    );
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $image_schema = parent::schema($field_definition);
    $link_schema = LinkItem::schema($field_definition);

    //NOTE: they both share the "title" column, this is a hack to work around.
    $link_schema['columns']['link_title'] = $link_schema['columns']['title'];
    unset($link_schema['columns']['title']);

    return [
      'columns' => array_merge($image_schema['columns'], $link_schema['columns']),
      'indexes' => array_merge($image_schema['indexes'], $link_schema['indexes']),
      'foreign keys' => $image_schema['foreign keys'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $image_form = parent::fieldSettingsForm($form, $form_state);
    $link_form = $this->getLinkItem()->fieldSettingsForm($form, $form_state);

    //NOTE: they both share the "title" column, this is a hack to work around.
    $link_form['link_title'] = $link_form['title'];
    unset($link_form['title']);

    return array_merge($image_form, $image_form);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $image_sample = parent::generateSampleValue($field_definition);
    $link_sample = LinkItem::generateSampleValue($field_definition);

    //NOTE: they both share the "title" column, this is a hack to work around.
    $link_sample['link_title'] = $link_sample['title'];
    unset($link_sample['title']);

    return array_merge($image_sample, $link_sample);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return parent::isEmpty() && $this->getLinkItem()->isEmpty();
  }

  /**
   * Gets the link item.
   *
   * @return \Drupal\link\Plugin\Field\FieldType\LinkItem
   */
  protected function getLinkItem() {
    if (!isset($this->linkItemField)) {
      $definition = $this->getDataDefinition();

      // swap title and link title.
      $definition['title'] = $definition['link_title'];

      $this->linkItemField = new LinkItem($definition);
    }
    return $this->linkItemField;
  }

  /**
   * {@inheritdoc}
   */
  public function isExternal() {
    return $this->getLinkItem()->isExternal();
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->getLinkItem()->getUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    $link_values = $values;
    $link_values['title'] = $link_values['link_title'];
    unset($link_values['link_title']);
    $this->getLinkItem()->setValue($link_values, $notify);
    parent::setValue($values, $notify);
  }
}
