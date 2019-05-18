<?php

namespace Drupal\content_entity_builder\Plugin\BaseFieldConfig;

use Drupal\Core\Form\FormStateInterface;
use Drupal\content_entity_builder\ConfigurableBaseFieldConfigBase;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * StringItemBaseFieldConfig.
 *
 * @BaseFieldConfig(
 *   id = "string_base_field_config",
 *   label = @Translation("Text (plain)"),
 *   description = @Translation("A field containing a plain string value."),
 *   field_type = "string",
 *   category = @Translation("Text")
 * )
 */
class StringItemBaseFieldConfig extends ConfigurableBaseFieldConfigBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'max_length' => 255,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $has_data = $form_state->getValue('has_data');
    $applied = $this->isApplied();

    $form['max_length'] = [
      '#type' => 'number',
      '#title' => t('Maximum length'),
      '#default_value' => $this->configuration['max_length'],
      '#required' => TRUE,
      '#description' => t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => ($has_data && $applied),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['max_length'] = $form_state->getValue('max_length');
  }

  /**
   * {@inheritdoc}
   */
  public function buildBaseFieldDefinition() {
    $field_type = $this->getFieldType();
    $label = $this->getLabel();
    $description = $this->getDescription();
    $weight = $this->getWeight();
    $default_value = $this->getDefaultValue();
    $required = $this->isRequired();

    $base_field_definition = BaseFieldDefinition::create($field_type)
      ->setLabel($label)
      ->setDescription($description)
      ->setDefaultValue($default_value)
      ->setRequired($required)
      ->setSetting('max_length', $this->configuration['max_length'])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => $weight,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => $weight,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $base_field_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function exportCode() {
  $template = <<<Eof

    \$fields['@field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('@label'))
      ->setDescription(t('@description'))
      ->setDefaultValue('@default_value')
      ->setRequired(@required)
      ->setSetting('max_length', @max_length)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => @weight,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
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
      "@max_length" => $this->configuration['max_length'],
      "@weight" => $this->getWeight(),
    ));
	
    return $ret;
  }

}
