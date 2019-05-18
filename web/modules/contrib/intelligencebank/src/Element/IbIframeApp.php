<?php

namespace Drupal\ib_dam\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a intelligencebank iframe app form element.
 *
 * @FormElement("ib_dam_app")
 */
class IbIframeApp extends FormElement {

  const APP_URL = 'https://ucprod.intelligencebank.com/app/index.html';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $info = [
      '#input' => TRUE,
      '#markup' => '',
      '#process' => [[$class, 'processElement']],
      '#pre_render' => [[$class, 'preRenderElement']],
      '#theme_wrappers' => ['form_element'],
      '#file_extensions' => [],
      '#allow_embed' => FALSE,
      '#debug_response' => FALSE,
      '#submit_selector' => NULL,
      '#messages' => [],
      '#attached' => [
        'library' => ['ib_dam/browser'],
      ],
    ];
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function processElement(&$element) {
    $element['#tree'] = TRUE;

    // Embedded search.
    $element['browser'] = [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'id' => Html::getUniqueId('ib-dam-asset-browser'),
        'src' => '',
        'class' => 'ib-dam-app-browser',
        'width' => '100%',
        'frameborder' => 0,
        'style' => 'padding: 0; width: 1px !important; min-width: 100% !important; overflow: hidden !important;',
      ],
      '#prefix' => '<div class="ib-search-iframe-wrapper ib-dam-app-wrapper">',
      '#suffix' => '</div>',
    ];

    // Hold the response from the search.
    $element['response_items'] = [
      '#name' => 'ib_dam_app[response_items]',
      '#type' => 'hidden',
      '#default_value' => '',
    ];
    return $element;
  }

  /**
   * Add javascript settings for an element.
   */
  public static function preRenderElement(array $element) {
    $settings = [
      'host' => parse_url(static::APP_URL)['host'],
      'debug' => $element['#debug_response'],
      'allowEmbed' => $element['#allow_embed'],
      'submitSelector' => $element['#submit_selector'],
      'appUrl' => static::APP_URL,
    ];
    $settings['fileExtensions'] = isset($element['#file_extensions'])
      ? $element['#file_extensions']
      : [];

    $settings['messages'] = isset($element['#messages'])
      ? $element['#messages']
      : [];

    $element['#attached']['drupalSettings']['ib_dam']['browser'] = $settings;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $return['items'] = [];

    if ($input !== FALSE) {
      $user_input = NestedArray::getValue($form_state->getUserInput(), ['ib_dam_app']);

      if (!empty($user_input['response_items'])) {
        $return['items'] = [json_decode($user_input['response_items'])];
      }
      $form_state->setValueForElement($element, $return);
    }

    return $return;
  }

}
