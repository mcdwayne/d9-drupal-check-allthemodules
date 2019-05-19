<?php

namespace Drupal\zsm_spectra_reporter\Plugin\Field\FieldType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;

/**
 * Field type "zsm_spectra_reporter_grouped_data".
 *
 * @FieldType(
 *   id = "zsm_spectra_reporter_grouped_data",
 *   label = @Translation("Spectra Reporter Grouped Data"),
 *   description = @Translation("Spectra Reporter Grouped Data field."),
 *   category = @Translation("ZSM"),
 *   default_widget = "zsm_spectra_reporter_grouped_data_default",
 *   default_formatter = "zsm_spectra_reporter_grouped_data_default",
 * )
 */
class ZSMSpectraReporterGroupedDataItem extends FieldItemBase implements FieldItemInterface {
    /**
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field_definition) {
        $output = array();
        $output['columns']['group'] = array(
            'type' => 'varchar',
            'length' => 255,
        );
        $output['columns']['keys'] = array(
            'type' => 'text',
            'size' => 'medium',
        );
        return $output;
    }
    /**
     * {@inheritdoc}
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
        $properties['group'] = DataDefinition::create('string')
            ->setLabel(t('Group'));
        $properties['keys'] = DataDefinition::create('string')
            ->setLabel(t('Keys'));
        return $properties;
    }
    /**
     * {@inheritdoc}
     */
    public function isEmpty() {
        $item = $this->getValue();
        // Has the user entered any data?
        if (
            (isset($item['group']) && !empty($item['group'])) &&
            (isset($item['keys']) && !empty($item['keys']))
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