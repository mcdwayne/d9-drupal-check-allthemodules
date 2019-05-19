<?php

namespace Drupal\toolshed\Element;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;

/**
 * Text field for entering an external URL.
 *
 * @FormElement("external_url")
 */
class ExternalUrlElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#schemes' => ['https'],
      '#allow_query' => FALSE,
      '#allow_fragment' => FALSE,
      '#process' => [
        static::class . '::processExternalUrl',
        static::class . '::processAjaxForm',
        static::class . '::processGroup',
      ],
      '#element_validate' => [
        static::class . '::validateExternalUrl',
      ],
      '#pre_render' => [],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Break the URL into a consistent set of components.
   *
   * @param string|array $input
   *   A URL string or an array already split into URL components.
   * @param array $element
   *   The URL element being built.
   *
   * @return array
   *   An array of URL values with the scheme, host, port and path. If the
   *   element is configured to allow query and fragment those will be
   *   included as well.
   */
  protected static function parseUrl($input, array $element) {
    if (is_string($input)) {
      $input = UrlHelper::parse($input);

      if (preg_match('#^(?:([a-z]+)://|//)?(.*)$#i', $input['path'], $matches)) {
        $input['scheme'] = $matches[1];
        $input['path'] = $matches[2];
        $input = array_filter($input);
      }
    }
    else {
      $parts = array_filter(UrlHelper::parse($input['path']));
      $input = $parts + $input;
    }

    // Which portions of the input array are valid and should be included.
    $allow = ['scheme' => TRUE, 'path' => TRUE];
    $input += [
      'scheme' => 'https',
      'path' => '',
    ];

    // Determine if queries and fragments are allowed.
    // Make sure to exclude them if they aren't allowed.
    if (!empty($element['#allow_query'])) {
      $allow['query'] = TRUE;
    }
    if (!empty($element['#allow_fragment'])) {
      $allow['fragment'] = TRUE;
    }

    return array_intersect_key($input, $allow);
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      return static::parseUrl($input, $element);
    }
  }

  /**
   * Process the textfield element into the form element components.
   *
   * @param array $element
   *   Reference to the form element array passed from the form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form build and state information.
   * @param array $complete_form
   *   Reference to the complete form definition.
   *
   * @return array
   *   The processed element.
   */
  public static function processExternalUrl(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['#tree'] = TRUE;

    $values = $element['#value'];
    $values = is_string($values) ? static::parseUrl($values, $element) : $values;
    $pathValue = $values['path'];

    if (!empty($element['#allow_query']) && !empty($values['query'])) {
      $pathValue .= '?' . UrlHelper::buildQuery($values['query']);
    }
    if (!empty($element['#allow_fragment']) && !empty($values['fragment'])) {
      $pathValue .= '#' . $values['fragment'];
    }

    $pathInput = [
      '#theme_wrappers' => [],
      '#type' => 'textfield',
      '#default_value' => $pathValue,
    ];

    // Allow the scheme to be selected if there is more than one option.
    // Otherwise provide a value.
    if (count($element['#schemes']) > 1) {
      $element['scheme'] = [
        '#theme_wrappers' => [],
        '#type' => 'select',
        '#options' => array_combine($element['#schemes'], $element['#schemes']),
        '#default_value' => $values['scheme'],
      ];
      $element['scheme_suffix']['#plain_text'] = '://';
      $element['path'] = $pathInput;
    }
    else {
      $scheme = !empty($element['#schemes']) ? reset($element['#schemes']) : 'https';
      $element['scheme'] = [
        '#type' => 'value',
        '#value' => $scheme,
      ];
      $element['path_prefix']['#plain_text'] = $scheme . '://';
      $element['path'] = $pathInput;
    }

    return $element;
  }

  /**
   * Validate that the CSS classes entered here are in a valid CSS format.
   *
   * @param array $element
   *   Array definition of this css class element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object, containing the build, info and values of the
   *   current form.
   */
  public static function validateExternalUrl(array $element, FormStateInterface $form_state) {
    $path = $element['path']['#value'];
    $url = $element['scheme']['#value'] . '://' . $path;

    if (empty($path)) {
      return;
    }

    if (!UrlHelper::isValid($url, TRUE)) {
      $form_state->setError($element, t(
        'URL does not appear in a valid format as a proper external URL.'
      ));
    }

    if (!$element['#allow_query'] && strpos($path, '?') !== FALSE) {
      $form_state->setError($element['path'], t(
        'A URL query is provided but is not allowed for this URL.'
      ));
    }
    if (!$element['#allow_fragment'] && strpos($path, '#') !== FALSE) {
      $form_state->setError($element['path'], t(
        'A URL fragment is included in the path, but not allowed for as part of this URL.'
      ));
    }
  }

}
