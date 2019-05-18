<?php

/**
 * @file
 * Contains \Drupal\ip_language_negotiation\Form\IpLanguageNegotiationForm.
 */

namespace Drupal\ip_language_negotiation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IpLanguageNegotiationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ip_language_negotiation_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $countries = \Drupal::service('country_manager')->getList();
    $languages = \Drupal::languageManager()->getLanguages();
    $language_default = \Drupal::languageManager()->getDefaultLanguage();
    $settings = \Drupal::config('ip_language_negotiation.settings')->get('ip_language_negotiation_countries') ?: array();

    $ip2country_settings_link = \Drupal\Core\Url::fromRoute('ip2country.settings',
      array('fragment' => 'edit-ip2country-debug-preferences')
    )->toString();
    $form['intro'] = array(
      '#markup' => '<p>' . t('Use the interface below to select the default language per country. You only have to set the exceptions, because the default language will be used as fall-back. You can use the <a href="@url">Debug preferences</a> to test the module.', array('@url' => $ip2country_settings_link)) . '</p>',
    );


    // Remove the default language.
    unset($languages[$language_default->getId()]);

    // Build languages options array.
    $language_options = [
      '' => t('Default (@default_language)', [
        '@default_language' => $language_default->getName()
      ])
    ];
    foreach ($languages as $language) {
      $language_options[$language->getId()] = $language->getName();
    }

    $letter = '';
    foreach ($countries as $country_code => $country) {
      // Remove accents so we can sort countries correctly.
      $current_letter = iconv('UTF-8', 'ASCII//TRANSLIT', \Drupal\Component\Utility\Unicode::substr($country, 0, 1));

      if ($letter != $current_letter) {
        $letter = $current_letter;
        if (empty($form['ip_language_letter_' . $letter])) {
          $form['ip_language_letter_' . $letter] = [
            '#type' => 'fieldset',
            '#collapsible' => TRUE,
            '#collapsed' => TRUE,
            '#title' => t('Countries with the letter %letter', [
              '%letter' => $letter
              ]),
          ];
        }
      }
      $form['ip_language_letter_' . $letter][$country_code] = [
        '#type' => 'radios',
        '#options' => $language_options,
        '#title' => $country,
        '#default_value' => '',
      ];
      if (!empty($settings[$country_code])) {
        $form['ip_language_letter_' . $letter][$country_code]['#default_value'] = $settings[$country_code];
        $form['ip_language_letter_' . $letter]['#collapsed'] = FALSE;
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Exclude unnecessary elements.
    $form_state->cleanValues();
    \Drupal::configFactory()->getEditable('ip_language_negotiation.settings')->set('ip_language_negotiation_countries', $form_state->getValues())->save();
  }

}
