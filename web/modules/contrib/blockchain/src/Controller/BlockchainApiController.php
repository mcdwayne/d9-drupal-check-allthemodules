<?php

namespace Drupal\blockchain\Controller;

use Drupal\blockchain\Entity\BlockchainConfigInterface;
use Drupal\blockchain\Entity\BlockchainNodeInterface;
use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\blockchain\Utils\BlockchainRequest;
use Drupal\blockchain\Utils\BlockchainRequestInterface;
use Drupal\blockchain\Utils\BlockchainResponse;
use Drupal\blockchain\Utils\BlockchainResponseInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Blockchain controller.
 */
class BlockchainApiController extends ControllerBase {

  /**
   * Blockchain service.
   *
   * @var \Drupal\blockchain\Service\BlockchainServiceInterface
   */
  protected $blockchainService;

  /**
   * Blockchain block storage service.
   *
   * @var \Drupal\blockchain\Service\BlockchainStorageServiceInterface
   */
  protected $blockchainBlockStorage;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Validation result.
   *
   * @var \Drupal\blockchain\Utils\BlockchainRequestInterface|BlockchainResponseInterface
   */
  protected $validationResult;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('blockchain.service'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(BlockchainServiceInterface $blockchainService,
                              RequestStack $requestStack) {

    $this->blockchainService = $blockchainService;
    $this->blockchainBlockStorage = $blockchainService->getStorageService();
    $this->request = $requestStack->getCurrentRequest();
    $this->beforeAction();
  }

  /**
   * Request before action handler.
   *
   * This validates request according to defined protocol
   * and returns JsonResponse in case of fail or BlockchainRequest
   * in case if request is valid. Additionally this sets config.
   */
  public function beforeAction() {

    $blockchainRequest = BlockchainRequest::createFromRequest($this->request);
    $validationResult = $this->blockchainService
      ->getValidatorService()
      ->validateRequest($blockchainRequest, $this->request);
    if ($validationResult instanceof BlockchainRequestInterface) {

      $this->blockchainService
        ->getConfigService()
        ->setCurrentConfig($validationResult->getTypeParam());
    }

    $this->validationResult = $validationResult;
  }

  /**
   * Getter for logger.
   *
   * @return \Psr\Log\LoggerInterface
   *   Logger.
   */
  public function getBlockchainLogger() {

    return $this->getLogger('blockchain.api');
  }

  /**
   * Announce action.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response by convention.
   */
  public function announce() {

    $logger = $this->getBlockchainLogger();
    $logger->info('Announce attempt initiated.');
    $result = $this->validationResult;
    if ($result instanceof BlockchainResponseInterface) {

      return $result->log($logger)->toJsonResponse();
    }
    elseif ($result instanceof BlockchainRequestInterface) {
      if ($result->hasCountParam()) {
        $ownBlockCount = $this->blockchainBlockStorage->getBlockCount();
        if ($ownBlockCount < $result->getCountParam()) {
          $this->blockchainService->getQueueService()->addAnnounceItem(
            $result->sleep(),
            $this->blockchainService->getConfigService()->getCurrentConfig()->id());
          $announceManagement = $this->blockchainService->getConfigService()->getCurrentConfig()->getAnnounceManagement();
          if ($announceManagement == BlockchainConfigInterface::ANNOUNCE_MANAGEMENT_IMMEDIATE) {
            $this->blockchainService->getQueueService()->doAnnounceHandling();
          }

          return BlockchainResponse::create()
            ->setIp($result->getIp())
            ->setPort($result->getPort())
            ->setSecure($result->isSecure())
            ->setStatusCode(200)
            ->setMessageParam('Success')
            ->setDetailsParam('Added to queue.')
            ->log($logger)
            ->toJsonResponse();
        }
        else {

          return BlockchainResponse::create()
            ->setIp($result->getIp())
            ->setPort($result->getPort())
            ->setSecure($result->isSecure())
            ->setStatusCode(406)
            ->setMessageParam('Not acceptable')
            ->setDetailsParam('Count of blocks is less or equals.')
            ->log($logger)
            ->toJsonResponse();
        }

      }
      else {

        return BlockchainResponse::create()
          ->setIp($result->getIp())
          ->setPort($result->getPort())
          ->setSecure($result->isSecure())
          ->setStatusCode(400)
          ->setMessageParam('Bad request')
          ->setDetailsParam('No count param.')
          ->log($logger)
          ->toJsonResponse();
      }
    }

    return BlockchainResponse::create()
      ->setIp($result->getIp())
      ->setPort($result->getPort())
      ->setSecure($result->isSecure())
      ->setStatusCode(505)
      ->setMessageParam('Server error')
      ->setDetailsParam('Something unexpected happened.')
      ->log($logger)
      ->toJsonResponse();
  }

  /**
   * Subscribe action.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response by convention.
   */
  public function subscribe() {

    $logger = $this->getBlockchainLogger();
    $logger->info('Subscribe attempt initiated.');
    $result = $this->validationResult;
    if ($result instanceof BlockchainResponseInterface) {

      return $result->log($logger)->toJsonResponse();
    }
    elseif ($result instanceof BlockchainRequestInterface) {
      if (!$this->blockchainService->getNodeService()->existsBySelfAndType(
        $result->getSelfParam(), $result->getTypeParam())) {
        if ($this->blockchainService->getNodeService()->createFromRequest($result)) {

          return BlockchainResponse::create()
            ->setIp($result->getIp())
            ->setPort($result->getPort())
            ->setSecure($result->isSecure())
            ->setStatusCode(200)
            ->setSelfParam($this->blockchainService->getConfigService()->getCurrentConfig()->getNodeId())
            ->setMessageParam('Success')
            ->setDetailsParam('Added to list.')
            ->log($logger)
            ->toJsonResponse();
        }
      }
      else {

        return BlockchainResponse::create()
          ->setIp($result->getIp())
          ->setPort($result->getPort())
          ->setSecure($result->isSecure())
          ->setStatusCode(406)
          ->setMessageParam('Not acceptable')
          ->setDetailsParam('Already in list.')
          ->log($logger)
          ->toJsonResponse();
      }
    }

    return BlockchainResponse::create()
      ->setIp($result->getIp())
      ->setPort($result->getPort())
      ->setSecure($result->isSecure())
      ->setStatusCode(505)
      ->setMessageParam('Server error')
      ->setDetailsParam('Something unexpected happened.')
      ->log($logger)
      ->toJsonResponse();
  }

  /**
   * Count action.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response by convention.
   */
  public function count() {

    $logger = $this->getBlockchainLogger();
    $logger->info('Count attempt initiated.');
    $result = $this->validationResult;
    if ($result instanceof BlockchainResponseInterface) {

      return $result->log($logger)->toJsonResponse();
    }
    elseif ($result instanceof BlockchainRequestInterface) {

      return BlockchainResponse::create()
        ->setIp($result->getIp())
        ->setPort($result->getPort())
        ->setSecure($result->isSecure())
        ->setStatusCode(200)
        ->setMessageParam('Success')
        ->setCountParam($this->blockchainBlockStorage->getBlockCount())
        ->setDetailsParam('Block count set.')
        ->log($logger)
        ->toJsonResponse();
    }

    return BlockchainResponse::create()
      ->setIp($result->getIp())
      ->setPort($result->getPort())
      ->setSecure($result->isSecure())
      ->setStatusCode(505)
      ->setMessageParam('Server error')
      ->setDetailsParam('Something unexpected happened.')
      ->log($logger)
      ->toJsonResponse();
  }

  /**
   * Fetch action.
   *
   * This is previous action before pull.
   * If given block found, returns blocks after it.
   * Else returns general block count.
   * Same behavior if search params not set.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response by convention.
   */
  public function fetch() {

    $logger = $this->getBlockchainLogger();
    $logger->info('Subscribe attempt initiated.');
    $result = $this->validationResult;
    if ($result instanceof BlockchainResponseInterface) {

      return $result->log($logger)->toJsonResponse();
    }
    elseif ($result instanceof BlockchainRequestInterface) {

      if ($result->hasTimestampParam() && $result->hasPreviousHashParam()) {
        if ($block = $this->blockchainBlockStorage
          ->loadByTimestampAndHash($result->getTimestampParam(), $result->getPreviousHashParam())) {
          $exists = TRUE;
          $details = 'Block exists';
          $count = $this->blockchainBlockStorage
            ->getBlocksCountFrom($block);
        }
        else {
          $exists = FALSE;
          $details = 'Block not exists';
          $count = $this->blockchainBlockStorage->getBlockCount();
        }

        return BlockchainResponse::create()
          ->setIp($result->getIp())
          ->setPort($result->getPort())
          ->setSecure($result->isSecure())
          ->setStatusCode(200)
          ->setMessageParam('Success')
          ->setExistsParam($exists)
          ->setCountParam($count)
          ->setDetailsParam($details)
          ->log($logger)
          ->toJsonResponse();
      }
      else {

        return BlockchainResponse::create()
          ->setIp($result->getIp())
          ->setPort($result->getPort())
          ->setSecure($result->isSecure())
          ->setStatusCode(200)
          ->setExistsParam(FALSE)
          ->setCountParam($this->blockchainBlockStorage->getBlockCount())
          ->setMessageParam('Downgraded to count response')
          ->setDetailsParam('No timestamp or/and previous hash param.')
          ->log($logger)
          ->toJsonResponse();
      }
    }

    return BlockchainResponse::create()
      ->setIp($result->getIp())
      ->setPort($result->getPort())
      ->setSecure($result->isSecure())
      ->setStatusCode(505)
      ->setMessageParam('Server error')
      ->setDetailsParam('Something unexpected happened.')
      ->log($logger)
      ->toJsonResponse();
  }

  /**
   * Pull action.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response by convention.
   */
  public function pull() {

    $logger = $this->getBlockchainLogger();
    $logger->info('Pull attempt initiated.');
    $result = $this->validationResult;
    if ($result instanceof BlockchainResponseInterface) {

      return $result->log($logger)->toJsonResponse();
    }
    elseif ($result instanceof BlockchainRequestInterface) {

      if ($result->hasCountParam()) {
        if ($result->hasTimestampParam() && $result->hasPreviousHashParam()) {
          if ($block = $this->blockchainBlockStorage
            ->loadByTimestampAndHash($result->getTimestampParam(), $result->getPreviousHashParam())) {
            $exists = TRUE;
            $details = 'Block exists';
            $blocks = $this->blockchainBlockStorage
              ->getBlocksFrom($block, $result->getCountParam());
          }
          else {
            $exists = FALSE;
            $details = 'Block not exists';
            $blocks = [];
          }

          return BlockchainResponse::create()
            ->setIp($result->getIp())
            ->setPort($result->getPort())
            ->setSecure($result->isSecure())
            ->setStatusCode(200)
            ->setMessageParam('Success')
            ->setExistsParam($exists)
            ->setBlocksParam($blocks)
            ->setDetailsParam($details)
            ->log($logger)
            ->toJsonResponse();
        }
        else {
          $blocks = $this->blockchainBlockStorage
            ->getBlocks(0, $result->getCountParam(), TRUE);

          return BlockchainResponse::create()
            ->setIp($result->getIp())
            ->setPort($result->getPort())
            ->setSecure($result->isSecure())
            ->setStatusCode(200)
            ->setMessageParam('Success')
            ->setExistsParam(FALSE)
            ->setBlocksParam($blocks)
            ->setDetailsParam('Returning results starting form generic.')
            ->log($logger)
            ->toJsonResponse();
        }
      }
      else {

        return BlockchainResponse::create()
          ->setIp($result->getIp())
          ->setPort($result->getPort())
          ->setSecure($result->isSecure())
          ->setStatusCode(400)
          ->setMessageParam('Bad request')
          ->setDetailsParam('No count or/and timestamp or/and previous hash param.')
          ->log($logger)
          ->toJsonResponse();
      }
    }

    return BlockchainResponse::create()
      ->setIp($result->getIp())
      ->setPort($result->getPort())
      ->setSecure($result->isSecure())
      ->setStatusCode(505)
      ->setMessageParam('Server error')
      ->setDetailsParam('Something unexpected happened.')
      ->log($logger)
      ->toJsonResponse();
  }

}
