<?php

namespace Drupal\stripe\Element;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Hidden;

/**
 * Provides a form element that will be rendered by stripe elements.
 *
 * @see https://stripe.com/docs/elements
 *
 * Usage example:
 * @code
 * @endcode
 * *
 * @FormElement("stripe")
 */
class Stripe extends Hidden {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);

    $info['#process'][] = [$class, 'processStripe'];
    $info['#pre_render'][] = [$class, 'preRenderStripe'];
    $info['#theme_wrappers'] = [
      'stripe_element' => [],
      'form_element' => [],
    ];

    // The selectors are gonna be looked within the enclosing form only.
    $info['#stripe_selectors'] = [
      'name' => '',
      'first_name' => '',
      'last_name' => '',
      'address_line1' => '',
      'address_line2' => '',
      'address_city' => '',
      'address_state' => '',
      'address_zip' => '',
      'address_country' => '',
    ];
    return $info;
  }

  /**
   * Prepares a 'stripe' #type element for rendering.
   */
  public static function preRenderStripe($element) {
    Element::setAttributes($element, ['id']);
    $selectors = array_filter($element['#stripe_selectors']);
    if (!empty($selectors)) {
      $element['#attributes']['data-drupal-stripe-selectors'] = JSON::encode($element['#stripe_selectors']);
    }

    return $element;
  }

  /**
   * Processes a stripe element.
   */
  public static function processStripe(&$element, FormStateInterface $form_state, &$complete_form) {
    $config = \Drupal::config('stripe.settings');
    $element['#attached']['library'][] = 'stripe/stripe.js';
    $apikey = $config->get('apikey.' . $config->get('environment') . '.public');
    $element['#attached']['drupalSettings']['stripe']['apiKey'] = $apikey;
    if (empty($apikey)) {
      $settings_uri = \Drupal\Core\Url::fromRoute('stripe.settings')->toString();
      drupal_set_message(t('You must <a href="@settings_uri">configure</a> the <b>Publishable API Key</b> on <b>%environment</b> for the stripe elements to work.', ['%environment' => $config->get('environment'), '@settings_uri' => $settings_uri]), 'error');
    }
    $settings = [];
    $element['#attached']['drupalSettings']['stripe']['elements'][$element['#id']] = $settings;
    return $element;
  }

}
