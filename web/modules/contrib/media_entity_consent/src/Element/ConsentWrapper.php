<?php

namespace Drupal\media_entity_consent\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Component\Utility\Html;
use Drupal\media_entity_consent\ConsentHelper;

/**
 * Provides the consent_wrapper render element.entity_view_alter.
 *
 * @see plugin_api
 * @see render_example_theme()
 *
 * @RenderElement("consent_wrapper")
 */
class ConsentWrapper extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {

    // Returns an array of default properties that will be merged with any
    // properties defined in a render array when using this element type.
    // You can use any standard render array property here, and you can also
    // custom properties that are specific to your new element type.
    return [
      '#pre_render' => [
        [$this, 'preRenderConsent'],
      ],
    ];
  }

  /**
   * Pre-render callback; Process custom attribute options.
   *
   * If the user has a role to bypass or media_entity_consent is not activated
   * for this media bundle, it will return the normal media entity for rendering
   * but with added cache tags and contexts to react on changing of the settings
   * and / or role of the user.
   *
   * @param array $element
   *   The renderable array representing the element with
   *   '#type' => 'media_entity_consent_consent_wrapper'
   *   property set.
   *
   * @return array
   *   The passed in element with changes made to attributes depending on
   *   context.
   */
  public static function preRenderConsent(array $element) {
    $config = \Drupal::config('media_entity_consent.settings');
    $media_settings = $config->get('media_types');
    $media_display_modes = $config->get('display_bypass');
    $media_bundle = $element['#content']['#media']->bundle();
    $display_mode = $element['#content']['#view_mode'];
    $build_media = $element['#content'];
    $external_libs = ConsentHelper::identifyExternalLibraries();
    $external_js = isset($external_libs[$media_bundle]) ? $external_libs[$media_bundle] : [];

    if (array_key_exists($media_bundle, $media_settings)
      && !$media_display_modes[$display_mode]
      && $media_settings[$media_bundle]['enabled']
      && !ConsentHelper::userCanBypass()) {

      $element['#theme'] = 'media_entity_consent_wrapper';
      $element['#consent_id'] = Html::getUniqueId('media-entity-consent');
      $element['#media_bundle'] = $media_bundle;
      $element['#attributes']['class'] = [
        'media-entity-consent',
        'media-entity-consent--wrapper',
        ConsentHelper::CONSENT_PREFIX . $media_bundle,
        ConsentHelper::userHasGivenConsent($media_bundle) ? 'consent-given' : 'consent-denied',
      ];
      $element['#consent_status'] = ConsentHelper::userHasGivenConsent($media_bundle);
      $element['#attributes']['data-consent-id'] = [$element['#consent_id']];
      $element['#attributes']['id'] = [$element['#consent_id']];
      $element['#attributes']['data-consent-type'] = [$media_bundle];
      $element['#consent_form'] = self::buildConsentForm($element, $media_settings);
      $element['#consent_footer'] = ['#markup' => $media_settings[$media_bundle]['consent_footer']['value']];
    }
    else {
      // Return the unchanged media entity but with added cache contexts.
      $element = $build_media;
      $element['#attached']['drupalSettings']['mediaEntityConsent']['bypass'][] = $media_bundle;
    }

    // Set Cache accordingly.
    $config_tags = $config->getCacheTags();
    if (!isset($element['#cache'])) {
      $element['#cache'] = [];
    }
    if (!isset($element['#cache']['contexts'])) {
      $element['#cache']['contexts'] = [];
    }
    if (!isset($element['#cache']['tags'])) {
      $element['#cache']['tags'] = [];
    }
    $element['#cache']['tags'] = array_merge($element['#cache']['tags'], $config_tags);
    $element['#cache']['contexts'][] = 'user.roles';
    $element['#cache']['contexts'][] = 'cookies:Drupal_visitor_' . ConsentHelper::CONSENT_PREFIX . $media_bundle;

    // Add libraries and settings.
    $element['#attached']['drupalSettings']['mediaEntityConsent']['CONSENT_PREFIX'] = ConsentHelper::CONSENT_PREFIX;
    $element['#attached']['drupalSettings']['mediaEntityConsent']['libs'][$media_bundle] = $external_js;
    $element['#attached']['library'] = ['media_entity_consent/consent'];

    return $element;
  }

  /**
   * Build form for consent.
   *
   * @param array $element
   *   The renderable array representing the element with
   *   '#type' => 'media_entity_consent_consent_wrapper' property set.
   * @param array $settings
   *   The media_settings of media entity consent.
   *   Needed for getting the consent question.
   *
   * @return array
   *   The form render array.
   */
  public static function buildConsentForm(array $element, array $settings) {
    $form = [
      'check_' . $element['#consent_id'] => [
        '#type' => 'checkbox',
        '#id' => 'check-' . $element['#consent_id'],
        '#attributes' => [
          'data-consent-id' => $element['#consent_id'],
          'data-consent-type' => $element['#media_bundle'],
        ],
        '#title' => $settings[$element['#media_bundle']]['consent_question'],
      ],
    ];
    return $form;
  }

}
