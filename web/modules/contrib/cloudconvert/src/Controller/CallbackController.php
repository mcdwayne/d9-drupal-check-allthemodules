<?php

namespace Drupal\cloudconvert\Controller;

use Drupal\cloudconvert\CloudConvertProcessorInterface;
use Drupal\cloudconvert\Entity\CloudConvertTaskInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CallbackController.
 *
 * @package Drupal\cloudconvert\Controller
 */
class CallbackController extends ControllerBase {

  /**
   * Current Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Queue Factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Cloud Convert Processor.
   *
   * @var \Drupal\cloudconvert\CloudConvertProcessorInterface
   */
  protected $cloudConvertProcessor;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $requestStack, QueueFactory $queueFactory, CloudConvertProcessorInterface $cloudConvertProcessor) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->queueFactory = $queueFactory;
    $this->cloudConvertProcessor = $cloudConvertProcessor;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('queue'),
      $container->get('cloudconvert.processor')
    );
  }

  /**
   * Callback to handle a cloudconvert finished request.
   *
   * @param \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudconvert_task
   *   Contains a cloudconvert task entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON Response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function callback(CloudConvertTaskInterface $cloudconvert_task) {
    $cloudConvertTask = $cloudconvert_task;

    $step = $this->currentRequest->get('step');
    if ($step === 'finished') {
      $this->cloudConvertProcessor->createFinishQueueItem($cloudConvertTask);
    }

    $cloudConvertTask->setStep($step);
    $cloudConvertTask->save();

    return new JsonResponse();
  }

  /**
   * Access validation.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Contains a account entity.
   * @param \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudconvert_task
   *   Contains cloudconvert task entity.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Access Result Allowed or Forbidden.
   */
  public function access(AccountInterface $account, CloudConvertTaskInterface $cloudconvert_task) {
    $cloudConvertTask = $cloudconvert_task;
    $process_id = $this->currentRequest->get('id');
    $url = $this->currentRequest->get('url');
    if ($cloudConvertTask->getStep() === 'finished') {
      return AccessResult::forbidden('CloudConvert Task is already marked as finished.');
    }
    if ($cloudConvertTask->getProcessId() !== $process_id) {
      return AccessResult::forbidden('Process id does not match with the CloudConvert Task process id.');
    }
    if ($cloudConvertTask->getProcessInfo()['url'] !== $url) {
      return AccessResult::forbidden('Process url does not match with the CloudConvert Task process info url.');
    }

    return AccessResult::allowed();
  }

}
