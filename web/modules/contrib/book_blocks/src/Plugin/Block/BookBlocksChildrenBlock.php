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
 *   id = "book_block_children",
 *   admin_label = @Translation("Book Children Links"),
 *   category = @Translation("Menus")
 * )
 */
class BookBlocksChildrenBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
      'header' => "",
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    if (($node = $this->requestStack->getCurrentRequest()->get('node')) && !empty($node->book['bid'])) {

      $book_manager = \Drupal::service('book.manager');
      $book_link = $book_manager->loadBookLink ($node->id());
      $book_id = $node->book['bid'];
      $book_node = node_load ($book_id);

      // Only display this block when the user is browsing a book and do
      // not show unpublished books.
      $book_id = $node->book['bid'];
      $book_node = node_load ($book_id);

      // Provide extra variables for themers. Not needed by default.
      $variables['#book_id'] = $book_id;
      $variables['#book_title'] = $book_node->getTitle();
      $variables['#book_url'] = \Drupal::url('entity.node.canonical', ['node' => $book_id]);
      $variables['#current_depth'] = $node->book['depth'];
      $variables['#tree'] = '';

      /** @var \Drupal\book\BookOutline $book_outline */
      $book_outline = \Drupal::service('book.outline');

      $build=[]; // loads css

      $book_manager = \Drupal::service('book.manager');
      $book_link = $book_manager->loadBookLink ($node->id());
      $variables['#book_link']=$book_link;

      if ($book_id) {
        $variables['#tree'] = $book_outline->childrenLinks($book_link);

        if ($prev = $book_outline->prevLink($book_link)) {
          $prev_href = \Drupal::url('entity.node.canonical', ['node' => $prev['nid']]);
          $build['#attached']['html_head_link'][][] = [

            'rel' => 'prev',
            'href' => $prev_href,
          ];
          $variables['#prev_url'] = $prev_href;
          $variables['#prev_title'] = $prev['title'];
        }

        /** @var \Drupal\book\BookManagerInterface $book_manager */
        if ($book_link['pid'] && $parent = $book_manager->loadBookLink($book_link['pid'])) {
          $parent_href = \Drupal::url('entity.node.canonical', ['node' => $book_link['pid']]);
          $build['#attached']['html_head_link'][][] = [
            'rel' => 'up',
            'href' => $parent_href,
          ];
          $variables['#parent_url'] = $parent_href;
          $variables['#parent_title'] = $parent['title'];
        }

        if ($next = $book_outline->nextLink($book_link)) {
          $next_href = \Drupal::url('entity.node.canonical', ['node' => $next['nid']]);
          $build['#attached']['html_head_link'][][] = [
            'rel' => 'next',
            'href' => $next_href,
          ];
          $variables['#next_url'] = $next_href;
          $variables['#next_title'] = $next['title'];
        }

        $variables['#has_links'] = FALSE;
        // Link variables to filter for values and set state of the flag variable.
        $links = ['#prev_url', '#prev_title', '#parent_url', '#parent_title', '#next_url', '#next_title'];
        foreach ($links as $link) {
          if (isset($variables[$link])) {
            // Flag when there is a value.
            $variables['#has_links'] = TRUE;
          }
          else {
            // Set empty to prevent notices.
            $variables[$link] = '';
          }
        }

        if (!empty($build)) {
          \Drupal::service('renderer')->render($build);
        }

        $variables['#theme']='book_blocks_children';
        $variables['#attached']['library'][]='book_blocks/global-styling';
        $variables['#attributes']=['class' => ['book-blocks-children']];
        return $variables;
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
