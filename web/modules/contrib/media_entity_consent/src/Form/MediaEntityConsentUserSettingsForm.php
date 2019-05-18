<?php

namespace Drupal\media_entity_consent\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity_consent\ConsentHelper;

/**
 * Configure example settings for this site.
 */
class MediaEntityConsentUserSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_entity_consent_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('media_entity_consent.settings');
    $already_set_cookies = ConsentHelper::getConsentCookies();
    $consent_count = 0;

    foreach ((array) $config->get('media_types') as $type => $settings) {
      if ($settings['enabled']) {
        $consent_count++;

        $default_value = 0;
        if (isset($already_set_cookies[$type]) && $already_set_cookies[$type] == TRUE) {
          $default_value = 1;
        }

        $form[ConsentHelper::CONSENT_PREFIX . $type] = [
          '#type' => 'checkbox',
          '#title' => $settings['consent_question'],
          '#description' => $settings['privacy_policy']['value'],
          '#default_value' => $default_value,
          '#attributes' => [
            'data-consent-type' => $type,
          ],
          '#ajax' => [
            'callback' => '::submitForm',
            'event' => 'change',
            'wrapper' => 'media-entity-consent-user-form--wrapper',
            'progress' => [
              'type' => 'throbber',
            ],
          ],
        ];
      }
    }

    if ($consent_count == 0) {
      $form['empty_consent'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('There are no media entity consents activated.') . '</p>',
      ];
    }
    else {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#submit' => ['::submitForm'],
        '#name' => 'media-entity-consent-user-form--submit',
        '#attributes' => [
          'class' => ['js-hide'],
        ],
      ];
      $form['#submit'] = ['::submitForm'];
      $form['#prefix'] = '<div id="media-entity-consent-user-form--wrapper">';
      $form['#suffix'] = '</div>';

      $form['#attached']['drupalSettings']['mediaEntityConsent']['CONSENT_PREFIX'] = ConsentHelper::CONSENT_PREFIX;
      $form['#attached']['library'] = ['media_entity_consent/consent'];
      $form['#attached']['drupalSettings']['mediaEntityConsent']['libs'] = ConsentHelper::identifyExternalLibraries();

      // Set Cache accordingly.
      $config_tags = $config->getCacheTags();
      $form['#cache']['tags'] = array_merge($form['#cache']['tags'], $config_tags);
      $form['#cache']['contexts'][] = 'user.roles';
      $form['#cache']['contexts'][] = 'cookies';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $name => $value) {
      if (strpos($name, ConsentHelper::CONSENT_PREFIX) !== FALSE) {
        $type = str_replace(ConsentHelper::CONSENT_PREFIX, '', $name);
        ConsentHelper::setConsentCookie($type, $value);
      }
    }
    $form_state->setRebuild();
    return $form;
  }

}
