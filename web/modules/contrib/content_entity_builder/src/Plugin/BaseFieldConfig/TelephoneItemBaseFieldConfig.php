<?php

namespace Drupal\content_entity_builder\Plugin\BaseFieldConfig;

use Drupal\Core\Form\FormStateInterface;
use Drupal\content_entity_builder\ConfigurableBaseFieldConfigBase;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * TelephoneItemBaseFieldConfig.
 *
 * @BaseFieldConfig(
 *   id = "telephone_base_field_config",
 *   label = @Translation("Telephone number"),
 *   description = @Translation("This field stores a telephone number in the database."),
 *   field_type = "telephone",
 *   dependency = "telephone",
 *   category = @Translation("Number")
 * )
 */
class TelephoneItemBaseFieldConfig extends ConfigurableBaseFieldConfigBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildBaseFieldDefinition() {
    $field_type = $this->getFieldType();
    $label = $this->getLabel();
    $weight = $this->getWeight();
    $default_value = $this->getDefaultValue();
    $required = $this->isRequired();
    $description = $this->getDescription();

    $base_field_definition = BaseFieldDefinition::create($field_type)
      ->setLabel($label)
      ->setDescription($description)
      ->setRequired($required)
      ->setDefaultValue($default_value)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => $weight,
      ])
      ->setDisplayOptions('form', [
        'type' => 'telephone_default',
        'weight' => $weight,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $base_field_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function buildDefaultValueForm(array $form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'tel',
      '#title' => $this->getFieldName(),
      '#default_value' => $this->getDefaultValue(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function exportCode() {
  $template = <<<Eof

    \$fields['@field_name'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('@label'))
      ->setDescription(t('@description'))
      ->setRequired(@required)
      ->setDefaultValue('@default_value')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => @weight,
      ])
      ->setDisplayOptions('form', [
        'type' => 'telephone_default',
        'weight' => @weight,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);  

Eof;

    $ret = format_string($template, array(
      "@field_name" => $this->getFieldName(),
      "@label" => $this->getLabel(),
      "@description" => $this->getDescription(),
	  "@default_value" => $this->getDefaultValue(),
      "@required" => !empty($this->isRequired()) ? "TRUE" : "FALSE",
      "@weight" => $this->getWeight(),
    ));
	
    return $ret;
  }

}
