<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Entity\BlockchainNode;
use Drupal\blockchain\Entity\BlockchainNodeInterface;
use Drupal\blockchain\Utils\BlockchainRequestInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class BlockchainNodeService.
 *
 * @package Drupal\blockchain\Service
 */
class BlockchainNodeService implements BlockchainNodeServiceInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * BlockchainNodeService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {

    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage() {

    try {
      return $this->entityTypeManager
        ->getStorage(BlockchainNode::entityTypeId());
    }
    catch (\Exception $exception) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getList($offset = NULL, $limit = NULL) {

    $list = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->range($offset, $limit)
      ->execute();

    return $this->getStorage()->loadMultiple($list);
  }

  /**
   * {@inheritdoc}
   */
  public function getCount() {

    return $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function exists($id) {

    return (bool) $this->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {

    return $this->getStorage()->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadBySelfAndType($self, $blockchainTypeId) {

    if ($node = $this->load($this->generateId($blockchainTypeId, $self))) {
      if ($node->getBlockchainTypeId() == $blockchainTypeId) {

        return $node;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function existsBySelfAndType($self, $blockchainTypeId) {

    return (bool) $this->loadBySelfAndType($self, $blockchainTypeId);
  }

  /**
   * {@inheritdoc}
   */
  public function create($blockchainTypeId, $self, $addressSource, $address, $ip = NULL, $port = NULL, $secure = NULL, $label = NULL, $save = TRUE) {

    /** @var \Drupal\blockchain\Entity\BlockchainNodeInterface $blockchainNode */
    $blockchainNode = $this->getStorage()->create();
    $label = $label ? $label : $self;
    $ip = $ip? $ip : $address;
    $blockchainNode
      ->setBlockchainTypeId($blockchainTypeId)
      ->setSelf($self)
      ->setId($this->generateId($blockchainTypeId, $self))
      ->setAddressSource($addressSource)
      ->setLabel($label ? $label : $self)
      ->setAddress($address)
      ->setIp($ip)
      ->setSecure($secure)
      ->setPort($port);
    try {
      if ($save) {
        $this->getStorage()->save($blockchainNode);
      }

      return $blockchainNode;
    }
    catch (\Exception $exception) {

      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createFromRequest(BlockchainRequestInterface $request, $save = TRUE) {

    if ($request->hasSelfUrl() && UrlHelper::isValid($request->getSelfUrl())) {

      return $this->create(
        $request->getTypeParam(),
        $request->getSelfParam(),
        BlockchainNodeInterface::ADDRESS_SOURCE_CLIENT,
        $request->getSelfUrl(),
        $request->getIp(),
        $request->getPort(), $request->isSecure(),
        NULL,
        $save);
    }

    return $this->create(
      $request->getTypeParam(),
      $request->getSelfParam(),
      BlockchainNodeInterface::ADDRESS_SOURCE_REQUEST,
      $request->getIp(),
      $request->getIp(),
      $request->getPort(),
      $request->isSecure(),
      NULL,
      $save);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(BlockchainNodeInterface $blockchainNode) {
    try {
      $this->getStorage()->delete([$blockchainNode]);
      return TRUE;
    }
    catch (\Exception $exception) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(BlockchainNodeInterface $blockchainNode) {
    try {
      $this->getStorage()->save($blockchainNode);
      return TRUE;
    }
    catch (\Exception $exception) {
      return FALSE;
    }
  }

  /**
   * Id generator.
   *
   * @param string $blockchainTypeId
   *   Type of blockchain.
   * @param string $self
   *   Self id param.
   *
   * @return string
   *   Hash.
   */
  public function generateId($blockchainTypeId, $self) {

    return sha1($blockchainTypeId . '_' . $self);
  }

}
