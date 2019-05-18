<?php

namespace Drupal\tmgmt_acclaro\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt_acclaro\Plugin\tmgmt\Translator\AcclaroTranslator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles callback routes for tmgmt_acclaro module.
 */
class AcclaroController extends ControllerBase {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new instance of AcclaroController.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Injected logger manager.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')->get('tmgmt_acclaro')
    );
  }

  /**
   * Provides a callback function for order status changes.
   *
   * @param \Drupal\tmgmt\JobInterface $tmgmt_job
   *   The translation job.
   * @param string $order_id
   *   The order ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to return.
   */
  public function orderCallback(JobInterface $tmgmt_job, $order_id) {
    if (!$tmgmt_job->getTranslatorPlugin() instanceof AcclaroTranslator) {
      $this->logger->warning('Invalid parameters when receiving remote response for job %id', ['%id' => $tmgmt_job->id()]);
      throw new NotFoundHttpException();
    }

    $translator = $tmgmt_job->getTranslator();
    /** @var \Drupal\tmgmt_acclaro\Plugin\tmgmt\Translator\AcclaroTranslator $translator_plugin */
    $translator_plugin = $translator->getPlugin();

    $order = $translator_plugin->getOrder($translator, $order_id);
    switch ($order['status']) {
      case 'canceled':
        // Abort the translation if the order is cancelled.
        $translator_plugin->abortJob($tmgmt_job);
        break;

      default:
        $tmgmt_job->addMessage('Order (@order_id) status has been changed to @status.', ['@order_id' => $order_id, '@status' => $order['status']]);
    }

    return new Response(NULL, Response::HTTP_OK);
  }

  /**
   * Provides a callback function for file status changes.
   *
   * @param \Drupal\tmgmt\JobItemInterface $tmgmt_job_item
   *   The job item.
   * @param string $order_id
   *   The order ID.
   * @param string $file_id
   *   The file ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to return.
   */
  public function fileCallback(JobItemInterface $tmgmt_job_item, $order_id, $file_id) {
    if (!$tmgmt_job_item->getTranslatorPlugin() instanceof AcclaroTranslator) {
      $this->logger->warning('Invalid parameters when receiving remote response for job item %id', ['%id' => $tmgmt_job_item->id()]);
      throw new NotFoundHttpException();
    }

    $translator = $tmgmt_job_item->getTranslator();
    /** @var \Drupal\tmgmt_acclaro\Plugin\tmgmt\Translator\AcclaroTranslator $translator_plugin */
    $translator_plugin = $translator->getPlugin();

    try {
      $translator_plugin->updateTranslation($tmgmt_job_item, $order_id, $file_id);
    }
    catch (TMGMTException $tmgmt_exception) {
      $tmgmt_job_item->addMessage($tmgmt_exception->getMessage());
    }
    catch (\Exception $e) {
      watchdog_exception('tmgmt_acclaro', $e);
      return new Response(NULL, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    // @todo: Check if Acclaro supports 204 status code.
    return new Response(NULL, Response::HTTP_OK);
  }

}
