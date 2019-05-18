<?php
/**
 * Insertable block belonging to the payment form.
 * @author appels
 */
namespace Drupal\adcoin_payments\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\adcoin_payments\Exception\ExceptionHandler;
use Drupal\adcoin_payments\Form\PaymentForm;
use Drupal\adcoin_payments\Model\PageList;

 /**
  * Provides a payment form block.
  *
  * @Block(
  *   id = "adcoin_payments_block",
  *   admin_label = @Translation("AdCoin Payment Form"),
  *   category = @Translation("Forms")
  * )
  */
 class PaymentFormBlock extends BlockBase implements BlockPluginInterface {
   /**
    * {@inheritdoc}
    */
   public function build() {
     $form = \Drupal::service('class_resolver')->getInstanceFromDefinition('Drupal\adcoin_payments\Form\PaymentForm');
     $form->setConfiguration($this->getConfiguration());
     return \Drupal::formBuilder()->getForm($form);
   }

   /**
    * {@inheritdoc}
    */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $conf = $this->getConfiguration();

    // Add form fields
    $form['amount'] = [
      '#type'          => 'number',
      '#title'         => t('Amount in ACC'),
      '#min'           => 1,
      '#default_value' => isset($conf['amount']) ? $conf['amount'] : ''
    ];
    $form['description'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment description'),
      '#size'          => 40,
      '#default_value' => isset($conf['description']) ? $conf['description'] : ''
    ];
    $form['button_text'] = [
      '#type'          => 'textfield',
      '#title'         => t('Button text'),
      '#size'          => 40,
      '#default_value' => isset($conf['button_text']) ? $conf['button_text'] : ''
    ];

    // Route selection dropdowns
    try {
      $routes = PageList::fetchPublicRouteNames();
      $form['route_success'] = [
        '#title'         => t('Success page'),
        '#type'          => 'select',
        '#description'   => t('The route that the customer will be redirected to once the payment has been completed successfully.'),
        '#options'       => $routes,
        '#default_value' => in_array('adcoin_payments.success', $routes) ? 'adcoin_payments.success' : ''
      ];
      $form['route_cancel'] = [
        '#title'         => t('Cancel/failure page'),
        '#type'          => 'select',
        '#description'   => t('The route that the customer will be redirected to if the payment process failed or was cancelled.'),
        '#options'       => $routes,
        '#default_value' => in_array('adcoin_payments.failed', $routes) ? 'adcoin_payments.failed' : ''
      ];
    } catch (\Exception $e) {
      return ExceptionHandler::handle($e);
    }

    $form['enable_name'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Enable name field'),
      '#default_value' => isset($conf['enable_name']) ? $conf['enable_name'] : true
    ];
    $form['enable_email'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Enable email field'),
      '#default_value' => isset($conf['enable_email']) ? $conf['enable_email'] : true
    ];
    $form['enable_phone'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Enable phone number field'),
      '#default_value' => isset($conf['enable_phone']) ? $conf['enable_phone'] : true
    ];
    $form['enable_postal'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Enable post- or zipcode field'),
      '#default_value' => isset($conf['enable_postal']) ? $conf['enable_postal'] : true
    ];
    $form['enable_country'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Enable country field'),
      '#default_value' => isset($conf['enable_country']) ? $conf['enable_country'] : true
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('description',     $form_state->getValue('description'));
    $this->setConfigurationValue('amount',          $form_state->getValue('amount'));
    $this->setConfigurationValue('route_success',   $form_state->getValue('route_success'));
    $this->setConfigurationValue('route_cancel',    $form_state->getValue('route_cancel'));
    $this->setConfigurationValue('enable_name',     $form_state->getValue('enable_name'));
    $this->setConfigurationValue('enable_email',    $form_state->getValue('enable_email'));
    $this->setConfigurationValue('enable_phone',    $form_state->getValue('enable_phone'));
    $this->setConfigurationValue('enable_postal',   $form_state->getValue('enable_postal'));
    $this->setConfigurationValue('enable_country',  $form_state->getValue('enable_country'));
    $this->setConfigurationValue('button_text',     $form_state->getValue('button_text'));
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $description    = $form_state->getValue('description');
    $amount         = $form_state->getValue('amount');
    $url_success    = $form_state->getValue('route_success');
    $url_cancel     = $form_state->getValue('route_cancel');
    $enable_name    = $form_state->getValue('enable_name');
    $enable_email   = $form_state->getValue('enable_email');
    $enable_email   = $form_state->getValue('enable_phone');
    $enable_postal  = $form_state->getValue('enable_postal');
    $enable_country = $form_state->getValue('enable_country');
    $button_text    = $form_state->getValue('button_text');

    // Make sure amount is a positive number
    if (!is_numeric($amount) || ((int)$amount <= 0.0)) {
      $form_state->setErrorByName('adcoin_payments_block_settings', t('Amount has to be a positive number.'));
    }

  }


 }