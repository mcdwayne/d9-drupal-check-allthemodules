<?php

namespace Drupal\js_management\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Managed JavaScript entity.
 *
 * @ContentEntityType(
 *   id = "js_management_managed_js",
 *   label = @Translation("Managed JavaScript"),
 *   label_singular = @Translation("Managed JavaScript"),
 *   label_plural = @Translation("Managed JavaScript"),
 *   label_count = @PluralTranslation(
 *     singular = "@count script",
 *     plural = "@count scripts",
 *   ),
 *   base_table = "js_management_managed_js",
 *   data_table = "js_management_managed_js_data",
 *   admin_permission = "administer site settings",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "script_id",
 *     "label" = "name",
 *   },
 * )
 */
class JavaScriptManaged extends ContentEntityBase implements JavaScriptManagedInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(){
    return $this->get('data')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {
    $this->set('data', $data);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMinified() {
    return $this->get('minified')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMinified($minified) {
    $this->set('minified', $minified);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return $this->get('version')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setVersion($version) {
    $this->set('version', $version);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->set('type', $type);
    return $this;
  }

  public function getLoad() {
    return $this->get('load')->value;
  }

  public function setLoad($load) {
    $this->set('load', $load);
    return $this;
  }

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Name of managed JavaScript entity'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['data'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Data'))
      ->setDescription(t('Data of managed JavaScript entity'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['minified'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Minified'))
      ->setDescription(t('Minified status of Managed JavaScript'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'type' => 'boolean_checkbox',
        'weight' => 2,
        'disabled' => TRUE,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Version'))
      ->setDescription(t('Version of managed JavaScript entity'))
      ->setDefaultValue('1.0.0')
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('Type of managed JavaScript entity'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['load'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Load Script'))
      ->setDescription(t('Whether to load JavaScript.'))
      ->setDefaultValue(1)
      ->setDisplayOptions('view', [
        'type' => 'boolean_checkbox',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', FALSE);

    return $fields;
  }
}
