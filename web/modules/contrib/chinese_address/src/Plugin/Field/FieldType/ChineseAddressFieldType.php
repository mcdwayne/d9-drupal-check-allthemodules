<?php

namespace Drupal\chinese_address\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\chinese_address\chineseAddressHelper;

/**
 * Plugin implementation of the 'chinese_address_field_type' field type.
 *
 * @FieldType(
 * id = "chinese_address_field_type",
 * label = @Translation("Chinese Address"),
 * description = @Translation("Chinese Address Field"),
 * module = "chinese_address",
 * default_widget = "chinese_address_widget_type",
 * default_formatter = "chinese_address_formatter_type"
 * )
 */
class ChineseAddressFieldType extends FieldItemBase
{

    /**
   * {@inheritdoc}
  */
    Public static function defaultStorageSettings() 
    {
        return [
        'has_detail' => true,
        'has_street' => true,
        'province_limit' => array(),
        ] + parent::defaultStorageSettings();
    }


    /**
   * {@inheritdoc}
   */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) 
    {
        // Prevent early t() calls by using the TranslatableMarkup.
        $properties = [
        'province' => DataDefinition::create('integer')->setLabel(t('Province')),
        'city' => DataDefinition::create('integer')->setLabel(t('City')),
        'county' => DataDefinition::create('integer')->setLabel(t('County')),
        'street' => DataDefinition::create('integer')->setLabel(t('Street')),
        'detail' => DataDefinition::create('string')->setLabel(t('Detail')),
        ];
        return $properties;
    }

    /**
   * {@inheritdoc}
   */
    public static function schema(FieldStorageDefinitionInterface $field_definition) 
    {
        $schema = [
        'columns' => [
        'province' => [
          'type' => 'int',
          'size' => 'big',
          'not null' => false,
          'default' => chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX,
        ],
        'city' => [
          'type' => 'int',
          'size' => 'big',
        'not null' => false,
          'default' => chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX,
        ],
        'county' => [
          'type' => 'int',
          'size' => 'big',
        'not null' => false,
          'default' => chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX,
        ],
        'street' => [
          'type' => 'int',
          'size' => 'big',
        'not null' => false,
          'default' => chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX,
        ],
        'detail' => [
          'type' => 'varchar',
          'length' => 255,
        'not null' => false,
        ],
        ],
        'indexes' => [
        'province' => [
          'province',
        ],
        'city' => [
          'city',
        ],
        'county' => [
          'county',
        ],
        'street' => [
          'street',
        ],
        ],
        ];

        return $schema;
    }
    /**
     * {@inheritdoc}
     */
    public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) 
    {
        $element = [];
      
        $element['has_street'] = array(
        '#title' => t('Need Street Field?'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('has_street'),
        );
      
        $element['has_detail'] = array(
        '#title' => t('Need Detail Field?'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('has_detail'),
        );
      
        $element['province_limit'] = array(
        '#title' => t('Limit Province?'),
        '#type' => 'select',
        '#options' =>  chineseAddressHelper::chinese_address_get_location(chineseAddressHelper::CHINESE_ADDRESS_ROOT_INDEX, true),
        '#default_value' => $this->getSetting('province_limit'),
        "#multiple" => true,
        '#description'=>t('如果限定为一个,那省份的选项则会被隐藏,按住CTRL进行多选,若要所有地区则留空'),
        );
      
      
        return $element;
    }

    /**
   * {@inheritdoc}
   */
    public function isEmpty() 
    {
        $province = $this->get('province')->getValue();
        $city = $this->get('city')->getValue();
        $county = $this->get('county')->getValue();
        $street = $this->get('street')->getValue();
        return ($province == chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX  &&  $city==  chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX  &&  $county==  chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX &&  $street ==  chineseAddressHelper::CHINESE_ADDRESS_NULL_INDEX  );
    }

}
