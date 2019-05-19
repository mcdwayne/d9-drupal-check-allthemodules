<?php

namespace Drupal\zsm_mail_alert\Plugin\Field\FieldType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;

/**
 * Field type "zsm_mail_alert_message_component".
 *
 * @FieldType(
 *   id = "zsm_mail_alert_message_component",
 *   label = @Translation("ZSM: MailAlert Message Component"),
 *   description = @Translation("MailAlert Message Component field."),
 *   category = @Translation("ZSM"),
 *   default_widget = "zsm_mail_alert_message_component_default",
 *   default_formatter = "zsm_mail_alert_message_component_default",
 * )
 */
class ZSMMailAlertMessageComponentItem extends FieldItemBase implements FieldItemInterface {
    /**
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field_definition) {
        $output = array();
        $output['columns']['component_type'] = array(
            'type' => 'varchar',
            'length' => 255,
        );
        $output['columns']['component_fixed_data'] = array(
            'type' => 'text',
            'size' => 'medium',
        );
        $output['columns']['component_variable_data'] = array(
            'type' => 'varchar',
            'length' => 255,
        );
        return $output;
    }
    /**
     * {@inheritdoc}
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
      $properties['component_type'] = DataDefinition::create('string')
        ->setLabel(t('Component Type'));
      $properties['component_fixed_data'] = DataDefinition::create('string')
        ->setLabel(t('Fixed Value'));
      $properties['component_variable_data'] = DataDefinition::create('string')
        ->setLabel(t('Data from the Alert'));
      return $properties;
    }
    /**
     * {@inheritdoc}
     */
    public function isEmpty() {
        $item = $this->getValue();
        // Has the user entered any data?
        if (
          isset($item['component_fixed_data']) && !empty($item['component_fixed_data']) ||
          isset($item['component_variable_data']) && !empty($item['component_variable_data'])
        ) {
            return FALSE;
        }
        return TRUE;
    }
    /**
     * {@inheritdoc}
     */
    public static function defaultFieldSettings() {
        return parent::defaultFieldSettings();
    }
    /**
     * {@inheritdoc}
     */
    public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
        return $form;
    }
    /**
     * Returns an array.
     *
     * @return array
     *   An associative array
     */
    public function getSectionList() {
        $output = array();
        return $output;
    }
}