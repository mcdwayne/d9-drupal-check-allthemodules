<?php

namespace Drupal\cookie_content_blocker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Form builder to manage settings related to consent awareness.
 *
 * @package Drupal\cookie_content_blocker\Form
 */
class ConsentSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cookie_content_blocker_consent_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['cookie_content_blocker.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $form['consent_awareness'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => $this->t('Consent awareness'),
      '#description' => $this->t('Manage how Cookie content blocker knows about cookie consent and how it can change the consent. Note: only one cookie category is supported.'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
      'accepted' => [
        '#type' => 'details',
        '#title' => $this->t('Consent given'),
        '#description' => $this->t('Define the event that is triggered when a visitor actively gives consent and the cookies to determine if consent already has been given earlier.'),
        '#collapsible' => TRUE,
        '#open' => FALSE,
      ],
      'declined' => [
        '#type' => 'details',
        '#title' => $this->t('Consent refused'),
        '#description' => $this->t('Define the event that is triggered when a visitor actively declines consent and the cookies to determine if consent already has been declined earlier.'),
        '#collapsible' => TRUE,
        '#open' => FALSE,
      ],
      'change' => [
        '#type' => 'details',
        '#title' => $this->t('Consent changed'),
        '#description' => $this->t('Define the event that has to be triggered when the button or blocked content placeholder is clicked.'),
        '#collapsible' => TRUE,
        '#open' => FALSE,
      ],
    ];

    foreach (Element::children($form['consent_awareness']) as $event_type) {
      $form['consent_awareness'][$event_type]['event'] = $this->eventFormContainer($event_type);

      if ($event_type === 'change') {
        continue;
      }

      $form['consent_awareness'][$event_type]['cookie'] = $this->cookieFormContainer($event_type);
    }

    return $form;
  }

  /**
   * Makes sure whitespaces are stripped off element values.
   *
   * @param array $element
   *   The element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function stripWhitespaces(array $element, FormStateInterface $form_state): void {
    $form_state->setValueForElement($element, \trim($element['#value']));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $config = $this->config('cookie_content_blocker.settings');
    $config->set('consent_awareness', $form_state->getValue('consent_awareness'));
    $config->save();
  }

  /**
   * Returns a list of allowed cookie value operator options.
   *
   * @return array
   *   The cookie value operators.
   */
  private function cookieValueOperators() : array {
    return [
      '===' => $this->t('Equals'),
      '>' => $this->t('Greater than'),
      '<' => $this->t('Less than'),
      'c' => $this->t('Contains'),
      'e' => $this->t('Exists'),
    ];
  }

  /**
   * Create a container to configure cookie settings.
   *
   * @param string $type
   *   The type of the event.
   *
   * @return array
   *   The event form container.
   */
  private function cookieFormContainer(string $type): array {
    $defaults = $this->config('cookie_content_blocker.settings')->get('consent_awareness.' . $type . '.cookie') ?? [];
    $container = [];

    $container['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Cookie comparison operator'),
      '#description' => $this->t('The operator to use to compare the actual cookie value to the configured value'),
      '#default_value' => $defaults['operator'] ?? '',
      '#empty_option' => $this->t('- Select -'),
      '#options' => $this->cookieValueOperators(),
      '#element_validate' => [[$this, 'stripWhitespaces']],
    ];

    $container['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie name'),
      '#description' => $this->t('The name of the cookie to check for consent.'),
      '#default_value' => $defaults['name'] ?? '',
      '#element_validate' => [[$this, 'stripWhitespaces']],
    ];

    $container['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie value'),
      '#description' => $this->t('The value to use for comparison.'),
      '#default_value' => $defaults['value'] ?? '',
      '#element_validate' => [[$this, 'stripWhitespaces']],
    ];

    return $container;
  }

  /**
   * Create a container to configure event settings.
   *
   * @param string $type
   *   The type of the event.
   *
   * @return array
   *   The event form container.
   */
  private function eventFormContainer(string $type): array {
    $defaults = $this->config('cookie_content_blocker.settings')->get('consent_awareness.' . $type . '.event') ?? [];
    $container = [];
    $container['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JavaScript event name'),
      '#description' => $this->t('The event for the element selected below.'),
      '#default_value' => $defaults['name'] ?? '',
      '#element_validate' => [[$this, 'stripWhitespaces']],
    ];

    $container['selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JavaScript event DOM element selector'),
      '#description' => $this->t("The jQuery selector of the DOM element the above event is associated with, omit the jQuery('') or $('') part. E.g. 'window' or '.some-class > .other-child-class' (without quotes)."),
      '#default_value' => $defaults['selector'] ?? '',
      '#element_validate' => [[$this, 'stripWhitespaces']],
    ];

    return $container;
  }

}
