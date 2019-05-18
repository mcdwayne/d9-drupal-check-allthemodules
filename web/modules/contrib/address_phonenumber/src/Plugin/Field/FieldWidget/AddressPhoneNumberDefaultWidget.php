<?php

namespace Drupal\address_phonenumber\Plugin\Field\FieldWidget;

use Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use \libphonenumber\PhoneNumberUtil;
use \libphonenumber\NumberParseException;

/**
 * Class AddressPhoneNumberDefaultWidget.
 *
 * @FieldWidget(
 *   id = "address_phone_number_default",
 *   label = @Translation("Address with Phonenumber"),
 *   description = @Translation("An contact text field with an associated Address."),
 *   field_types = {
 *     "address_phone_number_item"
 *   }
 * )
 */
class AddressPhoneNumberDefaultWidget extends AddressDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget = parent::formElement($items, $delta, $element, $form, $form_state);
    $widget['address']['#type'] = 'address_phone_number_item';
    $widget['address']['locality'] = [
      '#weight' => 1,
    ];
    $widget['address']['address_phonenumber'] = [
      '#title' => $this->t('Phone Number'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => isset($items[$delta]->address_phonenumber) ? $items[$delta]->address_phonenumber : NULL,
      '#weight' => 10,
      '#states' => [
        'invisible' => [
          ':input[name="payment_information[billing_information][field_contact_address][0][address][country_code]"]' => ['value' => ''],
        ],
      ],
      '#element_validate' => array(
         array($this, 'addressphonenumberValidate'),
      ),

    ];
    return $widget;
  }

  /**
   * {@inheritdoc}
   */
  public function addressphonenumberValidate($element, FormStateInterface $form_state, $form) {
    $form_values = $form_state->getValues();
    $array_parents = $element['#parents'];
    if (in_array('payment_information', $array_parents, TRUE)) {
      if ($form_values['payment_information']['billing_information']['reuse_profile'] == 1) {
        return;
      }
    }
    $element_name = implode('][', $array_parents);
    array_pop($array_parents);
    $telephone = NestedArray::getValue(
      $form_values,
      array_merge($array_parents, ['address_phonenumber'])
    );
    $country_code = NestedArray::getValue(
      $form_values,
      array_merge($array_parents, ['country_code'])
    );
    $phone_util = PhoneNumberUtil::getInstance();
    try {
      $phone_util_number = $phone_util->parse($telephone, $country_code);
      $is_possible = $phone_util->isPossibleNumber($phone_util_number);
      if (!$is_possible) {
        $form_state->setErrorByName($element_name, 'Please enter valid phone number.');
      }
    }
    catch (NumberParseException $e) {
      $form_state->setErrorByName($element_name, 'Please enter valid phone number.');
    }
  }

}
