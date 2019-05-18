<?php

namespace Drupal\commerce_pagos_net\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\HasPaymentInstructionsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides the Onsite payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "pagos_net",
 *   label = "PagosNet",
 *   display_label = "PagosNet",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_pagos_net\PluginForm\PagosNetMethodAddForm",
 *   },
 *   payment_type = "payment_manual",
 *   payment_method_types = {"pagos_net"},
 * )
 */
class PagosNet extends OnsitePaymentGatewayBase implements HasPaymentInstructionsInterface, SupportsNotificationsInterface {

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new PaymentGatewayBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   *   The payment type manager.
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   *   The payment method type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   The logger channel factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, LoggerChannelFactoryInterface $logger_channel_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->logger = $logger_channel_factory->get('commerce_pagos_net');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'server' => '',
        'cod_prefix' => '',
        'account' => '',
        'password' => '',
        'business_code' => '',
        'description' => '',
        'prod_category' => 1,
        'prod_description' => '',
        'due_date' => '30',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['server'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pagos Net server'),
      '#description' => $this->t('This is the server address to the Pagos Net web service endpoint'),
      '#default_value' => $this->configuration['server'],
      '#required' => TRUE,
    ];
    $form['account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account code assigned by Pagos Net'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['account'],
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password code assigned by Pagos Net'),
      '#default_value' => $this->configuration['password'],
      '#required' => TRUE,
    ];
    $form['business_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Business code assigned by Pagos Net'),
      '#default_value' => $this->configuration['business_code'],
      '#required' => TRUE,
    ];
    $form['cod_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Code prefix'),
      '#description' => $this->t('It appears as part of the payment code.'),
      '#default_value' => $this->configuration['cod_prefix'],
      '#required' => FALSE,
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Note description'),
      '#description' => $this->t('A description of the payment to be used by pagos net (e.g. "Payment for store XYZ.inc").'),
      '#default_value' => $this->configuration['description'],
      '#required' => TRUE,
    ];
    $form['prod_category'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product code assigned by Pagos Net'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['prod_category'],
    ];
    $form['prod_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description code to attach to payment'),
      '#description' => $this->t('A description code of the payment to be used by pagos net (e.g. "CUOTA1").'),
      '#default_value' => $this->configuration['prod_description'],
      '#required' => TRUE,
    ];
    $form['due_date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Due date'),
      '#description' => $this->t('A number of days from today. For example 3 represents three days from today.'),
      '#default_value' => $this->configuration['due_date'],
      '#element_validate' => [['Drupal\Core\Render\Element\Number', 'validateNumber']],
      '#required' => TRUE,
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
      $this->configuration['server'] = $values['server'];
      $this->configuration['cod_prefix'] = $values['cod_prefix'];
      $this->configuration['account'] = $values['account'];
      $this->configuration['password'] = $values['password'];
      $this->configuration['business_code'] = $values['business_code'];
      $this->configuration['description'] = $values['description'];
      $this->configuration['prod_category'] = $values['prod_category'];
      $this->configuration['prod_description'] = $values['prod_description'];
      $this->configuration['due_date'] = $values['due_date'];
    }
  }
  protected function validatePayment(PaymentInterface $payment, $payment_state = 'new') {
    $this->assertPaymentState($payment, [$payment_state]);

    $payment_method = $payment->getPaymentMethod();
    if (empty($payment_method)) {
      throw new \InvalidArgumentException('The provided payment has no payment method referenced.');
    }

    if ($payment_method->isExpired()) {
      throw new HardDeclineException('The provided payment method has expired.');
    }
  }

  public function getCode($id) {
    if ($this->configuration['cod_prefix']) {
      return $this->configuration['cod_prefix']  . str_pad($id, 6, '0', STR_PAD_LEFT);
    } else {
      return str_pad($id, 6, '0', STR_PAD_LEFT);
    }
  }

  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->validatePayment($payment, 'new');

    date_default_timezone_set('America/La_Paz');

    $fiscal_name = $payment->getPaymentMethod()->get('fiscal_name')->value;
    $nit = $payment->getPaymentMethod()->get('nit')->value;
    $code = $this->getCode($payment->getOrderId());

    $param = [
      'cuenta' => $this->configuration['account'],
      'password' => $this->configuration['password'],
      'datos' =>  [
        'transaccion' => 'A',
        'nombreComprador' => $fiscal_name,
        'documentoIdentidadComprador' => $nit,
        'codigoComprador' => $payment->getOrder()->getCustomerId(),
        'fecha' => date("Ymd"),
        'hora' => date("His"),
        'correoElectronico' => $payment->getOrder()->getEmail(),
        'moneda' => 'BS',
        'codigoRecaudacion' => $code,
        'descripcionRecaudacion' => $this->configuration['description'],
        'fechaVencimiento' => date('Ymd', $payment->getPaymentMethod()->getExpiresTime()),
        'horaVencimiento' => 000000,
        'categoriaProducto' => $this->configuration['prod_category'],
        'planillas' => [[
          'numeroPago' => 1,
          'montoPago' => $payment->getAmount()->getNumber(),
          'descripcion' => $this->configuration['prod_description'],
          'montoCreditoFiscal' => $payment->getAmount()->getNumber(),
          'nombreFactura' => $fiscal_name,
          'nitFactura' => $nit,
        ]],
      ],
    ];

    // Allow modules to alter parameters of the API request.
    // TODO: replace drupal_alter with maybe a drupal event
    //drupal_alter('commerce_pagos_net_form_data', $datos, $order);

    $client = new \SoapClient($this->configuration['server']);

    $result = $client->registroPlan($param);

    // If codigoError is 0 then transacction was successful, else, show an error
    if($result->return->codigoError == "0")
    {
      $payment->state = 'pending';
      $payment->setRemoteId($code);
      $payment->save();
    }
    else
    {
      throw new PaymentGatewayException('Could not charge the pagos net. Error: ' . $result->return->descripcionError);
    }
  }

  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $payment_method->nit = $payment_details['nit'];
    $payment_method->fiscal_name = $payment_details['fiscal_name'];
    $payment_method->setExpiresTime(strtotime('+' . $this->configuration['due_date'] . ' days'));
    $payment_method->save();
  }

  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    $payment_method->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    if (empty($request->getContent())) {
      $this->logger->warning('IPN URL accessed with no POST data submitted.');
      throw new BadRequestHttpException('IPN URL accessed with no POST data submitted.');
    }

    $data = \GuzzleHttp\json_decode($request->getContent());

    if ($data->codigoEmpresa != $this->configuration['business_code'] &&
        $data->usuario != $this->configuration['account'] &&
        $data->clave != $this->configuration['password']) {
      $this->logger->warning('IPN URL accessed with wrong authentication information.');
      throw new BadRequestHttpException('IPN URL accessed with wrong authentication information.');
    }
    if (empty($data->codigoRecaudacion)) {
      $this->logger->warning('IPN URL accessed with no remote id data submitted.');
      return new JsonResponse(["codError" => 1, 'descripcion' => 'Falta el codigo de recaudacion']);
    }

    if ($data->transaccion == 'P') {
      // Ensure we can load the existing corresponding transaction.
      $payment = $this->loadPaymentByRemoteId($data->codigoRecaudacion);
      // If not, bail now because authorization transactions should be created
      // by the Express Checkout API request itself.
      if (!$payment) {
        $this->logger->warning('IPN for  @order_number ignored: authorization transaction already created.', ['@order_number' => $data->codigoRecaudacion]);
        return new JsonResponse(["codError" => 1, 'descripcion' => 'No existe ese codigo de recaudacion']);
      }
      if ($payment->get('state')->value == 'completed') {
        $this->logger->warning('Order @order_number already payed.', ['@order_number' => $data->codigoRecaudacion]);
        return new JsonResponse(["codError" => 1, 'descripcion' => 'Orden ya pagada']);
      }
      $payment->state = 'completed';
      $payment->setRemoteState($data->nroRentaRecibo);
      // Save the transaction information.
      $payment->save();

      return new JsonResponse(["codError" => 0, "descripcion" => "Sin Errores"]);
    }
    else {
      // Exit when we don't get a payment status we recognize.
      throw new BadRequestHttpException('Invalid transaction code');
    }
  }

  /**
   * Loads the payment for a given remote id.
   *
   * @param string $remote_id
   *   The remote id property for a payment.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   Payment object.
   *
   * @todo: to be replaced by Commerce core payment storage method
   * @see https://www.drupal.org/node/2856209
   */
  protected function loadPaymentByRemoteId($remote_id) {
    /** @var \Drupal\commerce_payment\PaymentStorage $storage */
    $storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment_by_remote_id = $storage->loadByProperties(['remote_id' => $remote_id]);
    return reset($payment_by_remote_id);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentInstructions(PaymentInterface $payment) {
    return [
      '#markup' => '<strong>Codigo: ' . $payment->getRemoteId() . '</strong>',
    ];
  }
}
