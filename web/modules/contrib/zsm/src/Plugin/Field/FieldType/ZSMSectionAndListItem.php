<?php

namespace Drupal\zsm\Plugin\Field\FieldType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;

/**
 * Field type "zsm_section_and_list".
 *
 * @FieldType(
 *   id = "zsm_section_and_list",
 *   label = @Translation("ZSM: Section and List"),
 *   description = @Translation("Custom section-list field."),
 *   category = @Translation("ZSM"),
 *   default_widget = "zsm_section_and_list_default",
 *   default_formatter = "zsm_section_and_list_default",
 * )
 */
class ZSMSectionAndListItem extends FieldItemBase implements FieldItemInterface {
    /**
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field_definition) {
        $output = array();
        // Create basic column for the section.
        $output['columns']['section'] = array(
            'type' => 'varchar',
            'length' => 255,
        );
        // Make a column for the list items.
        $output['columns']['list'] = array(
            'type' => 'text',
            'size' => 'medium',
        );
        return $output;
    }
    /**
     * {@inheritdoc}
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
        $properties['section'] = DataDefinition::create('string')
            ->setLabel(t('Name'))
            ->setRequired(FALSE);
        $properties['list'] = DataDefinition::create('string')
            ->setLabel('list');
        return $properties;
    }
    /**
     * {@inheritdoc}
     */
    public function isEmpty() {
        $item = $this->getValue();
        if ((isset($item['section']) && !empty($item['section'])) || (isset($item['list']) && !empty($item['list']))) {
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