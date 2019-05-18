<?php

namespace Drupal\idproof\Plugin\Field\FieldType;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 * @FieldType(
 *   id = "idproof",
 *   label = @Translation("ID Proof"),
 *   description = @Translation("This field allows input different types of id proof."),
 *   default_widget = "idproof_widget",
 *   default_formatter = "idproof_formatter"
 * )
 */

 class IDproofField extends FieldItemBase {

   /**
    * Field type properties definition.
    *
    * Inside this method we defines all the fields (properties) that our
    * custom field type will have.
    */

    /**
     * {@inheritdoc}
     */
    public static function defaultStorageSettings() {
      return array(
      ) + parent::defaultStorageSettings();
    }

    /**
     * {@inheritdoc}
     */
    public static function defaultFieldSettings() {
      return array(
      ) + parent::defaultFieldSettings();
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
      $properties = [];
      $properties['idproof'] = DataDefinition::create('string')->setLabel(t('ID Proof'));
      $properties['idproofother'] = DataDefinition::create('string')->setLabel(t('ID Proof Other'));
      $properties['iddetails'] = DataDefinition::create('string')->setLabel(t('ID Details'));
      return $properties;
    }

    public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
      $element = array();
      // We need the field-level setting, and $this->getSettings()
      // will only provide the instance-level one, so we need to explicitly fetch
      // the field.
      $settings = $this->getFieldDefinition()->getFieldStorageDefinition()->getSettings();
      return $element;
    }

    /**
     * Field type schema definition.
     *
     * Inside this method we defines the database schema used to store data for
     * our field type.
     */
    public static function schema(FieldStorageDefinitionInterface $field_definition) {
      return array(
        'columns' => array(
          'idproof' => array(
            'type' => 'text',
          ),
          'idproofother' => array(
            'type' => 'varchar',
            'length' => 255,
            'not null' => FALSE,
          ),
          'iddetails' => array(
            'type' => 'varchar',
            'length' => 255,
            'not null' => FALSE,
          ),
        ),
      );
    }

    /**
     * Define when the field type is empty.
     *
     * This method is important and used internally by Drupal.
     */
    public function isEmpty() {
      $idproof = $this->get('idproof')->getValue();
      $idproof_other = $this->get('idproofother')->getValue();
      $iddetails = $this->get('iddetails')->getValue();
      $isEmpty = empty($idproof) || empty($iddetails);
      if($idproof == "Other") {
        $isEmpty = empty($idproof) || empty($iddetails) || empty($idproof_other);
      }
      return $isEmpty;
    }

    /**
     * {@inheritdoc}
     */
    public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
      $element = [];

      $settings = $this->getSettings();

      return $element;
    }

  }
