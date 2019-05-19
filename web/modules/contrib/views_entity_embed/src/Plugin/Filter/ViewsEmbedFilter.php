<?php

namespace Drupal\views_entity_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\entity_embed\Exception\EntityNotFoundException;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\embed\DomHelperTrait;
use Drupal\views\Views;
use Drupal\Component\Serialization\Json;

/**
 * Provides a filter to display embedded entities based on data attributes.
 *
 * @Filter(
 *   id = "views_embed",
 *   title = @Translation("Display embedded views"),
 *   description = @Translation("Embeds views using data attributes: data-view-name, data-view-display, and data-view-attributes."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class ViewsEmbedFilter extends FilterBase implements ContainerFactoryPluginInterface {

  use DomHelperTrait;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a ViewsEmbedFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration, $plugin_id, $plugin_definition, $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    if (strpos($text, 'data-view-name') !== FALSE && (strpos($text, 'data-view-display') !== FALSE)) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      foreach ($xpath->query('//drupal-views[@data-view-name and @data-view-display]') as $node) {
        $render_view = '';
        try {
          $build = $this->buildViewsEmbed($node);
          $render_view = $this->renderer->executeInRenderContext(new RenderContext(), function () use (&$build) {
            return $this->renderer->render($build);
          });
          $result = $result->merge(BubbleableMetadata::createFromRenderArray($build));

        }
        catch (\Exception $e) {
          throw new EntityNotFoundException(sprintf('Unable to load embedded %s view with %s display.', $view_name, $view_display));
        }

        $this->replaceNodeContent($node, $render_view);
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('
        <p>You can embed Views. Additional properties can be added to the embed tag like data-caption and data-align if supported. Example:</p>
        <code><drupal-views data-view-display="default" data-view-name="content"></drupal-views></code>');
    }
    else {
      return $this->t('You can embed Views entities.');
    }
  }

  /**
   * Method that build data attributes per node.
   */
  protected function buildViewsEmbed($node) {
    $view_name = $node->getAttribute('data-view-name');
    $view_display = $node->getAttribute('data-view-display');
    $view_attr = Json::decode($node->getAttribute('data-view-arguments'));
    $view = Views::getView($view_name);
    $view->setDisplay($view_display);
    if ($view_attr['override_title']) {
      $view->setTitle($view_attr['title']);
    }
    if (!empty($view_attr['filters'])) {
      $view->setArguments($view_attr['filters']);
    }

    $build = [
      '#theme_wrappers' => ['views_entity_embed_container'],
      '#attributes' => ['class' => ['views-entity-embed']],
      '#view' => $view,
      '#context' => [
        'data-view-name' => $view_name,
        'data-view-display' => $view_display,
        'data-override-title' => $view_attr['override_title'],
        'data-title' => $view_attr['title'],
      ],

    ];

    return $build;
  }

}
