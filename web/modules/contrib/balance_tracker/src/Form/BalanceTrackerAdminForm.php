<?php

/**
 * @file
 * Contains \Drupal\balance_tracker\Form\BalanceTrackerAdminForm.
 */

namespace Drupal\balance_tracker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

class BalanceTrackerAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'balance_tracker_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('balance_tracker.settings');
    $form['currency'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Currency Settings'),
      '#tree' => TRUE,
    ];
    $form['currency']['symbol'] = [
      '#type' => 'textfield',
      '#title' => 'Currency Symbol',
      '#size' => 5,
      '#default_value' => $config->get('currency_symbol'),
      '#maxlength' => 15,
      '#description' => $this->t('Please enter the currency symbol you would like to use here. You may also look at <a href="http://www.xe.com/symbols.php">a list of currency symbols</a>.'),
    ];
    $form['currency']['symbol_position'] = [
      '#type' => 'radios',
      '#title' => $this->t('Symbol Position'),
      '#default_value' => $config->get('currency_symbol_position'),
      '#options' => [
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
      ],
      '#description' => $this->t('This controls whether the currency symbol appears before or after the currency value.'),
    ];
    $form['currency']['thousands_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Thousands Separator'),
      '#default_value' => $config->get('thousands_separator'),
      '#size' => 3,
      '#maxlength' => 1,
      '#description' => $this->t('Please set a thousands separator.'),
    ];
    $form['currency']['decimal_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Decimal Separator'),
      '#default_value' => $config->get('decimal_separator'),
      '#size' => 3,
      '#maxlength' => 1,
      '#description' => $this->t('Please set a decimal separator.'),
    ];
    $form['date'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Date Settings'),
      '#tree' => TRUE,
    ];
    $form['date']['format'] = [
       '#type' => 'radios',
       '#title' => $this->t('Date Format'),
       '#default_value' => $config->get('date_format'),
       '#options' => [
         'small' => $this->t('Short: @time', array('@time' => format_date(REQUEST_TIME, 'small'))),
         'medium' => $this->t('Medium: @time', array('@time' => format_date(REQUEST_TIME, 'medium'))),
         'large' => $this->t('Long: @time', array('@time' => format_date(REQUEST_TIME, 'large'))),
         'custom' => $this->t('Custom'),
       ],
       '#description' => $this->t('Please select a date format above. Short, Medium, and Long formats can be configured in the <a href=":link">Date and Time settings</a>.',
         array(':link' => Url::fromRoute('entity.date_format.collection')->toString())),
     ];

    $form['date']['custom_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Date Format'),
      '#default_value' => $config->get('custom_date_format'),
      '#size' => 20,
      '#maxlength' => 20,
      '#description' => $this->t('If you have chosen a Custom format above, please enter it here. See the <a href=":link">PHP Manual</a> for available format options.', [
        ':link' => 'http://php.net/manual/function.date.php'
        ]),
    ];
    $form['display'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Display settings'),
      '#tree' => TRUE,
    ];
    $form['display']['show_in_profile'] = [
      '#type' => 'radios',
      '#title' => $this->t('User profile'),
      '#default_value' => $config->get('show_in_profile') ? 'show' : 'noshow',
      '#options' => [
        'show' => $this->t('Show balance in user profile.'),
        'noshow' => $this->t('Do not show balance in user profile.'),
      ],
      '#description' => $this->t("Please select whether the user's balance should be shown in the user profile. Visibility is restricted via the 'view own balance' and 'view all balances' permissions."),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['date', 'format']) === 'custom' && $form_state->getValue(['date','custom_format']) == NULL) {
      $form_state->setErrorByName('date', $this->t('You must specify a format string if you have chosen to use a custom date format.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save currency settings.
    $config = $this->config('balance_tracker.settings');
    $config
      ->set('currency_symbol', $form_state->getValue(['currency', 'symbol']))
      ->set('currency_symbol_position', $form_state->getValue(['currency', 'symbol_position']))
      ->set('thousands_separator', $form_state->getValue(['currency', 'thousands_separator']))
      ->set('decimal_separator', $form_state->getValue(['currency', 'decimal_separator']))

      // Save timestamp format settings.
      ->set('date_format', $form_state->getValue(['date', 'format']));
    if ($form_state->getValue(['date', 'format']) !== 'custom') {
      $config->clear('custom_date_format');
    }
    else {
      $config->set('custom_date_format', $form_state->getValue(['date', 'custom_format']));
    }

    // Save user profile settings.
    $config->set('show_in_profile', $form_state->getValue(['display', 'show_in_profile']));

    // Save new config settings.
    $config->save();
    drupal_set_message($this->t('Configuration settings saved.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['balance_tracker.settings'];
  }

}
