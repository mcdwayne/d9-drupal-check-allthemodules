<?php

namespace Drupal\commerce_opp\Plugin\Commerce\PaymentGateway;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Open Payment Platform COPYandPAY gateway for bank accounts.
 *
 * @CommercePaymentGateway(
 *   id = "opp_copyandpay_bank",
 *   label = "Open Payment Platform COPYandPAY (bank transfer)",
 *   display_label = "Bank transfer",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_opp\PluginForm\CopyAndPayForm",
 *   },
 * )
 */
class CopyAndPayBankAccount extends CopyAndPayBase implements CopyAndPayBankAccountInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'sofort_countries' => [],
      'sofort_restrict_billing' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['brands']['sofort_countries'] = [
      '#type' => 'select',
      '#title' => $this->t("Available countries for SOFORT Überweisung"),
      '#multiple' => TRUE,
      '#options' => $this->getAvailableSofortCountries(),
      '#default_value' => isset($this->configuration['sofort_countries']) ? $this->configuration['sofort_countries'] : '',
      '#empty_value' => '',
      '#attributes' => ['size' => 6],
      '#states' => [
        'visible' => [
          ':input[name="configuration[opp_copyandpay_bank][brands][brands]"]' => ['value' => 'SOFORTUEBERWEISUNG'],
        ],
        'required' => [
          ':input[name="configuration[opp_copyandpay_bank][brands][brands]"]' => ['value' => 'SOFORTUEBERWEISUNG'],
        ],
      ],
    ];

    $form['brands']['sofort_restrict_billing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Further restrict SOFORT countries on billing address'),
      '#default_value' => $this->configuration['sofort_restrict_billing'],
      '#states' => [
        'visible' => [
          ':input[name="configuration[opp_copyandpay_bank][brands][brands]"]' => ['value' => 'SOFORTUEBERWEISUNG'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['sofort_countries'] = $values['brands']['sofort_countries'];
      $this->configuration['sofort_restrict_billing'] = $values['brands']['sofort_restrict_billing'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSofortCountries() {
    $selected_countries = $this->configuration['sofort_countries'];
    $selected_countries = array_combine($selected_countries, $selected_countries);
    return array_intersect_key($this->getAvailableSofortCountries(), $selected_countries);
  }

  /**
   * {@inheritdoc}
   */
  public function isSofortRestrictedToBillingAddress() {
    return (bool) $this->configuration['sofort_restrict_billing'];;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBrandOptions() {
    return $this->brandRepository->getBankAccountBrandLabels();
  }

  /**
   * Returns an array suitable for select list of available SOFORT countries.
   *
   * The possible options are taken from here:
   * https://docs.aciworldwide.com/tutorials/integration-guide/widget-api,
   *
   * @return string[]
   *   A list of all available SOFORT Überweisung countries.
   */
  protected function getAvailableSofortCountries() {
    return [
      'DE' => 'Deutschland',
      'NL' => 'Nederland',
      'AT' => 'Osterreich',
      'BE' => 'Belgique',
      'CH' => 'Schweiz',
      'GB' => 'United Kingdom',
      'ES' => 'España',
      'IT' => 'Italia',
      'PL' => 'Polska',
    ];
  }

}
