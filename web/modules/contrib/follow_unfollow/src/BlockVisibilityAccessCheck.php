<?php

namespace Drupal\follow_unfollow;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the block entity type.
 *
 * @see \Drupal\block\Entity\Block
 */
class BlockVisibilityAccessCheck {
  /**
   * The $configFactory variable.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The taxonomy storage.
   *
   * @var \Drupal\Taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The node storage.
   *
   * @var \Drupal\Node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The access variable.
   *
   * @var \Drupal\follow_unfollow\BlockVisibilityAccessCheck
   */
  protected $access;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager')
    );
  }

  /**
   * Constructs the BlockVisibilityAccessCheck.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configFactory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityManagerInterface $entityManager) {
    $this->configFactory = $configFactory;
    $this->entityManager = $entityManager;
    $this->termStorage = $entityManager->getStorage('taxonomy_term');
    $this->nodeStorage = $entityManager->getStorage('node');
    $this->userStorage = $entityManager->getStorage('user');
    $this->access = FALSE;
  }

  /**
   * Determines if the Block visibility access.
   *
   * @param string $path
   *   The current path.
   *
   * @return bool
   *   TRUE if the block visibility access exists, FALSE otherwise.
   */
  public function checkAccess($path = NULL) {
    $path = trim($path, '/');
    $pathArgument = explode('/', $path);

    // Checking for node accessibility.
    if (isset($pathArgument) && !empty($pathArgument) && $pathArgument[0] == 'node' && !empty($pathArgument[1])) {
      $nodeObject = $this->nodeStorage->load($pathArgument[1]);
      $type = $nodeObject->getType();
      $nodeSetting = $this->configFactory->get('follow_unfollow.admin.settings')->get('follow_unfollow.content_type');
      $this->access = in_array($type, $nodeSetting) ? TRUE : FALSE;
      return $this->access;
    }
    // Check for Vocabulary accessibility.
    elseif (isset($pathArgument) && !empty($pathArgument) && $pathArgument[0] == 'taxonomy'
      && $pathArgument[1] == 'term' && !empty($pathArgument[1])) {
      $taxonomyObject = $this->termStorage->load($pathArgument[2]);
      $vocabulary = $taxonomyObject->bundle();
      $taxonomySetting = $this->configFactory->get('follow_unfollow.admin.settings')->get('follow_unfollow.vocabulary_type');
      $this->access = in_array($vocabulary, $taxonomySetting) ? TRUE : FALSE;
      return $this->access;
    }
    // Chceck for user accessibility.
    elseif (isset($pathArgument) && !empty($pathArgument) && $pathArgument[0] == 'user' && !empty($pathArgument[1])) {
      $userSetting = $this->configFactory->get('follow_unfollow.admin.settings')->get('follow_unfollow.user');
      $this->access = isset($userSetting) ? TRUE : FALSE;
      return $this->access;
    }
    else {
      return $this->access;
    }
  }

}
