<?php

namespace Drupal\content_entity_builder\Plugin\BaseFieldConfig;

use Drupal\Core\Form\FormStateInterface;
use Drupal\content_entity_builder\ConfigurableBaseFieldConfigBase;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * DecimalItemBaseFieldConfig.
 *
 * @BaseFieldConfig(
 *   id = "decimal_base_field_config",
 *   label = @Translation("Number (decimal)"),
 *   description = @Translation("This field stores a number in the database in a fixed decimal format."),
 *   field_type = "decimal",
 * )
 */
class DecimalItemBaseFieldConfig extends ConfigurableBaseFieldConfigBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'precision' => 10,
      'scale' => 2,
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
    $has_data = $form_state->getValue('has_data');
    $applied = $this->isApplied();

    $form['precision'] = [
      '#type' => 'number',
      '#title' => t('Precision'),
      '#min' => 10,
      '#max' => 32,
      '#default_value' => $this->configuration['precision'],
      '#description' => t('The total number of digits to store in the database, including those to the right of the decimal.'),
      '#disabled' => ($has_data && $applied),
    ];
    $form['scale'] = [
      '#type' => 'number',
      '#title' => t('Scale', [], ['context' => 'decimal places']),
      '#min' => 0,
      '#max' => 10,
      '#default_value' => $this->configuration['scale'],
      '#description' => t('The number of digits to the right of the decimal.'),
      '#disabled' => ($has_data && $applied),
    ];
    $form['min'] = [
      '#type' => 'number',
      '#title' => t('Minimum'),
      '#default_value' => $this->configuration['min'],
      '#description' => t('The minimum value that should be allowed in this field. Leave blank for no minimum.'),
      '#step' => pow(0.1, $this->configuration['scale']),
    ];
    $form['max'] = [
      '#type' => 'number',
      '#title' => t('Maximum'),
      '#default_value' => $this->configuration['max'],
      '#description' => t('The maximum value that should be allowed in this field. Leave blank for no maximum.'),
      '#step' => pow(0.1, $this->configuration['scale']),
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
    $this->configuration['precision'] = $form_state->getValue('precision');
    $this->configuration['scale'] = $form_state->getValue('scale');
    $this->configuration['min'] = $form_state->getValue('min');
    $this->configuration['max'] = $form_state->getValue('max');
    $this->configuration['prefix'] = $form_state->getValue('prefix');
    $this->configuration['suffix'] = $form_state->getValue('suffix');
  }

  /**
   * {@inheritdoc}
   */
  public function buildBaseFieldDefinition() {
    $label = $this->getLabel();
    $weight = $this->getWeight();
    $default_value = $this->getDefaultValue();
    $required = $this->isRequired();
    $description = $this->getDescription();

    $base_field_definition = BaseFieldDefinition::create("decimal")
      ->setLabel($label)
      ->setDescription($description)
      ->setRequired($required)
      ->setDefaultValue($default_value)
      ->setSetting('precision', $this->configuration['precision'])
      ->setSetting('scale', $this->configuration['scale'])
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
      '#step' => pow(0.1, $this->configuration['scale']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function exportCode() {
  $template = <<<Eof

    \$fields['@field_name'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('@label'))
      ->setDescription(t('@description'))
      ->setDefaultValue(@default_value)
      ->setRequired(@required)
      ->setSetting('precision', @precision)
      ->setSetting('scale', @scale)
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
      "@precision" => $this->configuration['precision'],
      "@scale" => $this->configuration['scale'],
    ));
	
    return $ret;
  }

}
