<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Entity\BlockchainBlockInterface;
use Drupal\blockchain\Entity\BlockchainConfigInterface;
use Drupal\blockchain\Plugin\BlockchainAuthManager;
use Drupal\blockchain\Utils\BlockchainRequestInterface;
use Drupal\blockchain\Utils\BlockchainResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BlockchainValidatorService.
 *
 * @package Drupal\blockchain\Service
 */
class BlockchainValidatorService implements BlockchainValidatorServiceInterface {

  /**
   * Config service.
   *
   * @var BlockchainConfigServiceInterface
   */
  protected $configService;

  /**
   * Blockchain Node service.
   *
   * @var BlockchainNodeServiceInterface
   */
  protected $blockchainNodeService;

  /**
   * Auth manager.
   *
   * @var \Drupal\blockchain\Plugin\BlockchainAuthManager
   */
  protected $blockchainAuthManager;

  /**
   * Blockchain hash service.
   *
   * @var BlockchainHashServiceInterface
   */
  protected $blockchainHashService;

  /**
   * {@inheritdoc}
   */
  public function __construct(BlockchainConfigServiceInterface $blockchainSettingsService,
                              BlockchainNodeServiceInterface $blockchainNodeService,
                              BlockchainAuthManager $blockchainAuthManager,
                              BlockchainHashServiceInterface $blockchainHashService) {

    $this->configService = $blockchainSettingsService;
    $this->blockchainNodeService = $blockchainNodeService;
    $this->blockchainAuthManager = $blockchainAuthManager;
    $this->blockchainHashService = $blockchainHashService;
  }

  /**
   * {@inheritdoc}
   */
  public function hashIsValid($hash) {

    $powPosition = $this->configService->getCurrentConfig()->getPowPosition();
    $powExpression = $this->configService->getCurrentConfig()->getPowExpression();
    $length = strlen($powExpression);
    if ($powPosition === BlockchainConfigInterface::POW_POSITION_START) {
      if (substr($hash, 0, $length) === $powExpression) {

        return TRUE;
      }
    }
    else {
      if (substr($hash, -$length) === $powExpression) {

        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function blockIsValid(BlockchainBlockInterface $blockchainBlock, BlockchainBlockInterface $previousBlock = NULL) {

    $hashString = $this->blockchainHashService
      ->hash($blockchainBlock->getPreviousHash() . $blockchainBlock->getNonce());
    if (!$previousBlock) {

      return $this->hashIsValid($hashString);
    }

    return $previousBlock->toHash() == $blockchainBlock->getPreviousHash() &&
      $this->hashIsValid($hashString);
  }

  /**
   * {@inheritdoc}
   */
  public function validateRequest(BlockchainRequestInterface $blockchainRequest, Request $request) {

    if ($request->getMethod() !== Request::METHOD_POST) {

      return BlockchainResponse::create()
        ->setIp($request->getClientIp())
        ->setPort($request->getPort())
        ->setSecure($request->isSecure())
        ->setStatusCode(400)
        ->setMessageParam('Bad request')
        ->setDetailsParam('Incorrect method.');
    }
    if (!$blockchainRequest->hasTypeParam()) {

      return BlockchainResponse::create()
        ->setIp($request->getClientIp())
        ->setPort($request->getPort())
        ->setSecure($request->isSecure())
        ->setStatusCode(400)
        ->setMessageParam('Bad request')
        ->setDetailsParam('Missing type param.');
    }
    if (!$blockchainConfig = $this->configService->load($blockchainRequest->getTypeParam())) {

      return BlockchainResponse::create()
        ->setIp($request->getClientIp())
        ->setPort($request->getPort())
        ->setSecure($request->isSecure())
        ->setStatusCode(400)
        ->setMessageParam('Bad request')
        ->setDetailsParam('Invalid type param.');
    }
    if (!$request->isSecure() && !$blockchainConfig->getAllowNotSecure()) {

      return BlockchainResponse::create()
        ->setIp($request->getClientIp())
        ->setPort($request->getPort())
        ->setSecure($request->isSecure())
        ->setStatusCode(400)
        ->setMessageParam('Bad request')
        ->setDetailsParam('Incorrect protocol.');
    }
    if ($blockchainConfig->getType() === BlockchainConfigInterface::TYPE_SINGLE) {

      return BlockchainResponse::create()
        ->setIp($request->getClientIp())
        ->setPort($request->getPort())
        ->setSecure($request->isSecure())
        ->setStatusCode(403)
        ->setMessageParam('Forbidden')
        ->setDetailsParam('Access to this resource is restricted.');
    }
    if (!$blockchainRequest->hasSelfParam()) {

      return BlockchainResponse::create()
        ->setIp($blockchainRequest->getIp())
        ->setPort($request->getPort())
        ->setSecure($request->isSecure())
        ->setStatusCode(400)
        ->setMessageParam('Bad request')
        ->setDetailsParam('No self param.');
    }
    if ($authHandler = $this->blockchainAuthManager->getHandler($blockchainConfig)) {
      if (!$authHandler->authorize($blockchainRequest, $blockchainConfig)) {

        return BlockchainResponse::create()
          ->setIp($blockchainRequest->getIp())
          ->setPort($request->getPort())
          ->setSecure($request->isSecure())
          ->setStatusCode(401)
          ->setMessageParam('Unauthorized')
          ->setDetailsParam('Auth token invalid.');
      }
    }
    if ($blockchainRequest->getRequestType() !== BlockchainRequestInterface::TYPE_SUBSCRIBE) {
      if (!$blockchainNode = $this->blockchainNodeService->loadBySelfAndType(
        $blockchainRequest->getSelfParam(), $blockchainConfig->id())) {

        return BlockchainResponse::create()
          ->setIp($blockchainRequest->getIp())
          ->setPort($request->getPort())
          ->setSecure($request->isSecure())
          ->setStatusCode(401)
          ->setMessageParam('Unauthorized')
          ->setDetailsParam('Not subscribed yet.');
      }
      if (!$blockchainNode->hasClientData()) {
        $blockchainNode->setIp($request->getClientIp())
          ->setPort($request->getPort())
          ->setSecure($request->isSecure())
          ->save();
      }
    }
    if ($filterList = $blockchainConfig->getBlockchainFilterListAsArray()) {
      if ($blockchainConfig->getFilterType() === BlockchainConfigInterface::FILTER_TYPE_BLACKLIST) {
        if (in_array($blockchainRequest->getIp(), $filterList)) {

          return BlockchainResponse::create()
            ->setIp($blockchainRequest->getIp())
            ->setPort($request->getPort())
            ->setSecure($request->isSecure())
            ->setStatusCode(403)
            ->setMessageParam('Forbidden')
            ->setDetailsParam('You are forbidden to access this resource.');
        }
      }
      else {
        if (!in_array($blockchainRequest->getIp(), $filterList)) {

          return BlockchainResponse::create()
            ->setIp($blockchainRequest->getIp())
            ->setPort($request->getPort())
            ->setSecure($request->isSecure())
            ->setStatusCode(403)
            ->setMessageParam('Forbidden')
            ->setDetailsParam('You are forbidden to access this resource.');
        }
      }
    }

    return $blockchainRequest;
  }

  /**
   * {@inheritdoc}
   */
  public function validateBlocks(array $blocks) {

    $previousBlock = NULL;
    foreach ($blocks as $block) {
      if (!$this->blockIsValid($block, $previousBlock)) {

        return FALSE;
      }
      $previousBlock = $block;
    }

    return TRUE;
  }

}
