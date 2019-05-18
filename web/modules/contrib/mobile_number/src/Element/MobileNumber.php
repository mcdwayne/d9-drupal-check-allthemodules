<?php

namespace Drupal\mobile_number\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\mobile_number\MobileNumberUtilInterface;
use Drupal\mobile_number\Exception\MobileNumberException;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\SettingsCommand;

/**
 * Provides a form input element for entering an email address.
 *
 * Properties:
 * - #mobile_number
 *   - allowed_countries
 *   - verify
 *   - tfa
 *   - message
 *   - placeholder
 *   - token_data.
 *
 * Example usage:
 * @code
 * $form['mobile_number'] = array(
 *   '#type' => 'mobile_number',
 *   '#title' => $this->t('Mobile Number'),
 * );
 *
 * @end
 *
 * @FormElement("mobile_number")
 */
class MobileNumber extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#process' => [
        [$this, 'mobileNumberProcess'],
      ],
      '#element_validate' => [
        [$this, 'mobileNumberValidate'],
      ],
      '#mobile_number' => [],
    ];
  }

  /**
   * Mobile number element value callback.
   *
   * @param array $element
   *   Element.
   * @param bool $input
   *   Input.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Value.
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    /** @var \Drupal\mobile_number\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');
    $result = [];
    if ($input) {
      $settings = !empty($element['#mobile_number']) ? $element['#mobile_number'] : [];
      $settings += ['allowed_countries' => []];
      $country = !empty($input['country-code']) ? $input['country-code'] : (count($settings['allowed_countries']) == 1 ? key($settings['allowed_countries']) : NULL);
      $mobile_number = $util->getMobileNumber($input['mobile'], $country);
      $result = [
        'value' => $mobile_number ? $util->getCallableNumber($mobile_number) : '',
        'country' => $country,
        'local_number' => $input['mobile'],
        'tfa' => !empty($input['tfa']) ? 1 : 0,
        'verified' => 0,
        'verification_token' => !empty($input['verification_token']) ? $input['verification_token'] : NULL,
        'verification_code' => !empty($input['verification_code']) ? $input['verification_code'] : NULL,
      ];
    }
    else {
      $result = !empty($element['#default_value']) ? $element['#default_value'] : [];
    }

    return $result;
  }
  
  /**
   * Mobile number element process callback.
   *
   * @param  $element
   *   Element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Complete form.
   *
   * @return array
   *   Processed array.
   */
  public function mobileNumberProcess($element, FormStateInterface $form_state, array $complete_form) {
    /** @var \Drupal\mobile_number\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');
    $element['#tree'] = TRUE;
    $field_name = $element['#name'];
    $field_path = implode('][', $element['#parents']);
    $id = $element['#id'];
    $op = $this->getOp($element, $form_state);
    $element += [
      '#default_value' => [],
      '#mobile_number' => [],
    ];

    $errors = $form_state->getErrors();
    foreach ($errors as $path => $message) {
      if (!(strpos($path, $field_path) === 0)) {
        unset($errors[$path]);
      }
    }

    $element['#mobile_number'] += [
      'allowed_countries' => [],
      'verify' => MobileNumberUtilInterface::MOBILE_NUMBER_VERIFY_NONE,
      'message' => MobileNumberUtilInterface::MOBILE_NUMBER_DEFAULT_SMS_MESSAGE,
      'tfa' => FALSE,
      'token_data' => [],
      'placeholder' => NULL,
    ];
    $settings = $element['#mobile_number'];

    $element['#default_value'] += [
      'value' => '',
      'country' => (count($settings['allowed_countries']) == 1) ? key($settings['allowed_countries']) : 'US',
      'local_number' => '',
      'verified' => 0,
      'tfa' => 0,
    ];

    if ($default_mobile_number = $util->getMobileNumber($element['#default_value']['value'])) {
      $element['#default_value']['country'] = $util->getCountry($default_mobile_number);
    }

    $value = $element['#value'];

    $element['#prefix'] = "<div class=\"mobile-number-field form-item $field_name\" id=\"$id\">";
    $element['#suffix'] = '</div>';

    $element['label'] = [
      '#type' => 'label',
      '#title' => $element['#title'],
      '#required' => $element['#required'],
      '#title_display' => $element['#title_display'],
    ];

    $mobile_number = NULL;
    $verified = FALSE;
    $countries = $util->getCountryOptions($settings['allowed_countries'], TRUE);
    $countries += $util->getCountryOptions([$element['#default_value']['country'] => $element['#default_value']['country']], TRUE);
    $default_country = $element['#default_value']['country'];

    if (!empty($value['value']) && $mobile_number = $util->getMobileNumber($value['value'])) {
      $verified = ($settings['verify'] != MobileNumberUtilInterface::MOBILE_NUMBER_VERIFY_NONE) && static::isVerified($element);
      $default_country = $util->getCountry($mobile_number);
      $country = $util->getCountry($mobile_number);
      $countries += $util->getCountryOptions([$country => $country]);
    }

    $element['country-code'] = [
      '#type' => 'select',
      '#options' => $countries,
      '#default_value' => $default_country,
      '#access' => !(count($countries) == 1),
      '#attributes' => ['class' => ['country']],
      '#title' => t('Country Code'),
      '#title_display' => 'invisible',
    ];

    $element['mobile'] = [
      '#type' => 'textfield',
      '#default_value' => $mobile_number ? $util->libUtil()
        ->format($mobile_number, 2) : NULL,
      '#title' => t('Phone number'),
      '#title_display' => 'invisible',
      '#suffix' => '<div class="form-item verified ' . ($verified ? 'show' : '') . '" title="' . t('Verified') . '"><span>' . t('Verified') . '</span></div>',
      '#attributes' => [
        'class' => ['local-number'],
        'placeholder' => isset($settings['placeholder']) ? ($settings['placeholder'] ? t($settings['placeholder']) : '') : t('Phone number'),
      ],
    ];

    $element['mobile']['#attached']['library'][] = 'mobile_number/element';

    if ($settings['verify'] != MobileNumberUtilInterface::MOBILE_NUMBER_VERIFY_NONE) {
      $element['send_verification'] = [
        '#type' => 'button',
        '#value' => t('Send verification code'),
        '#ajax' => [
          'callback' => 'Drupal\mobile_number\Element\MobileNumber::verifyAjax',
          'wrapper' => $id,
          'effect' => 'fade',
        ],
        '#name' => implode('__', $element['#parents']) . '__send_verification',
        '#mobile_number_op' => 'mobile_number_send_verification',
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

      $verify_prompt = (!$verified && $op && (!$errors || $op == 'mobile_number_verify'));
      $element['verification_code'] = [
        '#type' => 'textfield',
        '#title' => t('Verification Code'),
        '#prefix' => '<div class="verification ' . ($verify_prompt ? 'show' : '') . '"><div class="description">' . t('A verification code has been sent to your mobile. Enter it here.') . '</div>',
      ];

      $element['verify'] = [
        '#type' => 'button',
        '#value' => t('Verify'),
        '#ajax' => [
          'callback' => 'Drupal\mobile_number\Element\MobileNumber::verifyAjax',
          'wrapper' => $id,
          'effect' => 'fade',
        ],
        '#suffix' => '</div>',
        '#name' => implode('__', $element['#parents']) . '__verify',
        '#mobile_number_op' => 'mobile_number_verify',
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
          '#prefix' => '<div class="mobile-number-tfa">',
          '#suffix' => '</div>',
        ];
      }

      $storage = $form_state->getStorage();
      $element['verification_token'] = [
        '#type' => 'hidden',
        '#value' => !empty($storage['mobile_number_fields'][$field_path]['token']) ? $storage['mobile_number_fields'][$field_path]['token'] : '',
        '#name' => $field_name . '[verification_token]',
      ];
    }

    if (!empty($element['#description'])) {
      $element['description']['#markup'] = '<div class="description">' . $element['#description'] . '</div>';
    }
    return $element;
  }

  /**
   * Mobile number element validate callback.
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
  public function mobileNumberValidate($element, FormStateInterface $form_state, &$complete_form) {
    /** @var \Drupal\mobile_number\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');
    $settings = $element['#mobile_number'];
    $op = $this->getOp($element, $form_state);
    $field_label = !empty($element['#field_title']) ? $element['#field_title'] : $element['#title'];
    $tree_parents = $element['#parents'];
    $field_path = implode('][', $tree_parents);
    $input = NestedArray::getValue($form_state->getUserInput(), $tree_parents);
    $input = $input ? $input : [];
    $mobile_number = NULL;
    $countries = $util->getCountryOptions([], TRUE);
    $token = !empty($element['#value']['verification_token']) ? $element['#value']['verification_token'] : FALSE;

    if ($input) {
      $input += count($settings['allowed_countries']) == 1 ? ['country-code' => key($settings['allowed_countries'])] : [];
      try {
        $mobile_number = $util->testMobileNumber($input['mobile'], $input['country-code']);
        $verified = static::isVerified($element);

        if ($op == 'mobile_number_send_verification' && !$util->checkFlood($mobile_number, 'sms')) {
          $form_state->setError($element['mobile'], t('Too many verification code requests for %field, please try again shortly.', [
            '%field' => $field_label,
          ]));
        }
        elseif ($op == 'mobile_number_send_verification' && !$verified && !($token = $util->sendVerification($mobile_number, $settings['message'], $util->generateVerificationCode(), $settings['token_data']))) {
          $form_state->setError($element['mobile'], t('An error occurred while sending sms.'));
        }
        elseif ($op == 'mobile_number_verify' && !$verified && $util->checkFlood($mobile_number)) {
          $verification_parents = $element['#array_parents'];
          $verification_element = NestedArray::getValue($complete_form, $verification_parents);
          $verification_element['verification_code']['#prefix'] = '<div class="verification show"><div class="description">' . t('A verification code has been sent to your mobile. Enter it here.') . '</div>';
          NestedArray::setValue($complete_form, $verification_parents, $verification_element);
        }
      }
      catch (MobileNumberException $e) {
        switch ($e->getCode()) {
          case MobileNumberException::ERROR_NO_NUMBER:
            if (!empty($element['#required']) || $op) {
              $form_state->setError($element['mobile'], t('Phone number in %field is required.', [
                '%field' => $field_label,
              ]));
            }
            break;

          case MobileNumberException::ERROR_INVALID_NUMBER:
          case MobileNumberException::ERROR_WRONG_TYPE:
            $form_state->setError($element['mobile'], t('The phone number %value provided for %field is not a valid mobile number for country %country.', [
              '%value' => $input['mobile'],
              '%field' => $field_label,
              '%country' => !empty($countries[$input['country-code']]) ? $countries[$input['country-code']] : '',
            ]));

            break;

          case MobileNumberException::ERROR_WRONG_COUNTRY:
            $form_state->setError($element['mobile'], t('The country %value provided for %field does not match the mobile number prefix.', [
              '%value' => !empty($countries[$input['country-code']]) ? $countries[$input['country-code']] : '',
              '%field' => $field_label,
            ]));
            break;
        }
      }
    }

    if (!empty($input['mobile'])) {
      $input += count($settings['allowed_countries']) == 1 ? ['country-code' => key($settings['allowed_countries'])] : [];
      if ($mobile_number = $util->getMobileNumber($input['mobile'], $input['country-code'])) {
        $country = $util->getCountry($mobile_number);
        if ($settings['allowed_countries'] && empty($settings['allowed_countries'][$country])) {
          $form_state->setError($element['country-code'], t('The country %value provided for %field is not an allowed country.', [
            '%value' => $util->getCountryName($country),
            '%field' => $field_label,
          ]));
        }
      }
    }

    if (!empty($token)) {
      $storage = $form_state->getStorage();
      $storage['mobile_number_fields'][$field_path]['token'] = $token;;
      $form_state->setStorage($storage);
    }

    return $element;
  }

  /**
   * Mobile number element ajax callback.
   *
   * @param array $complete_form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public static function verifyAjax($complete_form, FormStateInterface $form_state) {
    /** @var \Drupal\mobile_number\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');

    $element = static::getTriggeringElementParent($complete_form, $form_state);
    $tree_parents = $element['#parents'];
    $field_path = implode('][', $tree_parents);
    $storage = $form_state->getStorage();
    $token = !empty($storage['mobile_number_fields'][$field_path]['token']) ? $storage['mobile_number_fields'][$field_path]['token'] : NULL;
    $element['verification_token']['#value'] = $token;
    $settings = $element['#mobile_number'];
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

    $mobile_number = static::getMobileNumber($element);
    $verified = FALSE;
    $verify_prompt = FALSE;
    if ($mobile_number) {
      $verified = static::isVerified($element);
      $verify_flood_ok = $verified || ($util->checkFlood($mobile_number));

      if ($verify_flood_ok) {
        if (!$verified && !$errors && ($op == 'mobile_number_send_verification')) {
          $verify_prompt = TRUE;
        }
        elseif (!$verified && ($op == 'mobile_number_verify')) {
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
      $settings['mobileNumberVerificationPrompt'] = $element['#id'];
    }
    else {
      $settings['mobileNumberHideVerificationPrompt'] = $element['#id'];
    }

    if ($verified) {
      $settings['mobileNumberVerified'] = $element['#id'];
    }

    if ($settings) {
      $response->addCommand(new SettingsCommand($settings));
    }

    return $response;
  }

  /**
   * Get mobile number form operation name based on the button pressed in the form.
   *
   * @param array $element
   *   Mobile number element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return null|string
   *   Operation name, or null if button does not belong to element.
   */
  public static function getOp(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    $op = !empty($triggering_element['#mobile_number_op']) ? $triggering_element['#mobile_number_op'] : NULL;
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
   * Get mobile number form element based on currently pressed form button.
   *
   * @param array $complete_form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   Mobile number form element.
   */
  public static function getTriggeringElementParent(array $complete_form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];
    array_pop($parents);
    $element = NestedArray::getValue($complete_form, $parents);
    return $element;
  }

  /**
   * Get currently entered mobile number, given the form element.
   *
   * @param array $element
   *   Mobile number form element.
   * @param bool $input_value
   *   Whether to use the input value or the default value, TRUE = input value.
   *
   * @return \libphonenumber\PhoneNumber|null
   *   Mobile number. Null if empty, or not valid, mobile number.
   */
  public static function getMobileNumber($element, $input_value = TRUE) {
    /** @var \Drupal\mobile_number\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');

    if ($input_value) {
      $values = !empty($element['#value']['local_number']) ? $element['#value'] : [];
    }
    else {
      $values = !empty($element['#default_value']['local_number']) ? $element['#default_value'] : [];
    }
    if ($values) {
      return $util->getMobileNumber($values['local_number'], $values['country']);
    }

    return NULL;
  }

  /**
   * Gets verified status based on default value and verified numbers in session.
   *
   * @param array $element
   *   Form element.
   *
   * @return bool
   *   True if verified, false otherwise.
   */
  public static function isVerified($element) {
    /** @var \Drupal\mobile_number\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');

    $mobile_number = static::getMobileNumber($element);
    $default_mobile_number = static::getMobileNumber($element, FALSE);
    $verified = FALSE;
    if ($mobile_number) {
      $verified = ($default_mobile_number ? $util->getCallableNumber($default_mobile_number) == $util->getCallableNumber($mobile_number) : FALSE) && $element['#default_value']['verified'];
      $verified = $verified || $util->isVerified($mobile_number);
    }

    return $verified;
  }

}
