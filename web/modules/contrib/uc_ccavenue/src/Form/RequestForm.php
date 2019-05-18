<?php

namespace Drupal\uc_ccavenue\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_ccavenue\Plugin\Ubercart\PaymentMethod\CcavenuePayment;

/**
 * Returns the form for the custom Review Payment screen for Express Checkout.
 */
class RequestForm extends FormBase{

  /**
   * The order that is being reviewed.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_ccavenue_request_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $data=NULL) {
		$session = \Drupal::service('session');

		$order = Order::load($session->get('cart_order'));

		$plugin = \Drupal::service('plugin.manager.uc_payment.method')->createFromOrder($order);
		$config = $plugin->getConfiguration();

		$form['message'] = array('#markup' => '<div align="center"> Please wait while the request is being tranferred to the payment gateway. Do not refresh your browser at this moment</div>');

		$form['encRequest'] = array('#type' => 'hidden', '#value' => $data['encRequest']);

		$form['access_code'] = array('#type' => 'hidden', '#value' => $data['ccavenue_access_code']);

		$form['#action'] = $config['ccavenue_server'].'/transaction/transaction.do?command=initiateTransaction';


    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }

}
