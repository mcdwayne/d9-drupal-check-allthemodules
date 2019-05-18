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
 * BlockchainBlockData as simple string.
 *
 * @BlockchainAuth(
 *  id = "none",
 *  label = @Translation("No auth"),
 * )
 */
class BlockchainAuthNone extends PluginBase implements BlockchainAuthInterface,
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              BlockchainServiceInterface $blockchainService) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockchainService = $blockchainService;
  }

  /**
   * {@inheritdoc}
   */
  public function authorize(BlockchainRequestInterface $blockchainRequest, BlockchainConfigInterface $blockchainConfig) {

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addAuthParams(array &$params, BlockchainConfigInterface $blockchainConfig) {}

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
  public function tokenGenerate(BlockchainConfigInterface $blockchainConfig) {

    return $this->blockchainService->getHashService()
      ->hash($blockchainConfig->getBlockchainId() . $blockchainConfig->getNodeId());
  }

}
