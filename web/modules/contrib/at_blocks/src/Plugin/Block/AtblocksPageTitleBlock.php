<?php

/**
 * @file
 * Contains \Drupal\at_blocks\Plugin\Block\AtblocksPageTitleBlock.
 */

namespace Drupal\at_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

//use Drupal\views\Plugin\views\display\PathPluginBase;

/**
 * Provides a block to display the page title.
 *
 * @Block(
 *   id = "at_blocks_page_title_block",
 *   admin_label = @Translation("Page title")
 * )
 */
class AtblocksPageTitleBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * A request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Creates a AtblocksPageTitleBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, TitleResolverInterface $title_resolver, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->titleResolver = $title_resolver;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('title_resolver'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'label_display' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = array();
    $title = '';

    $request = $this->requestStack->getCurrentRequest();
    if ($route = $request->attributes->get(routeObjectInterface::ROUTE_OBJECT)) {
      $title = $this->titleResolver->getTitle($request, $route);
    }

    if (empty($title)) {
      if (is_callable('views_get_page_view')) {
        if ($view = views_get_page_view()) {
          $title = $view->getTitle();
        }
      }
    }

    if (empty($title)) {
      return FALSE;
    }

    $build['title'] = array(
      '#markup' => $title,
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // The 'Page title' block is never cacheable, because it may be dynamic.
    $form['cache']['#disabled'] = TRUE;
    $form['cache']['#description'] = t('This block is never cacheable, it is not configurable.');
    $form['cache']['max_age']['#value'] = 0;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    // The 'Page title' block is never cacheable, because it may be dynamic.
    return FALSE;
  }

}
