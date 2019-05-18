<?php
/**
 * Based on book module's block
 */

namespace Drupal\book_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\book\BookManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;

/**
 * Provides another 'Book navigation' block.
 *
 * @Block(
 *   id = "book_block_toc",
 *   admin_label = @Translation("Book Table of Contents"),
 *   category = @Translation("Menus")
 * )
 */
class BookBlocksTOCBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a new BookBlockNavigationBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, BookManagerInterface $book_manager, EntityStorageInterface $node_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->requestStack = $request_stack;
    $this->bookManager = $book_manager;
    $this->nodeStorage = $node_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('book.manager'),
      $container->get('entity.manager')->getStorage('node')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    if (($node = $this->requestStack->getCurrentRequest()->get('node')) && !empty($node->book['bid'])) {
      // Only display this block when the user is browsing a book and do
      // not show unpublished books.
      $book_id= $node->book['bid'];
      $nid = \Drupal::entityQuery('node')
        ->condition('nid', $book_id, '=')
        ->condition('status', NodeInterface::PUBLISHED)
        ->execute();

      // Only show the block if the user has view access for the top-level node.
      if ($nid) {
        $tree = $this->bookManager->bookTreeAllData($node->book['bid'], $node->book);
        // There should only be one element at the top level.
        $data = array_shift($tree);
        $below = $this->bookManager->bookTreeOutput($data['below']);
        $book_node = node_load($book_id);
        $title = $book_node->getTitle();
        $url = Url::fromUri("entity:node/$book_id")->toString();
        $title_class = $node->id() == $book_id ? 'book--active-trail' : '';
        if (!empty($below)) {
          return array (
            '#attached' => ['library' => ['book_blocks/global-styling']],
            '#type' => 'container',
            '#attributes' => array ('class' => array ('book-blocks-toc')), 
            array ( 
              '#markup' => "<div class='book-blocks-toc-book $title_class'><span class='book-blocks-toc-prefix'>".
                t('Book').
                ": </span><a href='$url'>$title</a></div>",
              $below,
            ),
          );
        }
      }
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route.book_block_toc']);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Make cacheable in https://www.drupal.org/node/2483181
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
