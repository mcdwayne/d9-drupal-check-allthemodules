<?php

namespace Drupal\onepass\Plugin\views\pager;

use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\views\Plugin\views\pager\PagerPluginBase;

/**
 * The plugin to handle full pager.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "onepasspager",
 *   title = @Translation("OnePass Atoms pagination"),
 *   short_title = @Translation("OnePass Atoms"),
 *   help = @Translation("Paged output of OnePass Atoms feed."),
 *   register_theme = FALSE
 * )
 */
class OnepassAtomsPager extends PagerPluginBase implements CacheableDependencyInterface {

  /**
   * Provide options with default values.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['items_per_page'] = array('default' => 10);
    $options['offset'] = array('default' => 0);
    $options['get_param'] = array('default' => 'page');
    return $options;
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $pager_text = $this->displayHandler->getPagerText();
    $form['items_per_page'] = array(
      '#title' => $pager_text['items per page title'],
      '#required' => TRUE,
      '#type' => 'number',
      '#description' => $pager_text['items per page description'],
      '#default_value' => $this->options['items_per_page'],
    );

    $form['offset'] = array(
      '#type' => 'number',
      '#title' => $this->t('Offset (number of items to skip)'),
      '#required' => TRUE,
      '#description' => $this->t('For example, set this to 3 and the first 3 items will not be displayed.'),
      '#default_value' => $this->options['offset'],
    );

    $form['get_param'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GET param'),
      '#required' => TRUE,
      '#description' => $this->t('Page number param name from URL.'),
      '#default_value' => $this->options['get_param'],
    );
  }

  /**
   * Modify query with pagination params.
   */
  public function query() {

    $limit = $this->options['items_per_page'];
    $offset = $this->getCurrentPage() * $limit + $this->options['offset'];

    $this->view->query->setLimit($limit);
    $this->view->query->setOffset($offset);
  }

  /**
   * Return current page number.
   */
  public function getCurrentPage() {
    $current_page = 0;

    if (isset($_GET[$this->options['get_param']])) {
      $current_page = abs(intval($_GET[$this->options['get_param']]));
    }

    return $current_page;
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    if (!empty($this->options['offset'])) {
      return $this->formatPlural(
        $this->options['items_per_page'],
        '@count item, skip @skip, from $_GET["@param"]',
        'Paged, @count items, skip @skip, from $_GET["@param"]',
        array(
          '@count' => $this->options['items_per_page'],
          '@skip' => $this->options['offset'],
          '@param' => $this->options['get_param'],
        )
      );
    }
    return $this->formatPlural(
      $this->options['items_per_page'],
      '@count item, from $_GET["@param"]',
      'Paged, @count items, from $_GET["@param"]',
      array(
        '@count' => $this->options['items_per_page'],
        '@param' => $this->options['get_param'],
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($input) {

    $pager = array();
    $current_page = $this->getCurrentPage();
    $total_pages = ceil($this->total_items / $this->options['items_per_page']);

    // Create "Self" (Current) page URL.
    $pager['self'] = Url::fromRoute(
      '<current>',
      ($current_page > 0 ? array('page' => $current_page) : array()),
      array('absolute' => TRUE)
    )->toString();

    // Create "First" page URL.
    $pager['first'] = Url::fromRoute(
      '<current>',
      array(),
      array('absolute' => TRUE)
    )->toString();

    // Create "Next" page URL.
    if ($current_page < $total_pages - 1) {
      $pager['next'] = Url::fromRoute(
        '<current>',
        array('page' => $current_page + 1),
        array('absolute' => TRUE)
      )->toString();
    }

    // Create "Previous" page URL.
    if ($current_page > 1) {
      $pager['previous'] = Url::fromRoute(
        '<current>',
        array('page' => $current_page - 1),
        array('absolute' => TRUE)
      )->toString();
    }

    // Create "Last" page URL.
    if ($total_pages == 1) {
      $pager['last'] = $pager['first'];
    }
    elseif ($total_pages > 1) {
      $pager['last'] = Url::fromRoute(
        '<current>',
        array('page' => $total_pages - 1),
        array('absolute' => TRUE)
      )->toString();
    }

    return array(
      '#theme' => 'onepassatoms_pager',
      '#pager' => $pager,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url.query_args'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
