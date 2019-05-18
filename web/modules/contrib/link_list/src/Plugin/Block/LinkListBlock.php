<?php

namespace Drupal\link_list\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'LinkList' block.
 *
 * @Block(
 *  id = "link_list_block",
 *  admin_label = @Translation("Link List"),
 * )
 */
class LinkListBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Drupal Renderer will be used to render the node to search for links.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Drupal RouteMatch will be used to access the node.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * LinkListBlock constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal Renderer which will be used to render the node before
   *   searching for links in the rendered output.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The Drupal RouteMatch which will be used to access the node.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer, RouteMatchInterface $routeMatch) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->renderer = $renderer;
    $this->routeMatch = $routeMatch;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('current_route_match')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    return [
      'text_before' => '',
      'class_selector' => '',
      'target' => '',
    ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['text_before'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text before'),
      '#description' => $this->t('Add text that should be rendered before the Link List. Leave empty to render no block.'),
      '#default_value' => $this->configuration['text_before'],
      '#weight' => '1',
    ];
    $form['target'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link target'),
      '#description' => $this->t('If set, a target attribute will be added to the links in the Link List (e.g. _blank). Leave empty if you do not want to render the target attribute.'),
      '#default_value' => $this->configuration['target'],
      '#maxlength' => 25,
      '#size' => 25,
      '#weight' => '2',
    ];
    $form['class_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Apply on links with class'),
      '#description' => $this->t('If set, only links with this class will be rendered in Link List block. Leave empty to apply on all links.'),
      '#default_value' => $this->configuration['class_selector'],
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '3',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->configuration['text_before'] = $form_state->getValue('text_before');
    $this->configuration['class_selector'] = $form_state->getValue('class_selector');
    $this->configuration['target'] = $form_state->getValue('target');

  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $links = [];

    $node = $this->routeMatch->getParameter('node');
    if ($node) {
      $nodeView = node_view($node);
      $result = $this->renderer->render($nodeView);

      $dom = new \DOMDocument();
      @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $result);

      $classSelector = $this->configuration['class_selector'];
      /** @var \DOMNodeList $linkElement */
      foreach ($dom->getElementsByTagName('a') as $linkElement) {
        if (empty($classSelector) || $classSelector === $linkElement->getAttribute('class')) {
          $link['value'] = $linkElement->nodeValue;
          foreach ($linkElement->attributes as $attribute) {
            $link[$attribute->nodeName] = $attribute->nodeValue;
          }
          $links[] = $link;
        }

      }
    }

    return [
      '#theme' => 'link_list',
      '#text_before' => $this->configuration['text_before'],
      '#target' => $this->configuration['target'],
      '#links' => $links,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {

    if ($node = $this->routeMatch->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      return parent::getCacheTags();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {

    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);

  }

}
