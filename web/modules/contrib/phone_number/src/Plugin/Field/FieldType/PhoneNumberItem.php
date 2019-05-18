<?php

namespace Drupal\phone_number\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\phone_number\PhoneNumberUtilInterface;

/**
 * Plugin implementation of the 'phone_number' field type.
 *
 * @FieldType(
 *   id = "phone_number",
 *   label = @Translation("Phone Number"),
 *   description = @Translation("Stores international number, local number, and country code for phone numbers."),
 *   default_widget = "phone_number_default",
 *   default_formatter = "phone_number_international",
 *   constraints = {
 *     "PhoneNumber" = {}
 *   }
 * )
 */
class PhoneNumberItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'unique' => PhoneNumberUtilInterface::PHONE_NUMBER_UNIQUE_NO,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return parent::defaultFieldSettings() + [
      'allowed_countries' => NULL,
      'allowed_types' => NULL,
      'extension_field' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 19,
          'not null' => TRUE,
          'default' => '',
        ],
        'country' => [
          'type' => 'varchar',
          'length' => 3,
          'not null' => TRUE,
          'default' => '',
        ],
        'local_number' => [
          'type' => 'varchar',
          'length' => 15,
          'not null' => TRUE,
          'default' => '',
        ],
        'extension' => [
          'description' => "The phone number's extension.",
          'type' => 'varchar',
          'length' => 40,
          'default' => NULL,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->getValue();
    return empty($value['value']) && empty($value['local_number']);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('E.165 Number'))
      ->addConstraint('Length', ['max' => 19]);

    $properties['country'] = DataDefinition::create('string')
      ->setLabel(t('Country Code'))
      ->addConstraint('Length', ['max' => 3]);

    $properties['local_number'] = DataDefinition::create('string')
      ->setLabel(t('Local Number'))
      ->addConstraint('Length', ['max' => 15]);

    $properties['extension'] = DataDefinition::create('string')
      ->setLabel(t('Extension'))
      ->addConstraint('Length', ['max' => 40]);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    /** @var \Drupal\phone_number\PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');
    $values = $this->getValue();

    $number = NULL;
    $country = NULL;
    $extension = NULL;

    if (!empty($values['country'])) {
      if (!empty($values['local_number'])) {
        $number = $values['local_number'];
      }
      $country = $values['country'];
    }

    if (!$number) {
      $number = $values['value'];
    }

    if (!empty($values['extension'])) {
      $extension = $values['extension'];
    }

    if ($phone_number = $util->getPhoneNumber($number, $country)) {
      $this->value = $util->getCallableNumber($phone_number);
      $this->country = $util->getCountry($phone_number);
      $this->local_number = $util->getLocalNumber($phone_number, TRUE);
      $this->extension = $extension;
    }
    else {
      $this->value = NULL;
      $this->local_number = NULL;
      $this->extension = NULL;
    }

    parent::preSave();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    /** @var \Drupal\phone_number\PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');

    $settings = $this->getSettings();

    $element = [];

    $element['unique'] = [
      '#type' => 'radios',
      '#title' => t('Unique'),
      '#options' => [
        $util::PHONE_NUMBER_UNIQUE_NO => t('No'),
        $util::PHONE_NUMBER_UNIQUE_YES => t('Yes'),
      ],
      '#default_value' => $settings['unique'],
      '#description' => t('Should phone numbers be unique within this field.'),
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\phone_number\PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');

    $settings = $this->getSettings();

    $element['allowed_countries'] = [
      '#type' => 'select',
      '#title' => t('Allowed Countries'),
      '#options' => $util->getCountryOptions(NULL, TRUE),
      '#default_value' => $settings['allowed_countries'],
      '#description' => t('Allowed counties for the phone number. If none selected, then all are allowed.'),
      '#multiple' => TRUE,
      '#attached' => ['library' => ['phone_number/element']],
    ];

    $element['allowed_types'] = [
      '#type' => 'select',
      '#title' => t('Allowed Types'),
      '#options' => $util->getTypeOptions(),
      '#default_value' => $settings['allowed_types'],
      '#description' => t('Restrict entry to certain types of phone numbers. If none are selected, then all types are allowed.  A description of each type can be found <a href="@url" target="_blank">here</a>.', [
        '@url' => 'https://github.com/giggsey/libphonenumber-for-php/blob/master/src/PhoneNumberType.php',
      ]),
      '#multiple' => TRUE,
    ];

    $element['extension_field'] = [
      '#type' => 'checkbox',
      '#title' => $this
        ->t('Enable <em>Extension</em> field'),
      '#default_value' => $settings['extension_field'],
      '#description' => $this
        ->t('Collect extension along with the phone number.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    /** @var \Drupal\phone_number\PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');

    $settings = $field_definition
      ->getSettings();
    $allowed_countries = $settings['allowed_countries'];
    if (empty($allowed_countries)) {
      $allowed_countries = array_keys($util->getCountryOptions());
    }
    $allowed_types = $settings['allowed_types'];
    if (empty($allowed_types)) {
      $allowed_types = array_keys($util->getTypeOptions());
    }

    static $last_numbers = [];

    $country = array_rand($allowed_countries);
    $type = array_rand($allowed_types);
    $last = !empty($last_numbers[$country]) ? $last_numbers[$country] : [];
    $phone_number = NULL;
    if (!$last) {
      $last['count'] = 0;
      $last['example'] = ($number = $util->libUtil()->getExampleNumberForType($country, $type)) ? $number->getNationalNumber() : NULL;
    }
    $example = $last['example'];
    $count = $last['count'];
    if ($example) {
      while ((strlen($count) <= strlen($example)) && !$phone_number) {
        $number_length = strlen($example);
        $number = substr($example, 0, $number_length - strlen($count)) . $count;
        if (substr($count, 0, 1) != substr($example, strlen($count) - 1, 1)) {
          $phone_number = $util->getPhoneNumber($number, $country);
        }
        $count = ($count + 1) % pow(10, strlen($example));
      };
    }
    $value = [];
    if ($phone_number) {
      $value = [
        'value' => $util->getCallableNumber($phone_number),
      ];

      if ($settings['extension_field']) {
        $value['extension'] = rand(0, 100);
      }
    }

    return $value;
  }

  /**
   * Get phone number object of the current item.
   *
   * @param bool $throw_exception
   *   Whether to throw phone number validity exceptions.
   *
   * @return \libphonenumber\PhoneNumber|null
   *   Phone number object, or null if not valid.
   */
  public function getPhoneNumber($throw_exception = FALSE) {
    /** @var \Drupal\phone_number\PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');
    $values = $this->getValue();
    $number = '';
    $country = NULL;
    $extension = NULL;
    if (!empty($values['country'])) {
      if (!empty($values['local_number'])) {
        $number = $values['local_number'];
      }
      $country = $values['country'];
    }

    if (!$number && !empty($values['value'])) {
      $number = $values['value'];
    }

    if (!empty($values['extension'])) {
      $extension = $values['extension'];
    }

    if ($throw_exception) {
      return $util->testPhoneNumber($number, $country, $extension);
    }
    else {
      return $util->getPhoneNumber($number, $country, $extension);
    }

  }

  /**
   * Is phone number unique within the entity/field.
   *
   * @return bool|null
   *   TRUE for is unique, false otherwise. Null if phone number is not valid.
   */
  public function isUnique() {
    /** @var \Drupal\phone_number\PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');

    $entity = $this->getEntity();
    $field_name = $this->getFieldDefinition()->getName();

    if (!$phone_number = $this->getPhoneNumber()) {
      return NULL;
    }
    $entity_type_id = $entity->getEntityTypeId();
    $id_key = $entity->getEntityType()->getKey('id');
    $query = \Drupal::entityQuery($entity_type_id)
      // The id could be NULL, so we cast it to 0 in that case.
      ->condition($id_key, (int) $entity->id(), '<>')
      ->condition($field_name, $util->getCallableNumber($phone_number))
      ->range(0, 1)
      ->count();

    return !(bool) $query->execute();
  }

  /**
   * Get all country options.
   *
   * @return array
   *   Array of countries, with country codes as keys and country names with
   *   prefix as labels.
   */
  public static function countryOptions() {
    /** @var \Drupal\phone_number\PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');
    return $util->getCountryOptions(NULL, TRUE);
  }

  /**
   * Boolean options for views.
   *
   * Because views' default boolean handler is ridiculous.
   *
   * @return array
   *   Array of 0 => No, 1 => Yes. As it should be.
   */
  public static function booleanOptions() {
    return [t('No'), t('Yes')];
  }

}
