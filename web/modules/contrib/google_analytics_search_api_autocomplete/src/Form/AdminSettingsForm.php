<?php

namespace Drupal\google_analytics_search_api_autocomplete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Displays the Google Analytics Search API Autocomplete settings form.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_analytics_search_api_autocomplete_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get autocomplete events form config.
    $config = $this->config('google_analytics_search_api_autocomplete.admin_settings');
    $events = $config->get('autocomplete_events');

    // Prepare options for checkboxes.
    $options = [];
    foreach ($events as $event => $value) {
      // To avoid confusion, uase the same labels as on jQuery UI API pages.
      $label = str_replace('autocomplete', '', $event);
      $options[$event] = $label;
    }

    // Prepare default values for checkboxes.
    $values = [];
    foreach ($events as $event => $value) {
      if ($value) {
        $values[] = $event;
      }
    }

    // Wrap all options within fieldset.
    $form['autocomplete_events'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select Tracking'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    ];

    // Some small help text.
    $form['autocomplete_events']['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'small',
      '#value' => $this->t('Select jQUery UI autocomplete events which you want to track. Eeach customized label will be transformed into eventAction property of corresponding GA event.'),
    ];

    foreach ($events as $event => $value) {
      $form['autocomplete_events'][$event] = [
        'enabled' => [
          '#type' => 'checkbox',
          '#title' => $options[$event],
          '#default_value' => $value['enabled'] ? TRUE : FALSE,
        ],
        'label' => [
          '#type' => 'textfield',
          '#title' => $this->t('Label'),
          '#title_display' => 'invisible',
          '#attributes' => [
            'placeholder' => $this->t('Add custom label'),
          ],
          '#default_value' => isset($value['label']) ? $value['label'] : '',
          '#states' => [
            'visible' => [
              ':input[name="autocomplete_events[' . $event . '][enabled]"]' => [
                'checked' => TRUE,
              ],
            ],
          ],
        ],
      ];
    }

    // Additional help in the bottom.
    $form['autocomplete_events']['help'] = [
      '#type' => 'html_tag',
      '#tag' => 'small',
      '#value' => $this->t('Visit <a href=":jqueryapiurl">jQuery UI API documentation</a> pages to get more details about autocomplete events.', [':jqueryapiurl' => Url::fromUri('http://api.jqueryui.com/autocomplete/#events')->toString()]),
    ];

    // This gives user ability to push GA Events from local environment.
    $form['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set cookieDomain to "none"'),
      '#default_value' => $config->get('debug_mode'),
      '#description' => $this->t('Useful for debugging on local environment, this will set cookieDomain of ga tracker to "none", see  <a href=":gadocs">Google Analytics documentation</a>.', [':gadocs' => Url::fromUri('https://developers.google.com/analytics/devguides/collection/analyticsjs/cookies-user-id#automatic_cookie_domain_configuration')->toString()]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('google_analytics_search_api_autocomplete.admin_settings');

    // Extract checkboxes configured by user and transform them
    // into array ready to save in config object.
    $events = $form_state->getValue('autocomplete_events');
    foreach ($events as $event => $value) {
      $events[$event]['enabled'] = !$value['enabled'] ? 0 : 1;
      $events[$event]['label'] = $value['label'] ? $value['label'] : FALSE;
    }

    // Update configuration of tracked ui.autocomplete events.
    $config->set('autocomplete_events', $events)->save();

    // Configure debug mode.
    $config->set('debug_mode', $form_state->getValue('debug_mode'));

    // Save updated configuration.
    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['google_analytics_search_api_autocomplete.admin_settings'];
  }

}
