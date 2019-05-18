<?php

namespace Drupal\exception_mailer\Subscribers;

use Drupal\Core\Form\FormAjaxException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\exception_mailer\Utility\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribe to thrown exceptions to send emails to admin users.
 */
class ExceptionEventSubscriber implements EventSubscriberInterface {

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The queue manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * Constructor.
   */
  public function __construct(LoggerChannelFactoryInterface $logger, QueueFactory $queue_factory, QueueWorkerManagerInterface $queue_manager) {
    $this->logger = $logger;
    $this->queueFactory = $queue_factory;
    $this->queueManager = $queue_manager;
  }

  /**
   * Event handler.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The exception event.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    $queue = $this->queueFactory->get('manual_exception_email', TRUE);
    $queue_worker = $this->queueManager->createInstance('manual_exception_email');
    if (!$exception instanceof FormAjaxException && !$exception instanceof NotFoundHttpException) {
      foreach (UserRepository::getUserEmails("administrator") as $admin) {
        $data['email'] = $admin;
        $data['exception'] = get_class($exception);
        $data['message'] = $exception->getMessage();
        $queue->createItem($data);
      }
      while ($item = $queue->claimItem()) {
        try {
          $queue_worker->processItem($item->data);
          $queue->deleteItem($item);
        }
        catch (SuspendQueueException $e) {
          $queue->releaseItem($item);
          break;
        }
        catch (\Exception $e) {
          watchdog_exception('exception_mailer', $e);
        }
      }
      $this->logger->get('php')->error($exception->getMessage());
      $response = new Response($exception->getMessage(), 500);
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onException', 60];
    return $events;
  }

}
