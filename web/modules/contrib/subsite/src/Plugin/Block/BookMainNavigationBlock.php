<?php

/**
 * @file
 * Contains \Drupal\subsite\Plugin\Block\BookMainNavigationBlock.
 */

namespace Drupal\subsite\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\book\BookManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides a 'Book navigation' block for use as main/primary navigation.
 *
 * @Block(
 *   id = "subsite_book_main_navigation",
 *   admin_label = @Translation("Subsite book main navigation"),
 *   category = @Translation("Menus")
 * )
 */
class BookMainNavigationBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new BookNavigationBlock instance.
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
      $container->get('subsite.book.manager'),
      $container->get('entity.manager')->getStorage('node')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'block_mode' => "all pages",
    );
  }

  /**
   * {@inheritdoc}
   */
  function blockForm($form, FormStateInterface $form_state) {
    $options = array(
      'all pages' => $this->t('Show block on all pages'),
      'book pages' => $this->t('Show block only on book pages'),
    );
    $form['book_block_mode'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Book navigation block display'),
      '#options' => $options,
      '#default_value' => $this->configuration['block_mode'],
      '#description' => $this->t("If <em>Show block on all pages</em> is selected, the block will contain the automatically generated menus for all of the site's books. If <em>Show block only on book pages</em> is selected, the block will contain only the one menu corresponding to the current page's book. In this case, if the current page is not in a book, no block will be displayed. The <em>Page specific visibility settings</em> or other visibility settings can be used in addition to selectively display this block."),
      );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['block_mode'] = $form_state->getValue('book_block_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_bid = 0;

    if ($node = $this->requestStack->getCurrentRequest()->get('node')) {
      $current_bid = empty($node->book['bid']) ? 0 : $node->book['bid'];
    }

    if ($current_bid) {
      $data = $this->bookManager->bookTreeAllData($node->book['bid'], $node->book);

      $items = $this->bookManager->bookTreeOutput($data);

      // Mimic main menu.
      $build['#theme'] = 'menu__main';
      $build['#items'] = $items;


      return $build;
    }

    return array();
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredCacheContexts() {
    // The "Book navigation" block must be cached per role and book navigation
    // context.
    return [
      'user.roles',
      'route.book_navigation',
    ];
  }

}
