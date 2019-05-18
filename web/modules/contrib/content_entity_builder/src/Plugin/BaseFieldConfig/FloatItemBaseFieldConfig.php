<?php

namespace Drupal\content_entity_builder\Plugin\BaseFieldConfig;

use Drupal\Core\Form\FormStateInterface;
use Drupal\content_entity_builder\ConfigurableBaseFieldConfigBase;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * FloatItemBaseFieldConfig.
 *
 * @BaseFieldConfig(
 *   id = "float_base_field_config",
 *   label = @Translation("Number (float)"),
 *   description = @Translation("This field stores a number in the database in a floating point format."),
 *   field_type = "float",
 *   category = @Translation("Number"),
 * )
 */
class FloatItemBaseFieldConfig extends ConfigurableBaseFieldConfigBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'min' => '',
      'max' => '',
      'prefix' => '',
      'suffix' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['min'] = [
      '#type' => 'number',
      '#title' => t('Minimum'),
      '#default_value' => $this->configuration['min'],
      '#description' => t('The minimum value that should be allowed in this field. Leave blank for no minimum.'),
      '#step' => 'any',
    ];
    $form['max'] = [
      '#type' => 'number',
      '#title' => t('Maximum'),
      '#default_value' => $this->configuration['max'],
      '#description' => t('The maximum value that should be allowed in this field. Leave blank for no maximum.'),
      '#step' => 'any',
    ];

    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => t('Prefix'),
      '#default_value' => $this->configuration['prefix'],
      '#description' => t("Define a string that should be prefixed to the value, like '$ ' or '€ '. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    ];
    $form['suffix'] = [
      '#type' => 'textfield',
      '#title' => t('Suffix'),
      '#default_value' => $this->configuration['suffix'],
      '#description' => t("Define a string that should be suffixed to the value, like ' m', ' kb/s'. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['min'] = $form_state->getValue('min');
    $this->configuration['max'] = $form_state->getValue('max');
    $this->configuration['prefix'] = $form_state->getValue('prefix');
    $this->configuration['suffix'] = $form_state->getValue('suffix');
  }

  /**
   * {@inheritdoc}
   */
  public function buildBaseFieldDefinition() {
    // $field_type = $this->getFieldType();
    $label = $this->getLabel();
    $weight = $this->getWeight();
    $default_value = $this->getDefaultValue();
    $required = $this->isRequired();
    $description = $this->getDescription();

    $base_field_definition = BaseFieldDefinition::create("float")
      ->setLabel($label)
      ->setDescription($description)
      ->setRequired($required)
      ->setDefaultValue($default_value)
      ->setSetting('min', $this->configuration['min'])
      ->setSetting('max', $this->configuration['max'])
      ->setSetting('prefix', $this->configuration['prefix'])
      ->setSetting('suffix', $this->configuration['suffix'])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => $weight,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
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
      '#type' => 'number',
      '#title' => $this->getFieldName(),
      '#default_value' => $this->getDefaultValue(),
      '#step' => 'any',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function exportCode() {
  $template = <<<Eof

    \$fields['@field_name'] = BaseFieldDefinition::create('float')
      ->setLabel(t('@label'))
      ->setDescription(t('@description'))
      ->setDefaultValue(@default_value)
      ->setRequired(@required)
      ->setSetting('min', @min)
      ->setSetting('max', @max)
      ->setSetting('prefix', '@prefix')
      ->setSetting('suffix', '@suffix')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => @weight,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => @weight,
      ])  
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

Eof;

    $min = $this->configuration['min'];
    if($min == ''){
	  $min = 'NULL'; 
    }
    $max = $this->configuration['max'];
    if($max == ''){
	  $max = 'NULL'; 
    }
    $default_value = $this->getDefaultValue();
    if($default_value == ''){
	  $default_value = 'NULL'; 
    }   
    $ret = format_string($template, array(
      "@field_name" => $this->getFieldName(),
      "@label" => $this->getLabel(),
      "@description" => $this->getDescription(),
	  "@default_value" => $default_value,
      "@required" => !empty($this->isRequired()) ? "TRUE" : "FALSE",
      "@weight" => $this->getWeight(),
      "@min" => $min,
      "@max" => $max,
      "@prefix" => $this->configuration['prefix'],
      "@suffix" => $this->configuration['suffix'],	  
    ));
	
    return $ret;
  }
  
}
