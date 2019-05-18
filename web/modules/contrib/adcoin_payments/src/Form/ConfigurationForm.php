<?php
/**
 * The plugin's configuration form.
 * @author appels
 */
namespace Drupal\adcoin_payments\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\adcoin_payments\WalletAPIWrapper\ClientException;
use Drupal\adcoin_payments\WalletAPIWrapper\PaymentGateway;

class ConfigurationForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adcoin_payments_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'adcoin_payments.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('adcoin_payments.settings');
    $api_key = $config->get('api_key');

    // API key field
    $form['adcoin_payments']['api_key'] = [
      '#type'          => 'textfield',
      '#size'          => '64',
      '#title'         => $this->t('API Key'),
      '#description'   => $this->t('Your AdCoin Wallet API key. '
                                 . 'You can find the key in your <a href="https://wallet.getadcoin.com">'
                                 . 'AdCoin Web Wallet</a> under your name > "API Key".'),
      '#required'      => TRUE,
      '#default_value' => $api_key
    ];

    if (empty($api_key)) {
      // No API key warning
      $form['adcoin_payments']['api_key_msg'] = [
        '#markup' => '<div role="contentinfo" aria-label="Warning message" class="messages messages--warning">'
                    .'<div role="alert">'
                    .'Please provide a Wallet API key.'
                    .'</div>'
                    .'</div>',
        '#allowed_tags' => [ 'div' ]
      ];

    } else {
      // Try to display wallet info
      try {
        // Fetch account information
        $gateway = new PaymentGateway($api_key);
        $account = $gateway->getAccountInformation();

        $form['adcoin_payments']['wallet_info'] = [
          '#markup' => '<b>Account information:</b>'
                      .'<table>'
                      .'<tr><td>Name</td><td>'.$account['name'].'</td></tr>'
                      .'<tr><td>Email</td><td>'.$account['email'].'</td></tr>'
                      .'</table>',
          '#allowed_tags' => [
            'b', 'table', 'tr', 'td'
          ]
        ];

      } catch (ClientException $e) {
        // Invalid API key
        $form['adcoin_payments']['wallet_info'] = [
          '#markup' => '<div role="contentinfo" aria-label="Error message" class="messages messages--error">'
                      .'<div role="alert">'
                      .'Could not find this AdCoin Wallet account!<br>'
                      .'Make sure to provide a correct Wallet API key.'
                      .'</div>'
                      .'</div>',
          '#allowed_tags' => [
            'div',
            'br'
          ]
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve form values
    $values = $form_state->getValues();
    $api_key = trim($values['api_key']);

    // Make sure API key is in correct format
    if (64 != strlen($api_key)) {
      drupal_set_message(t('Given API key is of incorrect length!'), 'error', TRUE);
      $this->config('adcoin_payments.settings')->set('api_key', '')->save();
      return;
    }

    // Make sure API key has an account attached to it
    try {
      $gateway = new PaymentGateway($api_key);
      $account = $gateway->getAccountInformation();
    } catch (ClientException $e) {
      drupal_set_message(t('The provided API key has no Wallet account attached to it!'), 'error', TRUE);
      $this->config('adcoin_payments.settings')->set('api_key', '')->save();
      return;
    }

    // Save new API key
    $this->config('adcoin_payments.settings')
      ->set('api_key', $values['api_key'])
      ->save();
    parent::submitForm($form, $form_state);
  }
}