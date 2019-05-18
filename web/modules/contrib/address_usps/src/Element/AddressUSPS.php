<?php

namespace Drupal\address_usps\Element;

use Drupal\address\Element\Address;
use Drupal\address_usps\AddressUSPSHelper;
use Drupal\address_usps\AddressUSPSProposer;
use Drupal\address_usps\Ajax\USPSSuggestCommand;
use Drupal\address_usps\Ajax\USPSSuggestConfirmCommand;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an address form element with USPS validation.
 *
 * Usage example:
 * @code
 * $form['address'] = [
 *   '#type' => 'address_usps',
 *   '#popup_validation' => TRUE,
 *   '#default_value' => [
 *     'given_name' => 'John',
 *     'family_name' => 'Smith',
 *     'organization' => 'Google Inc.',
 *     'address_line1' => '9200 Milliken Ave',
 *     'address_line2' => '100',
 *     'postal_code' => '91730',
 *     'locality' => 'Rancho Cucomonga',
 *     'administrative_area' => 'CA',
 *     'country_code' => 'US',
 *   ],
 *   '#available_countries' => ['US'],
 * ];
 * @endcode
 *
 * @FormElement("address_usps")
 */
class AddressUSPS extends Address {

  /**
   * {@inheritdoc}
   */
  public static function processAddress(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element = parent::processAddress($element, $form_state, $complete_form);

    $id_prefix = implode('-', $element['#parents']);
    $element['#wrapper_id'] = $id_prefix . '-wrapper';
    $element['#id'] = $id_prefix;
    $element['#prefix'] = '<div id="' . $element['#wrapper_id'] . '">';
    $element['#suffix'] = '</div>';

    // If current country is United States.
    if ($element['#value']['country_code'] == 'US') {
      // Attach libraries.
      $element['#attached']['library'][] = 'address/form';
      $element['#attached']['library'][] = 'address_usps/address_usps.validation';

      /*
       * Provide element selector information to drupalSettings JS object.
       * @see Drupal.behaviors.address_usps
       */
      $element['#attached']['drupalSettings']['address_usps']['elements'][$element['#wrapper_id']] = '#' . $element['#wrapper_id'];

      // Add custom validation.
      $element['#element_validate'][] = [get_called_class(), 'uspsValidate'];

      /*
       * Convert button.
       * Hidden by default, will be clicked automatically in JS.
       *
       * @see Drupal.behaviors.address_usps
       */
      $element['convert'] = [
        '#type' => 'button',
        '#value' => 'Convert',
        '#attributes' => [
          'class' => [
            'address-usps-convert-button',
            'visually-hidden',
          ],
          'name' => $element['#name'] . '[convert]',
        ],
        '#name' => $element['#name'] . '[convert]',
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxSuggest'],
          'event' => 'click',
          'progress' => [
            'type' => 'throbber',
            'message' => t('Searching in USPS database...'),
          ],
        ],
      ];
    }

    if (!empty($element['#value']['country_code'])) {
      $element = static::addressElements($element, $element['#value']);
    }

    \Drupal::moduleHandler()->alter(AddressUSPSHelper::HOOK_ELEMENT_ALTER, $element, $form_state, $complete_form);

    return $element;
  }

  /**
   * USPS Address element validation.
   *
   * If address was found in USPS repository - do nothing. If not - print error.
   *
   * @param array $element
   *   Address USPS render element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Entity complete form.
   */
  public static function uspsValidate(array $element, FormStateInterface $form_state, array &$complete_form) {
    // Not fire on field settings form.
    if ($complete_form['#form_id'] != 'field_config_edit_form') {
      $form_values = $form_state->getValues();
      $element_value = NestedArray::getValue($form_values, array_slice($element['#parents'], 0, -1));

      $proposer = new AddressUSPSProposer();
      $proposer->setAddressElementValue($element_value['address']);
      $usps_address = $proposer->suggestAsElementValues();

      if (!empty($usps_address['error'])) {
        $form_state->setError($element, t('Address you entered not found in USPS repository.'));
      }

      /*
       * @see hook_address_usps_element_validation_alter()
       */
      \Drupal::moduleHandler()->alter(AddressUSPSHelper::HOOK_ELEMENT_VALIDATION_ALTER, $element, $form_state, $complete_form);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $element = parent::ajaxRefresh($form, $form_state);

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#' . $element['#wrapper_id'], $element));

    return $response;
  }

  /**
   * Refills form with USPS suggested values by AJAX.
   *
   * If address was not found in USPS repository - prints error message in form.
   *
   * @param array $form
   *   Entity form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Entity form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax Response object.
   */
  public static function ajaxSuggest(array $form, FormStateInterface $form_state, Request $request) {
    $response = new AjaxResponse();

    $validate_button_element = $form_state->getTriggeringElement();
    $address_element = NestedArray::getValue($form, array_slice($validate_button_element['#array_parents'], 0, -1));

    // Get suggested value.
    $suggester = new AddressUSPSProposer();
    $suggester->setAddressElementValue($address_element['#value']);
    $suggested_values = $suggester->suggestAsElementValues();

    $data_for_altering = [
      'response' => &$response,
      'element' => &$address_element,
      'suggested_values' => &$suggested_values,
      'form_state' => &$form_state,
      'request' => &$request,
    ];

    /*
     * @see hook_address_usps_element_ajax_response_pre_alter()
     */
    \Drupal::moduleHandler()->alter(AddressUSPSHelper::HOOK_ELEMENT_AJAX_RESPONSE_PRE_ALTER, $data_for_altering);

    // If error - reload form and display error.
    if (!empty($suggested_values['error'])) {
      // Just recreate element with error message.
      $address_element['error_messages'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'error' => [AddressUSPSHelper::ADDRESS_NOT_FOUND_MESSAGE],
        ],
      ];
      $response->addCommand(new ReplaceCommand('#' . $address_element['#wrapper_id'], $address_element));
    }
    // If no error - suggest value.
    else {
      // Clear error messages.
      $address_element['usps_error_messages'] = [];

      // If popup validation enabled for this widget.
      if ($address_element['#popup_validation'] == TRUE) {
        $response->addCommand(new USPSSuggestConfirmCommand('#' . $address_element['#wrapper_id'], $suggested_values, $address_element['#value']));
      }
      // If popup validation disabled for this widget.
      else {
        $response->addCommand(new USPSSuggestCommand('#' . $address_element['#wrapper_id'], $suggested_values));
      }
    }

    /*
     * @see hook_address_usps_element_ajax_response_alter()
     */
    \Drupal::moduleHandler()->alter(AddressUSPSHelper::HOOK_ELEMENT_AJAX_RESPONSE_ALTER, $data_for_altering);

    return $response;
  }

}
