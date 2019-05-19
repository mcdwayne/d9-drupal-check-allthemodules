<?php

namespace Drupal\site_map\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;
use Drupal\book\BookManagerInterface;
use Drupal\Core\Url;

/**
 * Provides a configuration form for sitemap.
 */
class SitemapSettingsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * Constructs a SitemapSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactory $config_factory, ModuleHandler $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $module_handler = $container->get('module_handler');
    $form = new static(
      $container->get('config.factory'),
      $module_handler
    );
    if ($module_handler->moduleExists('book')) {
      $form->setBookManager($container->get('book.manager'));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_map_settings';
  }

  /**
   * Set book manager service.
   *
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   Book manager service to set.
   */
  public function setBookManager(BookManagerInterface $book_manager) {
    $this->bookManager = $book_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('site_map.settings');

    $form['page_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#default_value' => $config->get('page_title'),
      '#description' => $this->t('Page title that will be used on the @link.', array('@link' => $this->l($this->t('site map page'), Url::fromRoute('site_map.page')))),
    );

    $site_map_message = $config->get('message');
    $form['message'] = array(
      '#type' => 'text_format',
      '#format' => isset($site_map_message['format']) ? $site_map_message['format'] : NULL,
      '#title' => $this->t('Site map message'),
      '#default_value' => $site_map_message['value'],
      '#description' => $this->t('Define a message to be displayed above the site map.'),
    );

    $form['site_map_content'] = array(
      '#type' => 'details',
      '#title' => $this->t('Site map content'),
      '#open' => TRUE,
    );
    $site_map_ordering = array();
    $form['site_map_content']['show_front'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show front page'),
      '#default_value' => $config->get('show_front'),
      '#description' => $this->t('When enabled, this option will include the front page in the site map.'),
    );
    $site_map_ordering['front'] = t('Front page');
    $form['site_map_content']['show_titles'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show titles'),
      '#default_value' => $config->get('show_titles'),
      '#description' => $this->t('When enabled, this option will show titles. Disable to not show section titles.'),
    );

    if ($this->moduleHandler->moduleExists('blog')) {
      $form['site_map_content']['show_blogs'] = array(
        '#type' => 'checkbox',
        '#title' => t('Show active blog authors'),
        '#default_value' => $config->get('show_blogs'),
        '#description' => t('When enabled, this option will show the 10 most active blog authors.'),
      );
      $site_map_ordering['blogs'] = t('Active blog authors');
    }

    if ($this->moduleHandler->moduleExists('book')) {
      $book_options = array();
      foreach ($this->bookManager->getAllBooks() as $book) {
        $book_options[$book['bid']] = $book['title'];
      }
      $form['site_map_content']['show_books'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Books to include in the site map'),
        '#default_value' => $config->get('show_books'),
        '#options' => $book_options,
        '#multiple' => TRUE,
      );
      $form['site_map_content']['books_expanded'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Show books expanded'),
        '#default_value' => $config->get('books_expanded'),
        '#description' => $this->t('When enabled, this option will show all children pages for each book.'),
      );
      $site_map_ordering['books'] = t('Books');
    }

    $menu_options = array();
    $menus = Menu::loadMultiple();
    foreach ($menus as $id => $menu) {
      $menu_options[$id] = $menu->label();
      $site_map_ordering['menus_' . $id] = $menu->label();
    }
    $form['site_map_content']['show_menus'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Menus to include in the site map'),
      '#default_value' => $config->get('show_menus'),
      '#options' => $menu_options,
    );
    // Thanks for fix by zhuber at
    // https://drupal.org/node/1331104#comment-5200266.
    $form['site_map_content']['show_menus_hidden'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show disabled menu items'),
      '#default_value' => $config->get('show_menus_hidden'),
      '#description' => $this->t('When enabled, hidden menu links will also be shown.'),
    );

    if ($this->moduleHandler->moduleExists('faq')) {
      $form['site_map_content']['show_faq'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Show FAQ content'),
        '#default_value' => $config->get('show_faq'),
        '#description' => $this->t('When enabled, this option will include the content from the FAQ module in the site map.'),
      );
      $site_map_ordering['faq'] = t('FAQ content');
    }

    if ($this->moduleHandler->moduleExists('taxonomy')) {
      $vocab_options = array();
      foreach (taxonomy_vocabulary_load_multiple() as $vocabulary) {
        $vocab_options[$vocabulary->id()] = $vocabulary->label();
        $site_map_ordering['vocabularies_' . $vocabulary->id()] = $vocabulary->label();
      }
      $form['site_map_content']['show_vocabularies'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Vocabularies to include in the site map'),
        '#default_value' => $config->get('show_vocabularies'),
        '#options' => $vocab_options,
        '#multiple' => TRUE,
      );
    }

    $form['site_map_content']['order'] = array(
      '#type' => 'item',
      '#title' => t('Site map order'),
      '#theme' => 'site_map_order',
    );
    $site_map_order_defaults = $config->get('order');
    foreach ($site_map_ordering as $content_id => $content_title) {
      $form['site_map_content']['order'][$content_id] = array(
        'content' => array(
          '#markup' => $content_title,
        ),
        'weight' => array(
          '#type' => 'weight',
          '#title' => t('Weight for @title', array('@title' => $content_title)),
          '#title_display' => 'invisible',
          '#delta' => 50,
          '#default_value' => isset($site_map_order_defaults[$content_id]) ? $site_map_order_defaults[$content_id] : -50,
          '#parents' => array('order', $content_id),
        ),
        '#weight' => isset($site_map_order_defaults[$content_id]) ? $site_map_order_defaults[$content_id] : -50,
      );
    }

    $form['site_map_taxonomy_options'] = array(
      '#type' => 'details',
      '#title' => $this->t('Taxonomy settings'),
    );
    $form['site_map_taxonomy_options']['show_description'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show vocabulary description'),
      '#default_value' => $config->get('show_description'),
      '#description' => $this->t('When enabled, this option will show the vocabulary description.'),
    );
    $form['site_map_taxonomy_options']['show_count'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show node counts by taxonomy terms'),
      '#default_value' => $config->get('show_count'),
      '#description' => $this->t('When enabled, this option will show the number of nodes in each taxonomy term.'),
    );
    $form['site_map_taxonomy_options']['vocabulary_depth'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Vocabulary depth'),
      '#default_value' => $config->get('vocabulary_depth'),
      '#size' => 3,
      '#maxlength' => 10,
      '#description' => $this->t('Specify how many levels taxonomy terms should be included. Enter "-1" to include all terms, "0" not to include terms at all, or "1" to only include top-level terms.'),
    );
    $form['site_map_taxonomy_options']['term_threshold'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Term count threshold'),
      '#default_value' => $config->get('term_threshold'),
      '#size' => 3,
      '#description' => $this->t('Only show taxonomy terms whose node counts are greater than this threshold. Set to -1 to disable.'),
    );
    if ($this->moduleHandler->moduleExists('forum')) {
      $form['site_map_taxonomy_options']['forum_threshold'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Forum count threshold'),
        '#default_value' => $config->get('forum_threshold'),
        '#size' => 3,
        '#description' => $this->t('Only show forums whose node counts are greater than this threshold. Set to -1 to disable.'),
      );
    }

    $form['site_map_rss_options'] = array(
      '#type' => 'details',
      '#title' => $this->t('RSS settings'),
    );
    $form['site_map_rss_options']['rss_front'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('RSS feed for front page'),
      '#default_value' => $config->get('rss_front'),
      '#description' => $this->t('The RSS feed for the front page, default is rss.xml.'),
    );
    $form['site_map_rss_options']['show_rss_links'] = array(
      '#type' => 'select',
      '#title' => $this->t('Include RSS links'),
      '#default_value' => $config->get('show_rss_links'),
      '#options' => array(
        0 => $this->t('None'),
        1 => $this->t('Include on the right side'),
        2 => $this->t('Include on the left side'),
      ),
      '#description' => $this->t('When enabled, this option will show links to the RSS feeds for the front page and taxonomy terms, if enabled.'),
    );
    $form['site_map_rss_options']['rss_taxonomy'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('RSS depth for vocabularies'),
      '#default_value' => $config->get('rss_taxonomy'),
      '#size' => 3,
      '#maxlength' => 10,
      '#description' => $this->t('Specify how many RSS feed links should be displayed with taxonomy terms. Enter "-1" to include with all terms, "0" not to include with any terms, or "1" to show only for top-level taxonomy terms.'),
    );

    $form['site_map_css_options'] = array(
      '#type' => 'details',
      '#title' => $this->t('CSS settings'),
    );
    $form['site_map_css_options']['css'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Do not include site map CSS file'),
      '#default_value' => $config->get('css'),
      '#description' => $this->t("If you don't want to load the included CSS file you can check this box."),
    );

    // Make use of the Checkall module if it's installed.
    if ($this->moduleHandler->moduleExists('checkall')) {
      $form['site_map_content']['show_books']['#checkall'] = TRUE;
      $form['site_map_content']['show_menus']['#checkall'] = TRUE;
      $form['site_map_content']['show_vocabularies']['#checkall'] = TRUE;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('site_map.settings');

    $keys = array(
      'page_title',
      array('message', 'value'),
      array('message', 'format'),
      'show_front',
      'show_titles',
      'show_menus',
      'show_menus_hidden',
      'show_vocabularies',
      'show_description',
      'show_count',
      'vocabulary_depth',
      'term_threshold',
      'forum_threshold',
      'rss_front',
      'show_rss_links',
      'rss_taxonomy',
      'css',
      'order',
    );

    if ($this->moduleHandler->moduleExists('book')) {
      $keys[] = 'show_books';
      $keys[] = 'books_expanded';
    }

    if ($this->moduleHandler->moduleExists('faq')) {
      $keys[] = 'show_faq';
    }

    // Save config.
    foreach ($keys as $key) {
      if ($form_state->hasValue($key)) {
        $config->set(is_string($key) ? $key : implode('.', $key), $form_state->getValue($key));
      }
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['site_map.settings'];
  }

}
