<?php

namespace Drupal\blockchain\Plugin\BlockchainAuth;

use Drupal\blockchain\Entity\BlockchainConfigInterface;
use Drupal\blockchain\Plugin\BlockchainAuthInterface;
use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\blockchain\Utils\BlockchainRequestInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * BlockchainAuthSharedKey as simple string.
 *
 * @BlockchainAuth(
 *  id = "shared_key",
 *  label = @Translation("Shared key"),
 * )
 */
class BlockchainAuthSharedKey extends PluginBase implements BlockchainAuthInterface,
  ContainerFactoryPluginInterface {

  /**
   * Blockchain service.
   *
   * @var \Drupal\blockchain\Service\BlockchainServiceInterface
   */
  protected $blockchainService;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockchainServiceInterface $blockchainService) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockchainService = $blockchainService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('blockchain.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function authorize(BlockchainRequestInterface $blockchainRequest, BlockchainConfigInterface $blockchainConfig) {

    if (!$authToken = $blockchainRequest->getAuthParam()) {

      return FALSE;
    }
    if (!$this->authIsValid(
      $blockchainRequest->getSelfParam(),
      $blockchainRequest->getAuthParam(),
      $blockchainConfig->getBlockchainId())
    ) {

      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addAuthParams(array &$params, BlockchainConfigInterface $blockchainConfig) {

    $params[BlockchainRequestInterface::PARAM_AUTH] = $this->tokenGenerate($blockchainConfig);
  }

  /**
   * Validates auth against current blockchain.
   *
   * @param string $self
   *   Self key.
   * @param string $auth
   *   Auth key.
   * @param string $blockchainId
   *   Blockchain id.
   *
   * @return bool
   *   Validation result.
   */
  public function authIsValid($self, $auth, $blockchainId) {

    return $this->blockchainService->getHashService()->hash($blockchainId . $self) === $auth;
  }

  /**
   * {@inheritdoc}
   */
  public function tokenGenerate(BlockchainConfigInterface $blockchainConfig) {

    return $this->blockchainService->getHashService()
      ->hash($blockchainConfig->getBlockchainId() . $blockchainConfig->getNodeId());
  }

}
