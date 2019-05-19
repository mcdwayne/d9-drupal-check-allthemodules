<?php

/**
 * @file
 * Contains \Drupal\smart_ip\Plugin\Condition\UserCountry.
 */


namespace Drupal\smart_ip\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides 'Countries' condition.
 *
 * @Condition(
 *   id = "countries",
 *   label = @Translation("Countries"),
 * )
 */
class UserCountry extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    module_load_include('inc', 'smart_ip', 'includes/smart_ip.country_list');
    $countries = country_get_predefined_list();
    $form['countries'] = [
      '#type'          => 'select',
      '#multiple'      => TRUE,
      '#size'          => 8,
      '#attached'      => ['library' => ['smart_ip/drupal.smart_ip.block.summary']],
      '#title'         => $this->t('Countries'),
      '#default_value' => $this->configuration['countries'],
      '#options'       => $countries,
      '#description'   => $this->t('Select one or more countries. Select none if all countries.'),
    ];
    $form['negate'] = [
      '#type' => 'radios',
      '#default_value' => (int) $this->configuration['negate'],
      '#title_display' => 'invisible',
      '#options' => [
        $this->t('Show to visitors located in countries selected below'),
        $this->t('Hide to visitors located in countries selected below'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'countries' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['countries'] = array_filter($form_state->getValue('countries'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $countryCodes = $this->configuration['countries'];
    module_load_include('inc', 'smart_ip', 'includes/smart_ip.country_list');
    $allCountries = country_get_predefined_list();
    foreach ($countryCodes as $countryCode) {
      $countries[] = $allCountries[$countryCode];
    }
    $countries = implode(', ', $countries);
    if (!empty($this->configuration['negate'])) {
      return $this->t('Do not return true on the following countries: @countries', ['@countries' => $countries]);
    }
    return $this->t('Return true on the following countries: @countries', ['@countries' => $countries]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $countries = $this->configuration['countries'];
    if (empty($countries)) {
      return TRUE;
    }
    /** @var \Drupal\smart_ip\SmartIpLocation $location */
    $location    = \Drupal::service('smart_ip.smart_ip_location');
    $userCountry = $location->get('countryCode');
    if (empty($userCountry)) {
      // Can't identify visitor's location then show this block.
      return TRUE;
    }
    return (bool) isset($countries[$userCountry]) xor $this->isNegated();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    return $contexts;
  }

}
