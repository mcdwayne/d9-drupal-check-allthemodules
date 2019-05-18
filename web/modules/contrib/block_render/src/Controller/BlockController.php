<?php
/**
 * @file
 * Contains \Drupal\block_render\Controller\Block.
 */

namespace Drupal\block_render\Controller;

use Drupal\block\BlockInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Block Controllers.
 */
class BlockController extends ControllerBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Constructor to add the dependencies.
   */
  public function __construct(
    EntityManagerInterface $entity_manager,
    RequestStack $request,
    AccountInterface $current_user,
    TranslationInterface $string_translation) {

    $this->entityManager = $entity_manager;
    $this->request = $request;
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('string_translation')
    );
  }

  /**
   * Render Controller.
   *
   * @param \Drupal\block\BlockInterface $block
   *   The block to render.
   *
   * @return array
   *   Build array of the requested block.
   */
  public function render(BlockInterface $block) {
    if (!$block->getPlugin()->access($this->currentUser())) {
      throw new AccessDeniedHttpException($this->t('Access Denied to Block with ID @id', ['@id' => $block->id()]));
    }

    // Add the configuration to the block.
    $config = $this->getRequest()->query->all();
    $block->getPlugin()->setConfiguration($config);

    // Build the block.
    $build = $this->entityManager()->getViewBuilder('block')->view($block);

    // If a lazy_builder is returned, execute that first.
    if (isset($build['#lazy_builder'])) {
      $build = call_user_func_array($build['#lazy_builder'][0], $build['#lazy_builder'][1]);
    }

    // Add the query arguments to the cache contexts.
    if (isset($build['#cache']['contexts'])) {
      $contexts = $build['#cache']['contexts'];
      $build['#cache']['contexts'] = Cache::mergeContexts(['url.query_args'], $contexts);
    }
    else {
      $build['#cache']['contexts'] = ['url.query_args'];
    }

    return $build;
  }

  /**
   * Render Title.
   *
   * @param \Drupal\block\BlockInterface $block
   *   The block to get the title from.
   *
   * @return string
   *   Title of the page.
   */
  public function renderTitle(BlockInterface $block) {
    return $block->label();
  }

  /**
   * Gets the current request.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   Request Object.
   */
  public function getRequest() {
    return $this->request->getCurrentRequest();
  }

}
