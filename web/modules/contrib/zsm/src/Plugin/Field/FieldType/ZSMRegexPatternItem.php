<?php

namespace Drupal\zsm\Plugin\Field\FieldType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;

/**
 * Field type "zsm_regex_pattern".
 *
 * @FieldType(
 *   id = "zsm_regex_pattern",
 *   label = @Translation("ZSM: Regex Pattern"),
 *   description = @Translation("Custom regex pattern field."),
 *   category = @Translation("ZSM"),
 *   default_widget = "zsm_regex_pattern_default",
 *   default_formatter = "zsm_regex_pattern_default",
 * )
 */
class ZSMRegexPatternItem extends FieldItemBase implements FieldItemInterface {
    /**
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field_definition) {
        $output = array();
        // Create basic column for the section.
        $output['columns']['type'] = array(
            'type' => 'varchar',
            'length' => 255,
        );
        // Make a column for the list items.
        $output['columns']['location'] = array(
            'type' => 'varchar',
            'length' => 511,
        );
       $output['columns']['pattern'] = array(
            'type' => 'varchar',
            'length' => 511,
        );
        return $output;
    }
    /**
     * {@inheritdoc}
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
        $properties['type'] = DataDefinition::create('string')
            ->setLabel(t('Type'))
            ->setRequired(FALSE);
        $properties['location'] = DataDefinition::create('string')
            ->setLabel('Location');
        $properties['pattern'] = DataDefinition::create('string')
            ->setLabel('RegEx Pattern');
        return $properties;
    }
    /**
     * {@inheritdoc}
     */
    public function isEmpty() {
        $item = $this->getValue();
        if (
            (isset($item['type']) && !empty($item['type'])) ||
            (isset($item['location']) && !empty($item['location'])) ||
            (isset($item['pattern']) && !empty($item['pattern']))) {
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