<?php

namespace Drupal\commerce_xero\EventSubscriber;

use Drupal\commerce_xero\CommerceXeroData;
use Drupal\commerce_xero\CommerceXeroDataTypeManager;
use Drupal\commerce_xero\CommerceXeroProcessorManager;
use Drupal\commerce_xero\CommerceXeroStrategyResolverInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Acts on workflow state event from commerce payment.
 */
class OrderSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Commerce Xero Strategy Resolver service.
   *
   * @var \Drupal\commerce_xero\CommerceXeroStrategyResolverInterface
   */
  protected $strategyResolver;

  /**
   * Commerce Xero Data Type Manager service.
   *
   * @var \Drupal\commerce_xero\CommerceXeroDataTypeManager
   */
  protected $dataTypeManager;

  /**
   * Commerce Xero Processor Manager service.
   *
   * @var \Drupal\commerce_xero\CommerceXeroProcessorManager
   */
  protected $processorManager;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Commerce xero queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * Initialize method.
   *
   * @param \Drupal\commerce_xero\CommerceXeroStrategyResolverInterface $strategyResolver
   *   The commerce xero strategy resolver.
   * @param \Drupal\commerce_xero\CommerceXeroDataTypeManager $dataTypeManager
   *   The commerce xero data type manager.
   * @param \Drupal\commerce_xero\CommerceXeroProcessorManager $processorManager
   *   The commerce xero processor manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The commerce xero logger channel.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue service.
   */
  public function __construct(CommerceXeroStrategyResolverInterface $strategyResolver, CommerceXeroDataTypeManager $dataTypeManager, CommerceXeroProcessorManager $processorManager, LoggerChannelInterface $logger, QueueFactory $queueFactory) {
    $this->strategyResolver = $strategyResolver;
    $this->dataTypeManager = $dataTypeManager;
    $this->processorManager = $processorManager;
    $this->logger = $logger;
    $this->queue = $queueFactory->get('commerce_xero_process');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return ['commerce_payment.post_transition' => 'onPaymentReceived'];
  }

  /**
   * Finds the appropriate Commerce Xero strategy to use for an order.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The commerce order transition.
   */
  public function onPaymentReceived(WorkflowTransitionEvent $event) {
    // @todo this seems fairly fragile.
    $transitions = ['capture', 'receive'];

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $event->getEntity();
    try {
      if (in_array($event->getTransition()->getId(), $transitions)) {

        $strategy = $this->strategyResolver->resolve($payment);

        // Create the xero data.
        $data = $this->dataTypeManager->createData($payment, $strategy);

        // Run immediate processors on the data type given the payment.
        $success = $this->processorManager->process($strategy, $payment, $data, 'immediate');
        if (!$success) {
          throw new \Exception('Immediate process plugins failed.');
        }

        // Adds to the queue for later processing.
        $data = new CommerceXeroData($strategy->id(), $payment->id(), $data, 'process');
        $success = $this->queue->createItem($data);
        if (!$success) {
          throw new \Exception('Failed to add item to queue.');
        }
      }
    }
    catch (PluginException $e) {
      $this->logger->error($this->t(
       '%file at %line: %message %stack',
       [
         '%file' => $e->getFile(),
         '%line' => $e->getLine(),
         '%message' => $e->getMessage(),
         '%stack' => $e->getTraceAsString(),
       ]
      ));
    }
    catch (\Exception $e) {
      $transition = $event->getTransition();
      $this->logger->error($this->t(
        'Could not resolve strategy for payment id @payment during @transition transition to @to: @exception',
        [
          '@transition' => $transition->getLabel(),
          '@to' => $transition->getToState()->getLabel(),
          '@payment' => $payment->id(),
          '@exception' => $e->getMessage(),
        ]
      ));
    }
  }

}
