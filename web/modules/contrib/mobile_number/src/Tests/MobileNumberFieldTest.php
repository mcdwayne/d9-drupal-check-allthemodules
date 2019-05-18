<?php

namespace Drupal\mobile_number\Tests;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\Core\Language\Language;
use Drupal\field\Entity\FieldConfig;
use Drupal\mobile_number\MobileNumberUtilInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Mobile number field functionality.'.
 *
 * @group mobile_number
 */
class MobileNumberFieldTest extends WebTestBase {

  public static $modules = ['mobile_number', 'node'];

  /**
   * Mobile number util.
   *
   * @var \Drupal\mobile_number\MobileNumberUtilInterface
   */
  public $util;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  public $flood;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->util = \Drupal::service('mobile_number.util');
    $this->flood = \Drupal::service('flood');
  }

  /**
   * Test number validation.
   */
  public function testNumberValidity() {

    $local_numbers = [
      '0502345678' => 'Valid IL',
      '111' => 'Invalid IL',
      NULL => 'Empty',
    ];

    $countries = [
      'IL' => 'IL',
      'US' => 'US',
      NULL => 'Empty',
    ];

    $allowed_countries = [
      'IL' => ['IL' => 'IL'],
      'US' => ['US' => 'US'],
      'Mix' => ['US' => 'US', 'IL' => 'IL'],
      'All' => [],
    ];

    $input = [
      'country-code' => 'IL',
      'mobile' => '0502345678',
    ];
    $name = 'validity';
    $this->drupalCreateContentType(['type' => $name]);
    $this->createField($name, "field_$name", MobileNumberUtilInterface::MOBILE_NUMBER_UNIQUE_NO, 1);
    $user = $this->drupalCreateUser(["create $name content"]);
    $this->setCurrentUser($user);

    foreach ($allowed_countries as $type => $allowed) {
      foreach ($local_numbers as $ln => $local_number) {
        foreach ($countries as $c => $country) {
          $input['country-code'] = $c;
          $input['mobile'] = $ln;

          $this->updateFieldConfig($name, 'countries', $allowed);
          $errors = $this->createMobileNumberNodeFromInput($name, $input);
          $valid = '0502345678' == $ln && ($type == 'IL' || $type == 'All' || $type == 'Mix') && $c == 'IL';

          $success = $valid ? 'Success' : 'Failure';
          $this->assertTrue($valid == !$errors, "$country country, $local_number local number, allowed $type: $success. " . ($errors ? reset($errors) : ''), 'Number Validity');
        }
      }
    }
  }

  /**
   * Test number validation.
   */
  public function testNumberUniqueness() {
    $tries = [
      'New values',
      'Resubmit values',
    ];

    $value_count = [
      1 => 'One value',
      2 => 'Two values',
    ];

    $number_types = [
      'Unverified',
      'Verified',
    ];

    $unique_types = [
      MobileNumberUtilInterface::MOBILE_NUMBER_UNIQUE_YES => 'Unique',
      MobileNumberUtilInterface::MOBILE_NUMBER_UNIQUE_YES_VERIFIED => 'Unique verified',
    ];

    $names = [];
    foreach ($value_count as $count => $count_text) {
      foreach ($unique_types as $unique => $unique_text) {
        $name = 'unique_' . $unique . '_count_' . $count;
        $this->drupalCreateContentType(['type' => $name]);
        $this->createField($name, "field_$name", $unique, $count);
        $names[] = $name;
      }
    }

    $user = $this->drupalCreateUser(array_map(function ($element) {
      return "create $element content";
    }, $names));
    $this->setCurrentUser($user);

    // Check for inter-entity multi-value duplicates.
    foreach ($unique_types as $unique => $unique_text) {
      $name = 'unique_' . $unique . '_count_2';
      $count = 0;
      foreach ($number_types as $existing_verified => $existing_verified_text) {
        foreach ($number_types as $verified => $verified_text) {
          $this->createMobileNumberNode($name, "+9725422222$existing_verified$verified", $existing_verified);
          $errors = $this->createMobileNumberNode($name, "+9725422222$existing_verified$verified", $verified);
          $valid = FALSE;
          switch ($unique) {
            case MobileNumberUtilInterface::MOBILE_NUMBER_UNIQUE_YES_VERIFIED:
              $valid = ($verified + $existing_verified) < 2;
              break;

          }
          $valid_text = $valid ? 'is unique' : 'is not unique';
          $this->assertTrue($valid == !$errors, "Resubmit values, One value, $unique_text, $verified_text, existing = $existing_verified_text: $valid_text.", 'Number Uniqueness');
          $count++;
        }
      }
    }

    // Check for inter-entity single-value duplicates.
    foreach ($unique_types as $unique => $unique_text) {
      $name = 'unique_' . $unique . '_count_1';
      foreach ($number_types as $existing_verified => $existing_verified_text) {
        foreach ($number_types as $verified => $verified_text) {
          $number = "+9725433333$existing_verified$verified";
          $this->createMobileNumberNode($name, $number, $existing_verified);
          $errors = $this->createMobileNumberNode($name, $number, $verified);
          $valid = FALSE;
          switch ($unique) {
            case MobileNumberUtilInterface::MOBILE_NUMBER_UNIQUE_YES_VERIFIED:
              $valid = ($verified + $existing_verified) < 2;
              break;
          }
          $valid_text = $valid ? 'is unique' : 'is not unique';
          $this->assertTrue($valid == !$errors, "Resubmit values, One value, $unique_text, presaved = $existing_verified_text, new = $verified_text, $valid_text. " . ($errors ? reset($errors) : ''), 'Number Uniqueness');
        }
      }
    }
  }

  /**
   * Test number verification.
   */
  public function testVerification() {
    $number = '0502345678';
    $country = 'IL';
    $value = '+972502345678';
    $mobile_number = $this->util->getMobileNumber($value);
    $code = $this->util->generateVerificationCode();

    $required_name = 'ver_required';
    $this->drupalCreateContentType(['type' => $required_name]);
    $this->createField($required_name, "field_$required_name", MobileNumberUtilInterface::MOBILE_NUMBER_UNIQUE_NO, 1, MobileNumberUtilInterface::MOBILE_NUMBER_VERIFY_REQUIRED);
    $optional_name = 'ver_optional';
    $this->drupalCreateContentType(['type' => $optional_name]);
    $this->createField($optional_name, "field_$optional_name", MobileNumberUtilInterface::MOBILE_NUMBER_UNIQUE_NO, 1, MobileNumberUtilInterface::MOBILE_NUMBER_VERIFY_OPTIONAL);

    $tokens = [
      FALSE => 'Wrong token',
      NULL => 'No token',
      TRUE => 'Correct token',
    ];

    $codes = [
      '000' => 'Wrong code',
      NULL => 'No code',
      $code => 'Correct code',
    ];

    $user = $this->drupalCreateUser(["create $required_name content", "create $optional_name content"]);
    $admin = $this->drupalCreateUser(["create $required_name content", 'bypass mobile number verification requirement']);

    $input['country-code'] = $country;
    $input['mobile'] = $number;

    $this->setCurrentUser($admin);
    $errors = $this->createMobileNumberNode($required_name, $value, FALSE);
    $this->assertTrue(!$errors, "Admin bypass verification requirement.", 'Number Verification');

    $this->setCurrentUser($user);
    $errors = $this->createMobileNumberNode($required_name, $value, FALSE);
    $this->assertTrue($errors, "Bypass verification requirement blocked.", 'Number Verification');

    $errors = $this->createMobileNumberNode($optional_name, $value, FALSE);
    $this->assertTrue(!$errors, "Optional verification allowed unverified.", 'Number Verification');

    /** @var \Drupal\Core\Flood\FloodInterface $flood */
    $flood = \Drupal::service('flood');
    foreach ($tokens as $is_valid_token => $t) {
      foreach ($codes as $input_code => $c) {
        $input['country-code'] = $country;
        $input['mobile'] = $number;
        $input['verification_token'] = isset($is_valid_token) ? ($is_valid_token ? $this->util->registerVerificationCode($mobile_number, $code) : 'abc') : NULL;
        $input['verification_code'] = $input_code;
        $flood->clear('mobile_number_verification', $value);
        $errors = $this->createMobileNumberNodeFromInput($required_name, $input);

        $validated = ($is_valid_token) && ($code == $input_code);

        $valid_text = $validated ? 'verified' : 'not verified';
        $this->assertTrue($validated == !$errors, "$t, $c, is $valid_text. " . ($errors ? reset($errors) : ''), 'Number Verification');
      }
    }

    $input = [
      'country-code' => $country,
      'mobile' => $value,
    ];
    $_SESSION['mobile_number_verification'][$value]['verified'] = TRUE;
    $errors = $this->createMobileNumberNodeFromInput($required_name, $input);
    $this->assertTrue(!$errors, "Already verified number is verified." . ($errors ? reset($errors) : ''), 'Number Verification');

    $input = [
      'country-code' => $country,
      'mobile' => substr($number, 0, 9) . '0',
    ];
    $errors = $this->createMobileNumberNodeFromInput($required_name, $input);
    $this->assertTrue($errors, "Not yet verified number is not verified. " . ($errors ? reset($errors) : ''), 'Number Verification');
  }

  /**
   * Create node with mobile number(s).
   */
  public function createMobileNumberNode($name, $number, $verified, $verified2 = NULL) {
    $values = [];
    $values["field_$name"][0] = [
      'mobile' => $number,
      'country-code' => 'IL',
    ];
    $mobile_number = $this->util->getMobileNumber($number);
    if ($verified) {
      $values["field_$name"][0]['verification_code'] = $code = $this->util->generateVerificationCode();
      $values["field_$name"][0]['verification_token'] = $this->util->registerVerificationCode($mobile_number, $code);
    }
    if (isset($verified2)) {
      $values["field_$name"][1] = [
        'mobile' => $number,
        'country-code' => 'IL',
      ];
      if ($verified2) {
        $values["field_$name"][1]['verification_code'] = $code = $this->util->generateVerificationCode();
        $values["field_$name"][1]['verification_token'] = $this->util->registerVerificationCode($mobile_number, $code);
      }
    }

    return $this->submitNodeForm($name, $values, $number);
  }

  /**
   * Create node with mobile number(s).
   */
  public function createMobileNumberNodeFromInput($name, $input) {
    $values = [];
    $values["field_$name"][0] = $input;
    $mobile_number = $this->util->getMobileNumber($input['mobile'], $input['country-code']);
    return $this->submitNodeForm($name, $values, $mobile_number ? $this->util->getCallableNumber($mobile_number) : NULL);
  }

  /**
   * Submit node form.
   */
  public function submitNodeForm($node_type, $values, $number) {

    $values += [
      'body'      => [Language::LANGCODE_NOT_SPECIFIED => [[]]],
      'title'     => $this->randomMachineName(8),
      'comment'   => 2,
      'changed'   => \Drupal::time()->getRequestTime(),
      'moderate'  => 0,
      'promote'   => 0,
      'revision'  => 1,
      'log'       => '',
      'status'    => 1,
      'sticky'    => 0,
      'type'      => $node_type,
      'revisions' => NULL,
      'language'  => Language::LANGCODE_NOT_SPECIFIED,
    ];

    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create($values);

    $form = \Drupal::entityTypeManager()
      ->getFormObject('node', 'default')
      ->setEntity($node);

    $form_state = new FormState();
    $form_state->setValues($values);
    $form_state->setValue('op', t('Save'));
    $form_state->setProgrammedBypassAccessCheck(TRUE);
    $form_state->setCached(FALSE);
    \Drupal::formBuilder()->submitForm($form, $form_state);

    unset($_SESSION['mobile_number_verification'][$number]['verified']);

    return $form_state->getErrors();
  }

  /**
   * Create mobile number field.
   */
  public function createField($content_type, $field_name, $unique, $cardinality, $verify = MobileNumberUtilInterface::MOBILE_NUMBER_VERIFY_OPTIONAL, $allowed_countries = []) {
    $entity_type_manager = \Drupal::entityTypeManager();

    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = $entity_type_manager->getStorage('field_storage_config')
      ->create([
        'entity_type' => 'node',
        'field_name' => $field_name,
        'type' => 'mobile_number',
      ]);
    $field_storage->setSetting('unique', $unique);
    $field_storage
      ->setCardinality($cardinality)
      ->save();

    // Create the instance on the bundle.
    $instance = [
      'field_name' => $field_name,
      'entity_type' => 'node',
      'label' => 'Mobile Number',
      'bundle' => $content_type,
      'required' => TRUE,
    ];

    FieldConfig::create($instance)
      ->setSetting('verify', $verify)
      ->setSetting('countries', $allowed_countries)
      ->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
    $entity_form_display = EntityFormDisplay::load('node.' . $content_type . '.default');
    if (!$entity_form_display) {
      $entity_form_display = EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => $content_type,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }
    $entity_form_display->save();

    $entity_form_display
      ->setComponent($field_storage->getName(), ['type' => 'mobile_number_default'])
      ->save();
  }

  /**
   * Update field config setting.
   *
   * @param string $name
   *   Name of content type.
   * @param string $setting
   *   Setting key.
   * @param mixed $value
   *   Value.
   */
  public function updateFieldConfig($name, $setting, $value) {
    /** @var \Drupal\field\FieldConfigInterface $field */
    $fields = \Drupal::entityManager()->getStorage('field_config')->loadByProperties(['field_name' => "field_$name"]);
    $field = reset($fields);

    $new_field = FieldConfig::create($field->toArray());
    $new_field->original = $field;
    $new_field->setSetting($setting, $value);
    $new_field->enforceIsNew(FALSE);
    $new_field->save();
  }

  /**
   * Updates a field widget setting.
   *
   * @param string $name
   *   Name of content type.
   * @param string $setting
   *   Setting key.
   * @param mixed $value
   *   Value.
   */
  public function updateWidgetSetting($name, $setting, $value) {
    /** @var \Drupal\field\FieldConfigInterface $field */
    $form_display = EntityFormDisplay::load('node.' . $name . '.default');
    $component = $form_display->getComponent("field_$name");
    $component['settings'][$setting] = $value;
    $form_display->setComponent("field_$name", $component);
    $form_display->save();
  }

}
