<?php

namespace Drupal\phone_number\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a Phone Number field mapper.
 *
 * @FeedsTarget(
 *   id = "phone_number",
 *   field_types = {"phone_number"}
 * )
 */
class PhoneNumber extends FieldTargetBase implements ConfigurableTargetInterface {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value')
      ->addProperty('local_number')
      ->addProperty('country')
      ->addProperty('extension');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    /** @var PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');
    $phone_number = FALSE;
    $extension = !empty($values['extension']) ? $values['extension'] : NULL;
    if (!empty($values['local_number']) && !empty($values['country'])) {
      $phone_number = $util->getPhoneNumber($values['local_number'], $values['country'], $extension);
    }
    else {
      $phone_number = $util->getPhoneNumber($values['value'], NULL, $extension);
    }
    if ($phone_number) {
      $values['value'] = $util->getCallableNumber($phone_number);
      $values['local_number'] = $util->getLocalNumber($phone_number, TRUE);
      $values['country'] = $util->getCountry($phone_number);
      $values['extension'] = $phone_number->getExtension();
    }
    else {
      $values = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return '';
  }

}
