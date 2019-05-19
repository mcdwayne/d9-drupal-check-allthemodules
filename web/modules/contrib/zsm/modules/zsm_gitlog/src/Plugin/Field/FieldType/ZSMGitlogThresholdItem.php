<?php

namespace Drupal\zsm_gitlog\Plugin\Field\FieldType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;

/**
 * Field type "zsm_gitlog_threshold".
 *
 * @FieldType(
 *   id = "zsm_gitlog_threshold",
 *   label = @Translation("ZSM: Gitlog Threshold"),
 *   description = @Translation("Gitlog Threshold field."),
 *   category = @Translation("ZSM"),
 *   default_widget = "zsm_gitlog_threshold_default",
 *   default_formatter = "zsm_gitlog_threshold_default",
 * )
 */
class ZSMGitlogThresholdItem extends FieldItemBase implements FieldItemInterface {
    /**
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field_definition) {
        $output = array();
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
            (isset($item['severity']) && $item['severity'] === 'report') ||
            (isset($item['severity_custom']) && !empty($item['severity_custom']))
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