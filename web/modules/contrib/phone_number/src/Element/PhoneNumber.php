<?php

namespace Drupal\phone_number\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\phone_number\Exception\CountryException;
use Drupal\phone_number\Exception\ParseException;
use Drupal\phone_number\Exception\TypeException;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Component\Utility\NestedArray;
use libphonenumber\PhoneNumberType;

/**
 * Provides a form input element for entering a phone number.
 *
 * Properties:
 * - #phone_number
 *   - allowed_countries.
 *   - allowed_types.
 *   - placeholder.
 *   - extension_field.
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
 * @FormElement("phone_number")
 */
class PhoneNumber extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#process' => [
        [$this, 'phoneNumberProcess'],
      ],
      '#element_validate' => [
        [$this, 'phoneNumberValidate'],
      ],
      '#phone_number' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    /** @var \Drupal\phone_number\PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');
    $result = [];
    if ($input) {
      $settings = !empty($element['#phone_number']) ? $element['#phone_number'] : [];
      $settings += [
        'allowed_countries' => NULL,
        'allowed_types' => NULL,
        'extension_field' => FALSE,
      ];
      $country = !empty($input['country-code']) ? $input['country-code'] : (count($settings['allowed_countries']) == 1 ? key($settings['allowed_countries']) : []);
      $extension = $settings['extension_field'] ? $input['extension'] : NULL;
      $phone_number = $util->getPhoneNumber($input['phone'], $country, $extension);
      $result = [
        'value' => $phone_number ? $util->getCallableNumber($phone_number) : '',
        'country' => $country,
        'local_number' => $input['phone'],
        'extension' => $extension,
      ];
    }
    else {
      $result = !empty($element['#default_value']) ? $element['#default_value'] : [];
    }

    return $result;
  }

  /**
   * Phone number element process callback.
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
  public function phoneNumberProcess(array $element, FormStateInterface $form_state, array $complete_form) {
    /** @var \Drupal\phone_number\PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');
    $element['#tree'] = TRUE;
    $field_name = $element['#name'];
    $field_path = implode('][', $element['#parents']);
    $id = $element['#id'];
    $element += [
      '#default_value' => [],
      '#phone_number' => [],
    ];

    $errors = $form_state->getErrors();
    foreach ($errors as $path => $message) {
      if (!(strpos($path, $field_path) === 0)) {
        unset($errors[$path]);
      }
    }

    $element['#phone_number'] += [
      'allowed_countries' => NULL,
      'allowed_types' => NULL,
      'placeholder' => NULL,
      'extension_field' => FALSE,
    ];
    $settings = $element['#phone_number'];

    $element['#default_value'] += [
      'value' => '',
      'country' => (!empty($settings['allowed_countries']) && count($settings['allowed_countries']) == 1) ? key($settings['allowed_countries']) : 'US',
      'local_number' => '',
    ];

    if ($default_phone_number = $util->getPhoneNumber($element['#default_value']['value'])) {
      $element['#default_value']['country'] = $util->getCountry($default_phone_number);
    }

    $value = $element['#value'];

    $element['#prefix'] = "<div class=\"phone-number-field form-item $field_name\" id=\"$id\">";
    $element['#suffix'] = '</div>';

    $element['label'] = [
      '#type' => 'label',
      '#title' => $element['#title'],
      '#required' => $element['#required'],
      '#title_display' => $element['#title_display'],
    ];

    $phone_number = NULL;
    $countries = $util->getCountryOptions($settings['allowed_countries'], TRUE);
    $countries += $util->getCountryOptions([$element['#default_value']['country'] => $element['#default_value']['country']], TRUE);
    $default_country = $element['#default_value']['country'];

    if (!empty($value['value']) && $phone_number = $util->getPhoneNumber($value['value'])) {
      $default_country = $util->getCountry($phone_number);
      $country = $util->getCountry($phone_number);
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

    $element['phone'] = [
      '#type' => 'textfield',
      '#default_value' => $phone_number ? $util->libUtil()
        ->format($phone_number, 2) : NULL,
      '#title' => t('Phone number'),
      '#title_display' => 'invisible',
      '#attributes' => [
        'class' => ['local-number'],
        'placeholder' => isset($settings['placeholder']) ? ($settings['placeholder'] ? t($settings['placeholder']) : '') : t('Phone number'),
      ],
      '#attached' => [
        'library' => [
          'phone_number/element',
        ],
      ],
    ];

    if ($settings['extension_field']) {
      $element['#default_value'] += [
        'extension' => '',
      ];
      $element['extension'] = [
        '#type' => 'textfield',
        '#default_value' => !empty($value['extension']) ? $value['extension'] : NULL,
        '#title' => t('Extension'),
        '#title_display' => 'invisible',
        '#size' => 5,
        '#maxlength' => 40,
        '#attributes' => [
          'class' => ['extension'],
          'placeholder' => $this->t('Ext.'),
        ],
      ];
    }

    if (!empty($element['#description'])) {
      $element['description']['#markup'] = '<div class="description">' . $element['#description'] . '</div>';
    }

    return $element;
  }

  /**
   * Phone number element validate callback.
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
  public function phoneNumberValidate(array $element, FormStateInterface $form_state, array &$complete_form) {
    /** @var \Drupal\phone_number\PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');
    $settings = $element['#phone_number'];
    $field_label = !empty($element['#field_title']) ? $element['#field_title'] : $element['#title'];
    $tree_parents = $element['#parents'];
    $input = NestedArray::getValue($form_state->getUserInput(), $tree_parents);
    $input = $input ? $input : [];
    $phone_number = NULL;
    $extension = NULL;
    if ($settings['extension_field']) {
      $extension = $input['extension'];
    }

    $phone_number_provided = isset($input['phone']) && $input['phone'] != '';

    if ($input && $phone_number_provided) {
      $input += (!empty($settings['allowed_countries']) && count($settings['allowed_countries']) == 1) ? ['country-code' => key($settings['allowed_countries'])] : [];
      try {
        $phone_number = $util->testPhoneNumber($input['phone'], $input['country-code'], $extension, $settings['allowed_types']);
      }
      catch (ParseException $e) {
        // Number was not parse-able.
        $form_state->setError($element['phone'], t('The phone number %number provided for %field is not a valid phone number for country %country.', [
          '%number' => $input['phone'],
          '%field' => $field_label,
          '%country' => $util->getCountryName($input['country-code']),
        ]));
      }
      catch (TypeException $e) {
        // Number was not an allowed type.
        if ($e->getType() == PhoneNumberType::UNKNOWN) {
          // Number is type-unknown.  Provide a simpler validation error
          // message.
          $form_state->setError($element['phone'], t('The phone number %number provided for %field is not a valid phone number for country %country.', [
            '%number' => $input['phone'],
            '%field' => $field_label,
            '%country' => $util->getCountryName($input['country-code']),
          ]));
        }
        else {
          $allowed_types = [
            '#theme' => 'item_list',
            '#items' => [],
          ];
          foreach ($util->getTypeOptions() as $type => $label) {
            $allowed = in_array($type, $settings['allowed_types']);
            // Hide the confusing FIXED_LINE_OR_MOBILE type, unless it is the
            // only allowed type.  It may be worth exposing this behavior as
            // a setting if ever desired.
            $hidden = ($type == PhoneNumberType::FIXED_LINE_OR_MOBILE && count($settings['allowed_types']) > 1);
            if ($allowed && !$hidden) {
              $allowed_types['#items'][] = $label;
            }
          }
          $allowed_types = \Drupal::service('renderer')->render($allowed_types);
          $form_state->setError($element['phone'], $this->t('The phone number %number provided for %field appears to be type: %number_type, which is not permitted.  The following phone number types are permitted: %allowed_types', [
            '%number' => $input['phone'],
            '%field' => $field_label,
            '%number_type' => (!is_null($e->getType()) && array_key_exists($e->getType(), $util->getTypeOptions())) ? $util->getTypeOptions()[$e->getType()] : '',
            '%allowed_types' => $allowed_types,
          ]));
        }
      }
      catch (CountryException $e) {
        // Number was of wrong country.
        if ($e->getCountry()) {
          $form_state->setError($element['phone'], $this->t('The phone number %number provided for %field appears to be a %number_country number, yet %country was provided for country.', [
            '%number' => $input['phone'],
            '%country' => $util->getCountryName($input['country-code']),
            '%number_country' => $util->getCountryName($e->getCountry()),
            '%field' => $field_label,
          ]));
        }
        else {
          $form_state->setError($element['phone'], $this->t('The phone number %number provided for %field does not appear to be a %country number.', [
            '%number' => $input['phone'],
            '%country' => $util->getCountryName($input['country-code']),
            '%field' => $field_label,
          ]));
        }
      }

      // Validate extension is numeric (if provided).
      if (!empty($settings['extension_field'])
        && (!is_null($extension) && $extension != '')
        && !ctype_digit($extension)) {
        $form_state->setError($element['extension'], $this->t('The extension for %field must be numeric.', [
          '%field' => $field_label,
        ]));
      }

      // Validate country is allowed.
      if ($phone_number) {
        $country = $util->getCountry($phone_number);
        if (!empty($settings['allowed_countries']) && empty($settings['allowed_countries'][$country])) {
          $form_state->setError($element['country-code'], $this->t('The country %country provided for %field is not an allowed country.', [
            '%country' => $util->getCountryName($country),
            '%field' => $field_label,
          ]));
        }
      }
    }
    elseif (!empty($element['#required'])) {
      $form_state->setError($element['phone'], t('Phone number in %field is required.', [
        '%field' => $field_label,
      ]));
    }

    return $element;
  }

  /**
   * Get currently entered phone number, given the form element.
   *
   * @param array $element
   *   Phone number form element.
   * @param bool $input_value
   *   Whether to use the input value or the default value, TRUE = input value.
   *
   * @return \libphonenumber\PhoneNumber|null
   *   Phone number. Null if empty, or not valid, phone number.
   */
  public static function getPhoneNumber(array $element, $input_value = TRUE) {
    /** @var \Drupal\phone_number\PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');

    if ($input_value) {
      $values = !empty($element['#value']['local_number']) ? $element['#value'] : [];
    }
    else {
      $values = !empty($element['#default_value']['local_number']) ? $element['#default_value'] : [];
    }
    if ($values) {
      $settings = $element['#phone_number'];
      $extension = NULL;
      if ($settings['extension_field']) {
        $extension = $values['extension'];
      }
      return $util->getPhoneNumber($values['local_number'], $values['country'], $extension);
    }

    return NULL;
  }

}
