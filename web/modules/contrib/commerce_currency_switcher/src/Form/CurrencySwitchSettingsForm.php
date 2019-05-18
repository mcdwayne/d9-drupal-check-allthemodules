<?php

namespace Drupal\commerce_currency_switcher\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class CurrencySwitchSettingsForm.
 *
 * @package Drupal\commerce_currency_switcher\Form
 */
class CurrencySwitchSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_currency_switcher.settings');
    $conversion_settings = $config->get('conversion_settings');
    $use_cross_sync = $config->get('use_cross_conversion');
    $demo_amount = $config->get('demo_amount');

    /* @var CurrencyManager $currency_manager */
    $currency_manager = \Drupal::service('commerce_multicurrency.currency_manager');

    /* @var \Drupal\commerce_price\Entity\Currency $default_currency */
    $default_currency = $currency_manager->getDefaultCommerceCurrency();
    $default_currency_code = $default_currency->getCurrencyCode();

    $url = Url::fromUri('https://www.drupal.org/project/geoip', ['attributes' => ['target' => '_blank']]);
    $link = Link::fromTextAndUrl('Geoip module', $url);

    $form['#tree'] = TRUE;

    $form['geoip_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Geoip based currency selection.'),
      '#default_value' => $config->get('geoip_enable'),
      '#description' => $this->t('This setting will only work if @link is enabled and configured correctly.', ['@link' => $link->toString()]),
    ];

    $form['enable_conversion'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable currency conversion instead of fixed prices set in various currencies for product.'),
      '#default_value' => $config->get('enable_conversion'),
      '#description' => $this->t("Once enabled, it is required to set exchange rates for enabled currencies."),
    ];

    if (!$config->get('enable_conversion')) {
      return parent::buildForm($form, $form_state);
    }

    $entity_type_manager = \Drupal::service('entity_type.manager');
    $enabled_currencies = $active_currencies = $entity_type_manager->getStorage('commerce_currency')
      ->loadByProperties([
        'status' => TRUE,
      ]);

    $form['use_cross_sync'] = [
      '#type' => 'checkbox',
      '#default_value' => $use_cross_sync,
      '#title' => t('Use cross conversion between non default currencies.'),
      '#description' => t('If enabled only the rates between the default currency and the other currencies have to be managed. The rates between the other currencies is derived from their rates relative to the default currency.'),
    ];

    $form['demo_amount'] = [
      '#type' => 'textfield',
      '#title' => t('Amount for example conversions:'),
      '#size' => 5,
      '#default_value' => $demo_amount,
    ];

    if (count($enabled_currencies) > 1) {
      /* @var \Drupal\commerce_price\Entity\Currency $currency */
      foreach ($enabled_currencies as $currency_code => $currency) {
        if ($use_cross_sync && $currency_code != $default_currency_code) {
          continue;
        }
        if (!isset($conversion_settings[$currency_code])) {
          $conversion_settings[$currency_code] = [];
        }
        $conversion_settings[$currency_code] += [
          'sync' => '1',
          'rates' => [],
        ];

        $form['conversion_settings'][$currency_code] = [
          '#type' => 'details',
          '#title' => $currency->label(),
        ];
        if ($currency_code == $default_currency_code) {
          $form['conversion_settings'][$currency_code]['#weight'] = -1;
        }
        /*$form['conversion_settings'][$currency_code]['sync'] = [
          '#type' => 'checkbox',
          '#title' => t('Synchronize all rates of this currency.'),
          '#states' => [
            'checked' => [
              '#edit-' . $currency_code . ' fieldset input[type="checkbox"]' => ['checked' => TRUE],
            ],
          ],
          '#default_value' => $conversion_settings[$currency_code]['sync'],
        ];*/

        /* @var \Drupal\commerce_price\Entity\Currency $conversion_currency */
        foreach ($enabled_currencies as $conversion_currency_code => $conversion_currency) {
          if ($conversion_currency_code == $currency_code) {
            continue;
          }
          if (!isset($conversion_settings[$currency_code]['rates'][$conversion_currency_code])) {
            $conversion_settings[$currency_code]['rates'][$conversion_currency_code] = [];
          }
          $conversion_settings[$currency_code]['rates'][$conversion_currency_code] += [
            'sync' => '1',
            'rate' => 0,
          ];

          $form['conversion_settings'][$currency_code]['sync']['#states']['checked']['input[name="conversion_settings[' . $currency_code . '][rates][' . $conversion_currency_code . '][sync]"]'] = ['checked' => TRUE];

          $form['conversion_settings'][$currency_code]['rates'][$conversion_currency_code] = [
            '#type' => 'details',
            '#attributes' => ['class' => ['conversion-rates']],
            '#open' => TRUE,
            '#title' => $conversion_currency_code,
          ];
          /*$form['conversion_settings'][$currency_code]['rates'][$conversion_currency_code]['sync'] = [
            '#type' => 'checkbox',
            '#title' => t('Synchronize this conversion rate.'),
            '#default_value' => $conversion_settings[$currency_code]['rates'][$conversion_currency_code]['sync'],
          ];*/
          $form['conversion_settings'][$currency_code]['rates'][$conversion_currency_code]['rate'] = [
            '#type' => 'textfield',
            '#title' => t('Exchange rate'),
            '#attributes' => ['class' => ['conversion-rate']],
            '#description' => t(
              'Exchange rate from @currency_code to @conversion_currency_code.',
              [
                '@currency_code' => $currency->label(),
                '@conversion_currency_code' => $conversion_currency->label(),
              ]
            ),
            /*'#states' => [
              'disabled' => [
                'input[name="conversion_settings[' . $currency_code . '][rates][' . $conversion_currency_code . '][sync]"]' => ['checked' => TRUE],
              ],
            ],*/
            '#size' => 13,
            //            '#element_validate' => ['commerce_currency_switcher_form_rate_validate'],
            '#default_value' => $conversion_settings[$currency_code]['rates'][$conversion_currency_code]['rate'],
            '#field_suffix' => t(
              '* @demo_amount @currency_symbol = @amount @conversion_currency_symbol',
              [
                '@demo_amount' => $demo_amount,
                '@currency_symbol' => $currency->getSymbol(),
                '@conversion_currency_symbol' => $conversion_currency->getSymbol(),
                '@amount' => $demo_amount * $conversion_settings[$currency_code]['rates'][$conversion_currency_code]['rate'],
              ]
            ),
          ];
        }
      }
    }
    else {
      drupal_set_message(t('Please enable the needed currencies to configure first.'), 'warning', FALSE);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'commerce_currency_switcher_form';
  }

  /**
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('commerce_currency_switcher.settings');

    $config->set('geoip_enable', $form_state->getValue('geoip_enable'))
      ->set('conversion_settings', $form_state->getValue('conversion_settings'))
      ->set('use_cross_conversion', $form_state->getValue('use_cross_sync'))
      ->set('demo_amount', $form_state->getValue('demo_amount'))
      ->set('enable_conversion', $form_state->getValue('enable_conversion'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_currency_switcher.settings'];
  }

}
