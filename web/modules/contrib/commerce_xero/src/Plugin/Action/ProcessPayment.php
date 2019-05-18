<?php

namespace Drupal\commerce_xero\Plugin\Action;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_xero\CommerceXeroDataTypeManager;
use Drupal\commerce_xero\CommerceXeroProcessorManager;
use Drupal\commerce_xero\CommerceXeroStrategyResolverInterface;
use Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manually processes an order with the chosen strategy.
 *
 * @Action(
 *   id = "commerce_xero_process_payment_action",
 *   label = @Translation("Process Payment to Xero"),
 *   type = "commerce_payment"
 * )
 */
class ProcessPayment extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The strategy resolver.
   *
   * @var \Drupal\commerce_xero\CommerceXeroStrategyResolverInterface
   */
  protected $resolver;

  /**
   * The data type manager.
   *
   * @var \Drupal\commerce_xero\CommerceXeroDataTypeManager
   */
  protected $dataManager;

  /**
   * The processor manager.
   *
   * @var \Drupal\commerce_xero\CommerceXeroProcessorManager
   */
  protected $processor;

  /**
   * The channel logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition array to describe how to instantiate the plugin.
   * @param \Drupal\commerce_xero\CommerceXeroStrategyResolverInterface $resolver
   *   The strategy resolver.
   * @param \Drupal\commerce_xero\CommerceXeroDataTypeManager $dataManager
   *   The commerce xero data type plugin manager.
   * @param \Drupal\commerce_xero\CommerceXeroProcessorManager $processor
   *   The commerce xero processor plugin manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger.factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CommerceXeroStrategyResolverInterface $resolver, CommerceXeroDataTypeManager $dataManager, CommerceXeroProcessorManager $processor, LoggerChannelFactoryInterface $loggerFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->resolver = $resolver;
    $this->dataManager = $dataManager;
    $this->processor = $processor;
    $this->logger = $loggerFactory->get('commerce_xero');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = FALSE;
    if (is_a($object, '\Drupal\commerce_payment\Entity\PaymentInterface')) {
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $object */
      $access = $account->hasPermission('commerce post to xero') &&
        $object->access('view', $account, FALSE);
    }

    return $return_as_object ? AccessResult::allowedIf($access) : $access;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($payment = NULL) {
    /* @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    if ($payment !== NULL &&
        is_a($payment, '\Drupal\commerce_payment\Entity\PaymentInterface')) {
      try {
        /* @var \Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface $strategy */
        $strategy = $this->resolver->resolve($payment);
        $data = $this->dataManager->createData($payment, $strategy);
        $immediateSuccess = $this->processor->process($strategy, $payment, $data, 'immediate');

        if (!$immediateSuccess) {
          $this->logError($payment, $strategy, 'immediate');
          return;
        }

        $processSuccess = $this->processor->process($strategy, $payment, $data, 'process');
        if (!$processSuccess) {
          $this->logError($payment, $strategy, 'process');
          return;
        }

        $sendSuccess = $this->processor->process($strategy, $payment, $data, 'send');
        if (!$sendSuccess) {
          $this->logError($payment, $strategy, 'send');
          return;
        }
      }
      catch (PluginException $e) {
        $this->logger->error('Could not instantiate plugins for strategy @id', ['@id' => $strategy->id()]);
      }
    }
  }

  /**
   * Logs errors.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param \Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface $strategy
   *   The commerce xero strategy.
   * @param string $state
   *   The execution state.
   */
  protected function logError(PaymentInterface $payment, CommerceXeroStrategyInterface $strategy, $state) {
    $this->logger->error(
      'Failed to run @state plugins for payment @payment, strategy @strategy',
      [
        '@state' => $state,
        '@payment' => $payment->id(),
        '@strategy' => $strategy->id(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_xero_strategy_simple_resolver'),
      $container->get('commerce_xero_data_type.manager'),
      $container->get('commerce_xero_processor.manager'),
      $container->get('logger.factory')
    );
  }

}
