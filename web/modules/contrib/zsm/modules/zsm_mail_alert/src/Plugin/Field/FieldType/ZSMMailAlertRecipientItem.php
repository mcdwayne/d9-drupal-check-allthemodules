<?php

namespace Drupal\zsm_mail_alert\Plugin\Field\FieldType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;

/**
 * Field type "zsm_mail_alert_recipient".
 *
 * @FieldType(
 *   id = "zsm_mail_alert_recipient",
 *   label = @Translation("ZSM: MailAlert Recipient"),
 *   description = @Translation("MailAlert Recipient field."),
 *   category = @Translation("ZSM"),
 *   default_widget = "zsm_mail_alert_recipient_default",
 *   default_formatter = "zsm_mail_alert_recipient_default",
 * )
 */
class ZSMMailAlertRecipientItem extends FieldItemBase implements FieldItemInterface {
    /**
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field_definition) {
        $output = array();
        $output['columns']['recipient'] = array(
            'type' => 'text',
            'size' => 'medium',
        );
        $output['columns']['severity'] = array(
            'type' => 'varchar',
            'length' => 255,
        );
        $output['columns']['severity_custom'] = array(
            'type' => 'varchar',
            'length' => 255,
        );
        return $output;
    }
    /**
     * {@inheritdoc}
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
        $properties['recipient'] = DataDefinition::create('string')
            ->setLabel(t('Recipient'));
        $properties['severity'] = DataDefinition::create('string')
            ->setLabel(t('Severity'));
        $properties['severity_custom'] = DataDefinition::create('string')
            ->setLabel(t('Custom Severity'));
        return $properties;
    }
    /**
     * {@inheritdoc}
     */
    public function isEmpty() {
        $item = $this->getValue();
        // Has the user entered any data?
        if (
            (isset($item['recipient']) && !empty($item['recipient'])) ||
            (isset($item['severity']) && !empty($item['severity']))
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