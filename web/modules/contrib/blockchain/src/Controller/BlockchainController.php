<?php

namespace Drupal\blockchain\Controller;

use Drupal\blockchain\Entity\BlockchainConfigInterface;
use Drupal\blockchain\Entity\BlockchainNodeInterface;
use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Blockchain controller.
 */
class BlockchainController extends ControllerBase {

  /**
   * Blockchain service.
   *
   * @var \Drupal\blockchain\Service\BlockchainServiceInterface
   */
  protected $blockchainService;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

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
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * Blocks validation callback.
   *
   * @param \Drupal\blockchain\Entity\BlockchainConfigInterface $blockchain_config
   *   Blockchain config object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Response.
   */
  public function storageValidate(BlockchainConfigInterface $blockchain_config) {

    $type = $blockchain_config->id();
    $this->blockchainService->getConfigService()->setCurrentConfig($type);
    if ($this->blockchainService->getStorageService()->checkBlocks()) {
      $this->messenger()->addStatus($this->t('Blocks are valid'));
    }
    else {
      $this->messenger()->addError($this->t('Validation failed'));
    }

    return $this->redirect("entity.{$type}.collection");
  }

  /**
   * Controller callback.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Response.
   */
  public function discoverConfigs() {

    $count = $this->blockchainService->getConfigService()->discoverBlockchainConfigs();
    $this->messenger()->addStatus($this->t('Discovered @count configurations.', [
      '@count' => $count,
    ]));

    return $this->redirect('entity.blockchain_config.collection');
  }

  /**
   * Controller callback.
   *
   * @param BlockchainConfigInterface $blockchain_config
   *   Blockchain config.
   * @param BlockchainNodeInterface $blockchain_node
   *   Blockchain node.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Response.
   */
  public function pull(BlockchainConfigInterface $blockchain_config, BlockchainNodeInterface $blockchain_node) {

    $this->blockchainService->getConfigService()->setCurrentConfig($blockchain_config->id());
    $endPoint = $blockchain_node->getEndPoint();
    if ($this->blockchainService->getLockerService()->lockAnnounce()) {
      try {
        $count = 0;
        $result = $this->blockchainService->getApiService()
          ->executeFetch($endPoint, $this->blockchainService->getStorageService()->getLastBlock());
        $collisionHandler = $this->blockchainService->getCollisionHandler();
        if ($collisionHandler->isPullGranted($result)) {
          $count = $collisionHandler->processNoConflict($result, $endPoint);
        }
        elseif ($result->isCountParamValid()) {
          $count = $collisionHandler->processConflict($result, $endPoint);
        }
      }
      catch (\Exception $exception) {
        $this->messenger()->addError($exception->getMessage());
      }
      finally {
        $this->blockchainService->getLockerService()->releaseAnnounce();
        $this->messenger()->addStatus($this->t('Added @count items.',[
          '@count' => $count,
        ]));
      }
    }
    else {
      $this->messenger()
        ->addError($this->t('Announce handling is locked.'));
    }

    return $this->redirect("entity.{$blockchain_node->getEntityTypeId()}.collection");
  }

}
