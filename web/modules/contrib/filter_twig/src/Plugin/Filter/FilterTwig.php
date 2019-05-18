<?php

namespace Drupal\filter_twig\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter that replaces twig values.
 *
 * @Filter(
 *   id = "filter_twig",
 *   title = @Translation("Replaces Twig values"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = { }
 * )
 */
class FilterTwig extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a filter twig plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *   The token service.
   *   The token entity mapper service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
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
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $build = [
      '#type' => 'inline_template',
      '#template' => $text,
    ];

    return new FilterProcessResult($this->renderer->render($build));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $build = array(
      '#markup' => $this->t('Allowed Twig syntax. <a href="@doc" target="_blank">See documentation how to use it</a>', [
        '@doc' => 'https://www.drupal.org/node/1918824',
      ]),
    );

    return $this->renderer->render($build);
  }

}
