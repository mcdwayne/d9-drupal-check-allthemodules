<?php

namespace Drupal\tmgmt_powerling\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt_powerling\Plugin\tmgmt\Translator\PowerlingTranslator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles callbacks for tmgmt_powerling module.
 */
class PowerlingController extends ControllerBase
{

  /**
   * Logger logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new instance of PowerlingController.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger
   */
  public function __construct(LoggerInterface $logger)
  {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('logger.factory')->get('tmgmt_powerling')
    );
  }

  /**
   * Callback for file status update
   *
   * @param \Drupal\tmgmt\JobItemInterface $tmgmtJobItem
   *   Job item.
   * @param string $orderId
   *   Order ID.
   * @param string $fileId
   *   File ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response.
   */
  public function fileCallback(JobItemInterface $tmgmtJobItem, $orderId, $fileId)
  {
    if (!$tmgmtJobItem->getTranslatorPlugin() instanceof PowerlingTranslator) {
      $this->logger->warning('Invalid parameters when receiving remote response for job item %id', ['%id' => $tmgmtJobItem->id()]);
      throw new NotFoundHttpException();
    }

    $translator = $tmgmtJobItem->getTranslator();
    /** @var \Drupal\tmgmt_powerling\Plugin\tmgmt\Translator\PowerlingTranslator $translatorPlugin */
    $translatorPlugin = $translator->getPlugin();

    try {
      $translatorPlugin->updateTranslation($tmgmtJobItem, $orderId, $fileId);
    }
    catch (TMGMTException $tmgmtException) {
      $tmgmtJobItem->addMessage($tmgmtException->getMessage());
    }
    catch (\Exception $e) {
      watchdog_exception('tmgmt_powerling', $e);
      return new Response(NULL, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return new Response(NULL, Response::HTTP_OK);
  }


  /**
   * Callback function for order status update
   *
   * @param \Drupal\tmgmt\JobInterface $tmgmtJob
   *   Translation job.
   * @param string $orderId
   *   Order ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response.
   */
  public function orderCallback(JobInterface $tmgmtJob, $orderId)
  {
    if (!$tmgmtJob->getTranslatorPlugin() instanceof PowerlingTranslator) {
      $this->logger->warning('Invalid parameters when receiving remote response for job %id', ['%id' => $tmgmtJob->id()]);
      throw new NotFoundHttpException();
    }

    $translator = $tmgmtJob->getTranslator();
    /** @var \Drupal\tmgmt_powerling\Plugin\tmgmt\Translator\PowerlingTranslator $translatorPlugin */
    $translatorPlugin = $translator->getPlugin();

    $order = $translatorPlugin->getOrder($translator, $orderId);

    if ($order['status'] == 'canceled') {
      $translatorPlugin->abortJob($tmgmtJob);
    }
    else {
      $tmgmtJob->addMessage('Order (@order_id) status has been changed to @status.', ['@order_id' => $orderId, '@status' => $order['status']]);
    }

    return new Response(NULL, Response::HTTP_OK);
  }
}

