<?php

namespace Drupal\sms_phone_number\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\phone_number\Plugin\Field\FieldWidget\PhoneNumberWidget;
use Drupal\sms_phone_number\Element\SmsPhoneNumber;
use Drupal\sms_phone_number\SmsPhoneNumberUtilInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'sms_phone_number' widget.
 *
 * @FieldWidget(
 *   id = "sms_phone_number_default",
 *   label = @Translation("SMS Phone Number"),
 *   description = @Translation("SMS Phone Number field default widget."),
 *   field_types = {
 *     "sms_phone_number"
 *   }
 * )
 */
class SmsPhoneNumberWidget extends PhoneNumberWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $item = $items[$delta];

    /** @var ContentEntityInterface $entity */
    $entity = $items->getEntity();

    /** @var \Drupal\sms_phone_number\Element\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');

    $settings = $this->getFieldSettings();
    $settings += $this->getSettings() + static::defaultSettings();

    $tfa_field = $util->getTfaField();

    $element['#default_value']['verified'] = $item->verified;
    $element['#default_value']['tfa'] = $item->tfa;
    $element['#phone_number']['verify'] = ($util->isSmsEnabled() && !empty($settings['verify'])) ? $settings['verify'] : SmsPhoneNumberUtilInterface::PHONE_NUMBER_VERIFY_NONE;
    $element['#phone_number']['message'] = !empty($settings['message']) ? $settings['message'] : NULL;
    $element['#phone_number']['tfa'] = (
      $entity->getEntityTypeId() == 'user' &&
      $tfa_field == $items->getFieldDefinition()->getName() &&
      $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality() == 1
    ) ? TRUE : NULL;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\sms_phone_number\Element\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');
    $op = SmsPhoneNumber::getOp($element, $form_state);
    $sms_phone_number = SmsPhoneNumber::getPhoneNumber($element);

    if ($op == 'sms_phone_number_send_verification' && $sms_phone_number && ($util->checkFlood($sms_phone_number) || $util->checkFlood($sms_phone_number, 'sms'))) {
      return FALSE;
    }

    return parent::errorElement($element, $error, $form, $form_state);
  }

}
