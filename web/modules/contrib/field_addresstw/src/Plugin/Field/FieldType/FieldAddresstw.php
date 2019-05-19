<?php

namespace Drupal\field_addresstw\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_addresstw' field type.
 *
 * @FieldType(
 *   id = "field_addresstw",
 *   label = @Translation("Taiwan Address Field"),
 *   module = "field_addresstw",
 *   description = @Translation("Create an address field for taiwan and can choose the county, district, zipcode."),
 *   default_widget = "FieldAddresstwWidget",
 *   default_formatter = "FieldAddresstwFormatter"
 * )
 */
class FieldAddresstw extends FieldItemBase  {
   
    /**
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field_definition) {
        return array(
            // columns contains the values that the field will store
            'columns' => array(
                'county' => array('type' => 'varchar', 'length' => 6, 'not null' => FALSE), //縣市
                'district' => array('type' => 'varchar', 'length' => 6, 'not null' => FALSE), //區域
                'zipcode' => array('type' => 'varchar', 'length' => 6, 'not null' => FALSE), //郵遞區號
                'addresstw' => array('type' => 'varchar', 'length' => 30, 'not null' => FALSE), //地址
            ),
            'index' => array(
                'addresstw' => array('addresstw'),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
        $properties = [];
        $properties['county'] = DataDefinition::create('string')->setLabel(t('county'));
        $properties['district'] = DataDefinition::create('string')->setLabel(t('district'));
        $properties['zipcode'] = DataDefinition::create('string')->setLabel(t('zipcode'));
        $properties['addresstw'] = DataDefinition::create('string')->setLabel(t('addresstw'));
    
        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty() {
        $addresstw = $this->get('addresstw')->getValue();
        $zipcode = $this->get('zipcode')->getValue();
        $district = $this->get('district')->getValue();
        $county = $this->get('county')->getValue();
        return empty($addresstw) && empty($zipcode) && empty($district) && empty($county);
    }
}