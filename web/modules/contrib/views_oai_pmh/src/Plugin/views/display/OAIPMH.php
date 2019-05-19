<?php

namespace Drupal\views_oai_pmh\Plugin\views\display;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\State\StateInterface;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\PathPluginBase;
use Drupal\views_oai_pmh\Service\Repository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "views_oai_pmh_display",
 *   title = @Translation("OAI-PMH Views"),
 *   help = @Translation("Provide a feed using the OAI-PMH protocol."),
 *   uses_route = TRUE,
 *   admin = @Translation("OAI-PMH Views"),
 *   returns_response = TRUE
 * )
 */
class OAIPMH extends PathPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesAJAX = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesMore = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesAreas = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesPager = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesAttachments = FALSE;

  protected $metadataPrefix = 'oai_dc';

  /**
   *
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider, StateInterface $state, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider, $state);

    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider'),
      $container->get('state'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'oai_pmh';
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Set the default style plugin to 'json'.
    $options['style']['contains']['type']['default'] = 'views_oai_pmh_record';
    $options['defaults']['default']['style'] = FALSE;
    $options['defaults']['default']['row'] = FALSE;

    // Remove css/exposed form settings, as they are not used for the data display.
    unset($options['exposed_form']);
    unset($options['exposed_block']);
    unset($options['css_class']);

    return $options;
  }

  /**
   *
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);
    $categories['page']['title'] = $this->t('OAI-PMH settings');
    $categories['title']['title'] = $this->t('Repository name');
    $options['title']['title'] = $this->t('Repository name');
  }

  /**
   *
   */
  public static function buildResponse($view_id, $display_id, array $args = []) {
    $build = static::buildBasicRenderable($view_id, $display_id, $args);

    // Setup an empty response so headers can be added as needed during views
    // rendering and processing.
    $response = new CacheableResponse('', 200);
    $build['#response'] = $response;

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $output = (string) $renderer->renderRoot($build);

    $response->setContent($output);
    $cache_metadata = CacheableMetadata::createFromRenderArray($build);
    $response->addCacheableDependency($cache_metadata);
    $response->headers->set('Content-Type', 'application/xml;charset=UTF-8');

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];

    $build['#markup'] = $this->renderer->executeInRenderContext(new RenderContext(), function () {
      return $this->view->style_plugin->render();
    });

    $this->view->element['#cache_properties'][] = '#content_type';

    // Encode and wrap the output in a pre tag if this is for a live preview.
    if (!empty($this->view->live_preview)) {
      $build['#prefix'] = '<pre>';
      $build['#plain_text'] = $build['#markup'];
      $build['#suffix'] = '</pre>';
      unset($build['#markup']);
    }
    else {
      $build['#markup'] = ViewsRenderPipelineMarkup::create($build['#markup']);
    }

    parent::applyDisplayCacheabilityMetadata($build);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $this->view->setCurrentPage($this->pageByResumptionToken());

    parent::execute();

    return $this->view->render();
  }

  /**
   * {@inheritdoc}
   *
   * The DisplayPluginBase preview method assumes we will be returning a render
   * array. The data plugin will already return the serialized string.
   */
  public function preview() {
    return $this->view->render();
  }

  /**
   *
   */
  public function initDisplay(ViewExecutable $view, array &$display, array &$options = NULL) {
    parent::initDisplay($view, $display, $options);

    if (!$prefix = $this->getMetadataPrefixByToken()) {
      $prefix = 'oai_dc';
    }

    $this->metadataPrefix = $this->view
      ->getRequest()
      ->query
      ->get('metadataPrefix', $prefix);
  }

  /**
   *
   */
  public function getCurrentMetadataPrefix() {
    return $this->metadataPrefix;
  }

  private function pageByResumptionToken() {
    $token = $this->view->getRequest()
      ->query
      ->get('resumptionToken', NULL);

    if ($token) {
      /** @var Repository $repository */
      $repository = \Drupal::service('views_oai_pmh.repository');
      $paginator = $repository->decodeResumptionToken($token);

      return $paginator['offset'];
    }

    return 0;
  }

  private function getMetadataPrefixByToken() {
    $token = $this->view->getRequest()
      ->query
      ->get('resumptionToken', NULL);

    if ($token) {
      /** @var Repository $repository */
      $repository = \Drupal::service('views_oai_pmh.repository');
      $paginator = $repository->decodeResumptionToken($token);

      return $paginator['metadataPrefix'];
    }

    return NULL;
  }
}
