<?php
/**
 * @file
 * Drupal\Tests\block_render\Unit\Plugin\rest\resource\BlockRenderResourceBase.
 */

namespace Drupal\Tests\block_render\Unit\Plugin\rest\resource;

use Drupal\block_render\Plugin\rest\resource\BlockRenderResource;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Base Class Resource Tests.
 */
abstract class BlockRenderResourceBase extends UnitTestCase {

  /**
   * Gets the logger.
   */
  public function getLogger() {
    return $this->getMockBuilder('Psr\Log\LoggerInterface')
      ->getMock();
  }

  /**
   * Gets the logger.
   */
  public function getCurrentUser() {
    return $this->getMockBuilder('Drupal\Core\Session\AccountInterface')
      ->getMock();
  }

  /**
   * Gets the storage.
   */
  public function getStorage() {
    return $this->getMockBuilder('Drupal\Core\Entity\EntityStorageInterface')
      ->getMock();
  }

  /**
   * Gets the entity manager.
   */
  public function getEntityManager() {
    return $this->getMockBuilder('Drupal\Core\Entity\EntityManagerInterface')
      ->getMock();
  }

  /**
   * Gets the entity manager.
   */
  public function getBuilder() {
    return $this->getMockBuilder('Drupal\block_render\BlockBuilderInterface')
      ->getMock();
  }

  /**
   * Gets the entity manager.
   */
  public function getPlugin() {
    return $this->getMockBuilder('Drupal\Core\Block\BlockPluginInterface')
      ->getMock();
  }

}
