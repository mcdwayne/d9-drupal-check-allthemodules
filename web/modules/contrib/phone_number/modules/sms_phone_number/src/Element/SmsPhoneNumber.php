<?php

namespace Drupal\sms_phone_number\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\sms_phone_number\SmsPhoneNumberUtilInterface;
use Drupal\phone_number\Exception\PhoneNumberException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\phone_number\Element\PhoneNumber;

/**
 * Provides a form input element for entering an email address.
 *
 * Properties:
 * - #phone_number
 *   - allowed_countries.
 *   - allowed_types.
 *   - placeholder.
 *   - extension_field.
 *   - verify.
 *   - tfa.
 *   - message.
 *   - token_data.
 *
 * Example usage:
 * @code
 * $form['phone_number'] = array(
 *   '#type' => 'phone_number',
 *   '#title' => $this->t('Phone Number'),
 * );
 *
 * @end
 *
 * @FormElement("sms_phone_number")
 */
class SmsPhoneNumber extends PhoneNumber {

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $result = parent::valueCallback($element, $input, $form_state);
    if ($input) {
      $result['tfa'] = !empty($input['tfa']) ? 1 : 0;
      $result['verified'] = 0;
      $result['verification_token'] = !empty($input['verification_token']) ? $input['verification_token'] : NULL;
      $result['verification_code'] = !empty($input['verification_code']) ? $input['verification_code'] : NULL;
    }

    return $result;
  }

  /**
   * SMS Phone Number element process callback.
   *
   * @param array $element
   *   Element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Complete form.
   *
   * @return array
   *   Processed array.
   */
  public function smsPhoneNumberProcess(array $element, FormStateInterface $form_state, array $complete_form) {
    $element = parent::phoneNumberProcess($element, $form_state, $complete_form);
    /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');
    $field_name = $element['#name'];
    $field_path = implode('][', $element['#parents']);
    $id = $element['#id'];
    $op = $this->getOp($element, $form_state);
    $errors = $form_state->getErrors();

    $element['#phone_number'] += [
      'verify' => SmsPhoneNumberUtilInterface::MOBILE_NUMBER_VERIFY_NONE,
      'message' => SmsPhoneNumberUtilInterface::MOBILE_NUMBER_DEFAULT_SMS_MESSAGE,
      'tfa' => FALSE,
    ];

    $element['#default_value'] += [
      'verified' => 0,
      'tfa' => 0,
    ];

    $settings = $element['#phone_number'];

    $value = $element['#value'];

    $element['#prefix'] = "<div class=\"sms-phone-number-field phone-number-field form-item $field_name\" id=\"$id\">";

    $verified = FALSE;

    if (!empty($value['value']) && $util->getPhoneNumber($value['value'])) {
      $verified = ($settings['verify'] != SmsPhoneNumberUtilInterface::MOBILE_NUMBER_VERIFY_NONE) && static::isVerified($element);
    }

    $element['phone']['#suffix'] = '<div class="form-item verified ' . ($verified ? 'show' : '') . '" title="' . t('Verified') . '"><span>' . t('Verified') . '</span></div>';

    $element['phone']['#attached']['library'][] = 'sms_phone_number/element';

    if ($settings['verify'] != SmsPhoneNumberUtilInterface::MOBILE_NUMBER_VERIFY_NONE) {
      $element['send_verification'] = [
        '#type' => 'button',
        '#value' => t('Send verification code'),
        '#ajax' => [
          'callback' => 'Drupal\sms_phone_number\Element\SmsPhoneNumber::verifyAjax',
          'wrapper' => $id,
          'effect' => 'fade',
        ],
        '#name' => implode('__', $element['#parents']) . '__send_verification',
        '#op' => 'send_verification',
        '#attributes' => [
          'class' => [
            !$verified ? 'show' : '',
            'send-button',
          ],
        ],
        '#submit' => [],
      ];

      $user_input = $form_state->getUserInput();
      $vc_parents = $element['#parents'];
      array_push($vc_parents, 'verification_code');
      if (NestedArray::getValue($user_input, $vc_parents)) {
        NestedArray::setValue($user_input, $vc_parents, '');
        $form_state->setUserInput($user_input);
      }

      $verify_prompt = (!$verified && $op && (!$errors || $op == 'verify'));
      $element['verification_code'] = [
        '#type' => 'textfield',
        '#title' => t('Verification Code'),
        '#prefix' => '<div class="verification ' . ($verify_prompt ? 'show' : '') . '"><div class="description">' . t('A verification code has been sent to your phone. Enter it here.') . '</div>',
      ];

      $element['verify'] = [
        '#type' => 'button',
        '#value' => t('Verify'),
        '#ajax' => [
          'callback' => 'Drupal\sms_phone_number\Element\SmsPhoneNumber::verifyAjax',
          'wrapper' => $id,
          'effect' => 'fade',
        ],
        '#suffix' => '</div>',
        '#name' => implode('__', $element['#parents']) . '__verify',
        '#op' => 'verify',
        '#attributes' => [
          'class' => [
            'verify-button',
          ],
        ],
        '#submit' => [],
      ];

      if (!empty($settings['tfa'])) {
        $element['tfa'] = [
          '#type' => 'checkbox',
          '#title' => t('Enable two-factor authentication'),
          '#default_value' => !empty($value['tfa']) ? 1 : 0,
          '#prefix' => '<div class="sms-phone-number-tfa">',
          '#suffix' => '</div>',
        ];
      }

      $storage = $form_state->getStorage();
      $element['verification_token'] = [
        '#type' => 'hidden',
        '#value' => !empty($storage['sms_phone_number_fields'][$field_path]['token']) ? $storage['phone_number_fields'][$field_path]['token'] : '',
        '#name' => $field_name . '[verification_token]',
      ];
    }

    if (!empty($element['#description'])) {
      $element['description']['#markup'] = '<div class="description">' . $element['#description'] . '</div>';
    }
    return $element;
  }

  /**
   * SMS Phone Number element validate callback.
   *
   * @param array $element
   *   Element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Complete form.
   *
   * @return array
   *   Element.
   */
  public function smsPhoneNumberValidate(array $element, FormStateInterface $form_state, array &$complete_form) {
    $element = parent::phoneNumberValidate($element, $form_state, $complete_form);
    /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');
    $settings = $element['#phone_number'];
    $op = $this->getOp($element, $form_state);
    $field_label = !empty($element['#field_title']) ? $element['#field_title'] : $element['#title'];
    $tree_parents = $element['#parents'];
    $field_path = implode('][', $tree_parents);
    $input = NestedArray::getValue($form_state->getUserInput(), $tree_parents);
    $input = $input ? $input : [];
    $phone_number = NULL;
    $token = !empty($element['#value']['verification_token']) ? $element['#value']['verification_token'] : FALSE;

    if ($input) {
      $input += count($settings['allowed_countries']) == 1 ? ['country-code' => key($settings['allowed_countries'])] : [];
      // @todo Can we eliminate this try-catch and test since it already
      // happened in parent::phoneNumberValidate()?
      try {
        $phone_number = $util->testPhoneNumber($input['phone'], $input['country-code']);
        $verified = static::isVerified($element);

        if ($op == 'send_verification' && !$util->checkFlood($phone_number, 'sms')) {
          $form_state->setError($element['phone'], t('Too many verification code requests for %field, please try again shortly.', [
            '%field' => $field_label,
          ]));
        }
        elseif ($op == 'send_verification' && !$verified && !($util->sendVerification($phone_number, $settings['message'], $util->generateVerificationCode(), $settings['token_data']))) {
          $form_state->setError($element['phone'], t('An error occurred while sending sms.'));
        }
        elseif ($op == 'verify' && !$verified && $util->checkFlood($phone_number)) {
          $verification_parents = $element['#array_parents'];
          $verification_element = NestedArray::getValue($complete_form, $verification_parents);
          $verification_element['verification_code']['#prefix'] = '<div class="verification show"><div class="description">' . t('A verification code has been sent to your phone. Enter it here.') . '</div>';
          NestedArray::setValue($complete_form, $verification_parents, $verification_element);
        }
      }
      catch (PhoneNumberException $e) {
        // Errors are already set for this situation in
        // parent::phoneNumberValidate().
      }
    }

    if (!empty($token)) {
      $storage = $form_state->getStorage();
      $storage['phone_number_fields'][$field_path]['token'] = $token;
      $form_state->setStorage($storage);
    }

    return $element;
  }

  /**
   * SMS Phone Number verification ajax callback.
   *
   * @param array $complete_form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public static function verifyAjax(array $complete_form, FormStateInterface $form_state) {
    /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');

    $element = static::getTriggeringElementParent($complete_form, $form_state);
    $tree_parents = $element['#parents'];
    $field_path = implode('][', $tree_parents);
    $storage = $form_state->getStorage();
    $token = !empty($storage['phone_number_fields'][$field_path]['token']) ? $storage['phone_number_fields'][$field_path]['token'] : NULL;
    $element['verification_token']['#value'] = $token;
    $settings = $element['#phone_number'];
    $op = static::getOp($element, $form_state);

    drupal_get_messages();
    $errors = $form_state->getErrors();

    foreach ($errors as $path => $message) {
      if (strpos($path, implode('][', $element['#parents'])) === 0) {
        drupal_set_message($message, 'error');
      }
      else {
        unset($errors[$path]);
      }
    }

    $phone_number = static::getPhoneNumber($element);
    $verified = FALSE;
    $verify_prompt = FALSE;
    if ($phone_number) {
      $verified = static::isVerified($element);
      $verify_flood_ok = $verified || ($util->checkFlood($phone_number));

      if ($verify_flood_ok) {
        if (!$verified && !$errors && ($op == 'send_verification')) {
          $verify_prompt = TRUE;
        }
        elseif (!$verified && ($op == 'verify')) {
          $verify_prompt = TRUE;
        }
      }
    }

    $element['messages'] = ['#type' => 'status_messages'];
    unset($element['_weight']);
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(NULL, $element));

    $settings = [];

    if ($verify_prompt) {
      $settings['smsPhoneNumberVerificationPrompt'] = $element['#id'];
    }
    else {
      $settings['smsPhoneNumberHideVerificationPrompt'] = $element['#id'];
    }

    if ($verified) {
      $settings['smsPhoneNumberVerified'] = $element['#id'];
    }

    if ($settings) {
      $response->addCommand(new SettingsCommand($settings));
    }

    return $response;
  }

  /**
   * Get form op name based on the button pressed in the form.
   *
   * @param array $element
   *   SMS Phone Number element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return null|string
   *   Operation name, or null if button does not belong to element.
   */
  public static function getOp(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    $op = !empty($triggering_element['#op']) ? $triggering_element['#op'] : NULL;
    $button = !empty($triggering_element['#name']) ? $triggering_element['#name'] : NULL;

    if (!in_array($button, [
      implode('__', $element['#parents']) . '__send_verification',
      implode('__', $element['#parents']) . '__verify',
    ])
    ) {
      $op = NULL;
    }

    return $op;
  }

  /**
   * Get SMS Phone Number form element based on currently pressed form button.
   *
   * @param array $complete_form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   SMS Phone Number form element.
   */
  public static function getTriggeringElementParent(array $complete_form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];
    array_pop($parents);
    $element = NestedArray::getValue($complete_form, $parents);
    return $element;
  }

  /**
   * Gets verified status.
   *
   * Based on default value and verified numbers in session.
   *
   * @param array $element
   *   Form element.
   *
   * @return bool
   *   True if verified, false otherwise.
   */
  public static function isVerified(array $element) {
    /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');

    $phone_number = static::getPhoneNumber($element);
    $default_phone_number = static::getPhoneNumber($element, FALSE);
    $verified = FALSE;
    if ($phone_number) {
      $verified = ($default_phone_number ? $util->getCallableNumber($default_phone_number) == $util->getCallableNumber($phone_number) : FALSE) && $element['#default_value']['verified'];
      $verified = $verified || $util->isVerified($phone_number);
    }

    return $verified;
  }

}
