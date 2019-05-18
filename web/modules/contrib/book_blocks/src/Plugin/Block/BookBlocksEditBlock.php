<?php
/**
 * Based on book module's block
 */

namespace Drupal\book_blocks\Plugin\Block;

use Drupal\book\BookManager;
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
 *   id = "book_block_edit",
 *   admin_label = @Translation("Book Edit Links"),
 *   category = @Translation("Menus")
 * )
 */
class BookBlocksEditBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $config=\Drupal::config('book_blocks.settings');
    return $config->get('block.settings.book_block_edit');
  }


  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form[] = [
      '#markup' => 
         '<h2>'.$this->t('Book Blocks: Edit Block Configuration').'</h2>'.
         '<p>'.$this->t('In general, the title (above) should not be displayed.').'</p>',
      ];
    $form['css'] = [
      '#type' => 'select',
      '#title' => $this->t('Display style'),
      '#options' => [
          'book-blocks-edit-text'    => t('Text'),
          'book-blocks-edit-rounded' => t('Rounded'),
          'book-blocks-edit-button'  => t('Button'),
          'book-blocks-edit-custom'  => t('Custom'),
          ],
      '#default_value' => $this->configuration['css'],
      '#description' => $this->t('Custom includes CSS class book-blocks-edit-custom.'),
      ];
    $form['icons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use icons'),
      '#default_value' => $this->configuration['icons'],
      '#description' => $this->t("Use icons instead of text."),
      ];
    $form['toc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Table of Contents'),
      '#default_value' => $this->configuration['toc'],
      '#description' => $this->t("Include table of contents link."),
      ];
    $form['nav'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Navigation links'),
      '#default_value' => $this->configuration['nav'],
      '#description' => $this->t("Include prior/up/next navigation links."),
      ];
    $form['add_sibling'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add sibling link text'),
      '#default_value' => $this->configuration['add_sibling'],
      '#description' => $this->t("Leave empty to no including the Add sibling link."),
      ];
    $form['replace'] = [
      '#type' => 'details',
      '#open' => false,
      '#title' => $this->t('Replacement Patterns'),
      ];
    $form['replace'][] = [
      '#markup' => '<h3>'.$this->t('These patterns can be used in the link fields').'</h3>'.'
        <ul>
          <li>{{ nid }} - '.$this->t('Curren node ID').'
          <li>{{ book_id }} - '.$this->t('Book node ID').'
          <li>{{ prev_id }} - '.$this->t('Previous node ID').'
          <li>{{ next_id }} - '.$this->t('Next node ID').'
          <li>{{ parent_id }} - '.$this->t('Parent node ID').'
        </ul>
        '
      ];
    $form['left_link'] = [
      '#type' => 'details',
      '#open' => true,
      '#title' => $this->t('Left link'),
      ];
    $form['left_link']['left_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $this->configuration['left_link']['name'],
      '#description' => $this->t("Leave empty to not include a link."),
      ];
    $form['left_link']['left_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link URL'),
      '#default_value' => $this->configuration['left_link']['url'],
      '#description' => $this->t("URL for this link. No link if this is empty."),
      ];
    $form['left_link']['left_hint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link hint text'),
      '#default_value' => $this->configuration['left_link']['hint'],
      '#description' => $this->t("Hint for link."),
      ];
    $form['middle_link'] = [
      '#type' => 'details',
      '#open' => true,
      '#title' => $this->t('Middle link'),
      ];
    $form['middle_link']['middle_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $this->configuration['middle_link']['name'],
      '#description' => $this->t("Leave empty to not include a link."),
      ];
    $form['middle_link']['middle_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link URL'),
      '#default_value' => $this->configuration['middle_link']['url'],
      '#description' => $this->t("URL for this link. No link if this is empty."),
      ];
    $form['middle_link']['middle_hint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link hint text'),
      '#default_value' => $this->configuration['middle_link']['hint'],
      '#description' => $this->t("Hint for link."),
      ];
    $form['right_link'] = [
      '#type' => 'details',
      '#open' => true,
      '#title' => $this->t('Right link'),
      ];
    $form['right_link']['right_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $this->configuration['right_link']['name'],
      '#description' => $this->t("Leave empty to not include a link."),
      ];
    $form['right_link']['right_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link URL'),
      '#default_value' => $this->configuration['right_link']['url'],
      '#description' => $this->t("URL for this link. No link if this is empty."),
      ];
    $form['right_link']['right_hint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link hint text'),
      '#default_value' => $this->configuration['right_link']['hint'],
      '#description' => $this->t("Hint for link."),
      ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['add_sibling'] = $form_state->getValue('add_sibling');
    $this->configuration['toc'] = $form_state->getValue('toc');
    $this->configuration['nav'] = $form_state->getValue('nav');

    $this->configuration['css'] = $form_state->getValue('css');
    $this->configuration['icons'] = $form_state->getValue('icons');

    $left_link = $form_state->getValue('left_link');
    $this->configuration['left_link']['name']  = $left_link['left_name'];
    $this->configuration['left_link']['url']   = $left_link['left_url'];
    $this->configuration['left_link']['hint']  = $left_link['left_hint'];

    $middle_link = $form_state->getValue('middle_link');
    $this->configuration['middle_link']['name']  = $middle_link['middle_name'];
    $this->configuration['middle_link']['url']   = $middle_link['middle_url'];
    $this->configuration['middle_link']['hint']  = $middle_link['middle_hint'];

    $right_link = $form_state->getValue('right_link');
    $this->configuration['right_link']['name'] = $right_link['right_name'];
    $this->configuration['right_link']['url']  = $right_link['right_url'];
    $this->configuration['right_link']['hint'] = $right_link['right_hint'];
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
          $token['prev_id']=$prev['nid'];
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
          $token['parent_id']=$book_link['pid'];
          $parent_href = \Drupal::url('entity.node.canonical', ['node' => $book_link['pid']]);
          $build['#attached']['html_head_link'][][] = [
            'rel' => 'up',
            'href' => $parent_href,
          ];
          $variables['#parent_url'] = $parent_href;
          $variables['#parent_title'] = $parent['title'];
        }

        if ($next = $book_outline->nextLink($book_link)) {
          $token['right_id']= $next['nid'];
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

        $variables['#theme']='book_blocks_edit';
        $variables['#attached']['library'][]='book_blocks/global-styling';
        $variables['#attributes']=['class' => ['book-blocks-edit']];

        $variables['#css']=$this->configuration['css'];
        $variables['#icons']=$this->configuration['icons'];
        $variables['#toc']=$this->configuration['toc'];
        $variables['#nav']=$this->configuration['nav'];

        // create token replacement table
        $token['nid']=$node->id();
        $token['book_id']=$book_id;
 
        foreach ($token as $key => $value) {
          $pattern[]="/\{\{ $key \}\}/";
          $replace[]=$value;
        }

        // get custom links
        foreach (['left_link', 'middle_link', 'right_link'] as $link ) {
          $name=preg_replace($pattern,$replace,$this->configuration[$link]['name']);
          $url=preg_replace($pattern,$replace,$this->configuration[$link]['url']);
          if ( $name && $url ) {
            $variables["#$link"]=preg_replace($pattern,$replace,$this->configuration[$link]);
          }
        }

        // get standard links
        $account = \Drupal::currentUser();

        if (isset($node->book['depth'])) {
          if ( /* need to get view mode somehow ======= $variables['view_mode'] == 'full' && */ node_is_page($node)) {
            $child_type = \Drupal::config('book.settings')->get('child_type');
            $access_control_handler = \Drupal::entityManager()->getAccessControlHandler('node');
            if (($account->hasPermission('add content to books') || $account->hasPermission('administer book outlines')) && $access_control_handler->createAccess($child_type) && $node->isPublished() && $node->book['depth'] < BookManager::BOOK_MAX_DEPTH) {
              $variables['#child_link']['name']=t('Add child page');
              $variables['#child_link']['hint']=t('Add a new book page below this one');
              $variables['#child_link']['url']=Url::fromRoute('node.add', ['node_type' => $child_type], ['query' => ['parent' => $node->id()]]);

              if($book_link['pid']) {
                $variables['#sibling_link']['name']=t('Add sibling page');
                $variables['#sibling_link']['hint']=t('Add a new book page at the same level as this');
                $variables['#sibling_link']['url']=Url::fromRoute('node.add', ['node_type' => $child_type], ['query' => ['parent' => $book_link['pid']]]);
              }
            }
          }

          if ($account->hasPermission('access printer-friendly version')) {
            $variables['#print_link']['name']=t('Printer-friendly version');
            $variables['#print_link']['hint']=t('Show a printer-friendly version of this book page and its sub-pages.');
            $variables['#print_link']['url']=Url::fromRoute('book.export', [
                  'type' => 'html',
                  'node' => $node->id(),
                ]);
          }
        }

        if ($this->configuration['toc']) {
          // There should only be one element at the top level
          $data = $this->bookManager->bookTreeAllData($node->book['bid'], $node->book);
          $data = reset($data);
          $below = $this->bookManager->bookTreeOutput($data['below']);
          $book_node = node_load($book_id);
          $title = $book_node->getTitle();
          $url = Url::fromUri("entity:node/$book_id")->toString();
          $title_class = $node->id() == $book_id ? 'book--active-trail' : '';
          if (!empty($below)) {
            return array (
              $variables,
              array (
                '#type' => 'container',
                '#attributes' => ['id' => ['book-blocks-toc-element'], 'class' => ['books-blocks-edit-toc book-blocks-toc'], 'style' => ['display:none;']],
                array (
                  '#markup' => "<div class='book-blocks-toc-book $title_class'><span class='book-blocks-toc-prefix'>".
                    t('Book').
                    ": </span><a href='$url'>$title</a></div>",
                  $below,
                ),
              ),
            );
          }
        }

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
