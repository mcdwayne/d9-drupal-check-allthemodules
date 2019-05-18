<?php

namespace Drupal\friendly_autocomplete\Element;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\CompositeFormElementTrait;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides an improved autocomplete form API element.
 *
 * The primary difference between this and the core autocomplete on a textfield
 * element is that the autocomplete route should provide arrays with the label
 * and value elements stored separately. For example, the default core method is
 * like this:
 * @code
 * $values = [
 *   [
 *     'value' => $node->label() . '(' . $node->id() . ')',
 *     'label' => $node->label(),
 *   ],
 * ];
 * return new JsonResponse($values);
 * @endcode
 *
 * The method with this module is like this:
 * @code
 * $values = [
 *   [
 *     'value' => $node->id(),
 *     'label' => $node->label(),
 *   ],
 * ];
 * return new JsonResponse($values);
 * @endcode
 *
 * Properties:
 * - #autocomplete_route_name: A route to be used as callback URL by the
 *   autocomplete JavaScript library.
 *
 * Usage example:
 * @code
 * $form['id'] = [
 *   '#type' => 'friendly_autocomplete',
 *   '#title' => $this->t('ID'),
 *   '#autocomplete_route_name' => 'my_module.route_name',
 * ];
 * @endcode
 *
 * @FormElement("friendly_autocomplete")
 */
class FriendlyAutocomplete extends FormElement {

  use CompositeFormElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input' => TRUE,
      '#markup' => '',
      '#process' => [
        [$class, 'processAutocomplete'],
      ],
      '#pre_render' => [
        [$class, 'preRenderCompositeFormElement'],
      ],
      '#attached' => [
        'library' => ['friendly_autocomplete/friendly-autocomplete'],
      ],
    ];
  }

  /**
   * Expand an autocomplete field into two textfields, one hidden, one not.
   */
  public static function processAutocomplete(&$element, FormStateInterface $form_state, &$complete_form) {
    // Check for the route name first.
    if (empty($element['#autocomplete_route_name'])) {
      // Return an empty array if the route is not present.
      return [];
    }

    // Get the default value from the element.
    $default_value = !empty($element['#default_value']) ? $element['#default_value'] : '';
    // Get the label from the controller callback.
    if (!empty($default_value)) {
      $existing_values = self::getDataFromId($default_value, $element['#autocomplete_route_name']);
    }

    $element['#tree'] = TRUE;
    $element['#element_validate'] = [[get_called_class(), 'validateAutocomplete']];
    // Build a unique ID to use so the fields can find each other in JavaScript.
    $unique_id = Html::getUniqueId('friendly-autocomplete');
    // Build the label field.
    $element['autocomplete_label'] = [
      '#type' => 'textfield',
      '#default_value' => !empty($existing_values['label']) ? $existing_values['label'] : '',
      '#required' => $element['#required'],
      '#autocomplete_route_name' => $element['#autocomplete_route_name'],
      '#attributes' => [
        'friendly-autocomplete-id' => $unique_id,
        $unique_id => 'label',
        'autocomplete' => 'off',
      ],
      '#maxlength' => !empty($element['#maxlength']) ? $element['#maxlength'] : 128,
    ];
    if (!empty($element['#autocomplete_route_parameters'])) {
      $element['autocomplete_label']['#autocomplete_route_parameters'] = $element['#autocomplete_route_parameters'];
    }
    // Build the hidden value field.
    $element['autocomplete_value'] = [
      '#type' => 'hidden',
      '#default_value' => !empty($existing_values['value']) ? $existing_values['value'] : '',
      '#attributes' => [
        'friendly-autocomplete-id' => $unique_id,
        $unique_id => 'value',
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      return [
        'autocomplete_label' => '',
        'autocomplete_value' => $element['#default_value'],
      ];
    }
    $value = ['autocomplete_label' => '', 'autocomplete_value' => ''];
    // Throw out all invalid array keys; we only allow autocomplete_label and
    // autocomplete_value.
    foreach ($value as $allowed_key => $default) {
      // These should be strings, but allow other scalars since they might be
      // valid input in programmatic form submissions. Any nested array values
      // are ignored.
      if (isset($input[$allowed_key]) && is_scalar($input[$allowed_key])) {
        $value[$allowed_key] = (string) $input[$allowed_key];
      }
    }
    return $value;
  }

  /**
   * Validates a Friendly Autocomplete form element.
   */
  public static function validateAutocomplete(&$element, FormStateInterface $form_state, &$complete_form) {
    // Get the submitted label.
    $label = trim($element['autocomplete_label']['#value']);
    // Get the submitted value.
    $value = trim($element['autocomplete_value']['#value']);
    // If the label is empty, set the value to an empty string too.
    if (empty($label)) {
      $value = '';
    }
    // Check if the field is required, throw an error if it is and no value was
    // given.
    if (!empty($element['#required']) && empty($value)) {
      $form_state->setError($element['autocomplete_label'], t('@name field is required.', ['@name' => $element['#title']]));
    }

    // Autocomplete field must be converted from a two-element array into a
    // single string regardless of validation results.
    $form_state->setValueForElement($element['autocomplete_label'], NULL);
    $form_state->setValueForElement($element['autocomplete_value'], NULL);
    $form_state->setValueForElement($element, $value);

    return $element;
  }

  /**
   * Get the label and value from the callback given a value.
   *
   * @param string $id
   *   The value to check against the callback.
   * @param string $route_name
   *   The route name for the callback.
   * @param bool $by_id
   *   (optional) Whether or not to search by ID. Defaults to TRUE.
   *
   * @return array
   *   Either an empty array, or an associative array containing label and value
   *   keys.
   */
  protected static function getDataFromId(string $id, string $route_name, bool $by_id = TRUE): array {
    // Try to get data from the callback without making a HTTP request.
    try {
      // Build a URL to the route.
      $uri = Url::fromRoute($route_name, [])->toString();
      // Build query parameters for the request.
      $query_parameters = [
        'q' => $id,
      ];
      if (!empty($by_id)) {
        $query_parameters['by_id'] = 'true';
      }
      // Get the request stack service.
      /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
      $request_stack = \Drupal::service('request_stack');
      // Get the current request.
      $current_request = $request_stack->getCurrentRequest();
      // Build the request object.
      $request = Request::create($uri, 'GET', $query_parameters, $current_request->cookies->all(), [], $current_request->server->all());
      // Get the HTTP kernel.
      /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
      $http_kernel = \Drupal::service('http_kernel');
      // Get the response from the callback.
      $response = $http_kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
      // Pop the request manually, since it is no longer needed and can
      // interfere with the rest of page execution.
      $request_stack->pop();
      // Decode the contents.
      $contents = Json::decode($response->getContent());
      // If the contents are empty, return an empty array.
      if (empty($contents)) {
        return [];
      }

      return reset($contents);
    }
    catch (\Exception $e) {
      // Fail simply by returning an empty array.
      return [];
    }
  }

}
