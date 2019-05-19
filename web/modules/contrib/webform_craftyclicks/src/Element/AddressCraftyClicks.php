<?php

namespace Drupal\webform_craftyclicks\Element;

use Drupal\webform\Element\WebformAddress;
use Drupal\webform\Element\WebformCompositeFormElementTrait;

/**
 * Provides a form element for an address element with Crafty Clicks postcode lookup.
 *
 * @FormElement("webform_address_craftyclicks")
 */
class AddressCraftyClicks extends WebformAddress {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {

    $elements['postcode'] = [
      '#type' => 'textfield',
      '#title' => t('Postcode'),
    ];

    $elements['company'] = [
      '#type' => 'textfield',
      '#title' => t('Company'),
    ];

    $elements['address1'] = [
      '#type' => 'textfield',
      '#title' => t('Address'),
    ];
    $elements['address2'] = [
      '#type' => 'textfield',
    ];
    $elements['address3'] = [
      '#type' => 'textfield',
    ];

    $elements['town'] = [
      '#type' => 'textfield',
      '#title' => t('Town/City'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderWebformCompositeFormElement($element) {
    $element = WebformCompositeFormElementTrait::preRenderWebformCompositeFormElement($element);

    $element['#attached']['library'][] = 'webform_craftyclicks/webform-craftyclicks';

    // #weight doesn't seem to work, so we:
    // split the element array after the 'postcode' key,
    // then insert extra Crafty Clicks stuff,
    // then re-attach remainder of element array at end of function.
    $index_after_postcode = array_search('postcode', array_keys($element)) + 1;
    $element_after_postcode = array_slice($element, $index_after_postcode);
    $element = array_slice($element, 0, $index_after_postcode);
    $onclick = 'cp_obj.doLookup(); return false;';
    $element['crafty_postcode_lookup_template'] = [
      '#type' => 'inline_template',
      '#title' => t('Postcode lookup template'),
      '#template' => "<button type='button' onclick='$onclick'>{{ label }}</button>\n",
      '#context' => [
        'label' => t('Find Address'),
      ]
    ];
    $element['result'] = [
      '#type' => 'markup',
      '#title' => t('Postcode lookup result'),
      '#prefix' => '<div id="crafty_postcode_result_display">',
      '#markup' => '<!-- Crafty Lookup Result Placeholder -->',
      '#suffix' => '</div>',
    ];

    $access_token = \Drupal::config('webform_craftyclicks.settings')->get('access_token');
    if (!isset($access_token)) {
      drupal_set_message('Crafty Clicks access token must be set in server configuration', 'error');
    }
    $element['crafty_token'] = [
      '#type' => 'inline_template',
      '#template' => '<input name="crafty_token" type="hidden" value="' . $access_token . '" >',
      '#value' => $access_token
    ];

    // Finally re-attach remainder of element array
    $element += $element_after_postcode;

    return $element;
  }

}
