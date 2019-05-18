<?php

namespace Drupal\akismet\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\akismet\Entity\FormInterface;

/**
 * Provides a form element for storage of internal information.
 *
 * @FormElement("akismet")
 */
class Akismet extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => FALSE,
      '#process' => array(
        array($class, 'processAkismet'),
      ),
      '#tree' => TRUE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return $input;
  }

  /**
   * #process callback for #type 'akismet'.
   *
   * @see akismet_form_alter()
   * @see akismet_element_info()
   */
  public static function processAkismet(array $element, FormStateInterface $form_state, array $form) {
    $akismet = $form_state->getValue('akismet');
    $akismet = $akismet ? $akismet : [];
    // Allow overriding via hook_form_alter to set akismet override properties.
    if (isset($form['#akismet']) && is_array($form['#akismet'])) {
      $akismet += $form['#akismet'];
    }

    // Setup initial Akismet session and form information.
    $akismet += array(
      // Only TRUE if the form is protected by text analysis.
      'require_analysis' => $element['#akismet_form']['mode'] == FormInterface::AKISMET_MODE_ANALYSIS,
      // Becomes TRUE if the form is protected by text analysis and the submitted
      // entity should be unpublished.
      'require_moderation' => FALSE,
      // Internally used bag for last Akismet API responses.
      'response' => array(
      ),
    );

    $akismet_form_array = $element['#akismet_form'];
    $akismet += $akismet_form_array;

    // By default, bad form submissions are discarded, unless the form was
    // configured to moderate bad posts. 'discard' may only be FALSE, if there is
    // a valid 'moderation callback'. Otherwise, it must be TRUE.
    if (empty($akismet['moderation callback']) || !function_exists($akismet['moderation callback'])) {
      $akismet['discard'] = TRUE;
    }

    $form_state->setValue('akismet', $akismet);

    // Add the Akismet session data elements.
    // These elements resemble the {akismet} database schema. The form validation
    // handlers will pollute them with values returned by Akismet. For entity
    // forms, the submitted values will appear in a $entity->akismet property,
    // which in turn represents the Akismet session data record to be stored.
    $element['entity'] = array(
      '#type' => 'value',
      '#value' => isset($akismet['entity']) ? $akismet['entity'] : 'akismet_content',
    );
    $element['id'] = array(
      '#type' => 'value',
      '#value' => NULL,
    );
    $element['form_id'] = array(
      '#type' => 'value',
      '#value' => $akismet['id'],
    );
    $element['moderate'] = array(
      '#type' => 'value',
      '#value' => 0,
    );
    $element['classification'] = array(
      '#type' => 'value',
      '#value' => NULL,
    );
    $element['passed_validation'] = array(
      '#type' => 'value',
      '#value' => FALSE,
    );

    // Add link to privacy policy on forms protected via textual analysis,
    // if enabled.
    if ($akismet_form_array['mode'] == FormInterface::AKISMET_MODE_ANALYSIS && \Drupal::config('akismet.settings')->get('privacy_link')) {
      $element['privacy'] = array(
        '#prefix' => '<div class="description akismet-privacy">',
        '#suffix' => '</div>',
        '#markup' => t('By submitting this form, you accept the <a href="@privacy-policy-url" class="akismet-target" rel="nofollow">Akismet privacy policy</a>.', array(
          '@privacy-policy-url' => 'https://akismet.com/web-service-privacy-policy',
        )),
        '#weight' => 10,
      );
    }

    return $element;
  }

}
