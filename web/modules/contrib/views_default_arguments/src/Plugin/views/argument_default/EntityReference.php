<?php

/**
 * @file
 * Contains \Drupal\views_default_arguments\Plugin\views\argument_default\EntityReference.
 */

namespace Drupal\views_default_arguments\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to extract entity reference content id from context node.
 *
 * @ViewsArgumentDefault(
 *   id = "entityreference",
 *   title = @Translation("Content ID from Node Entity Reference")
 * )
 */
class EntityReference extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new Node instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['argument'] = array('default' => '');
    $options['empty'] = array('default' => false);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['argument'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Entity Reference'),
      '#default_value' => $this->options['argument'],
    );
    $form['hideviewifempty'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Hide view if Entity Reference does not available'),
      '#default_value' => boolval($this->options['hideviewifempty']),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    if (($node = $this->routeMatch->getParameter('node')) && $node instanceof NodeInterface) {
      $ret = array();
      if($node->hasField($this->options['argument'])) {
        foreach($node->get($this->options['argument'])->getValue() as $item) {
          $ret[] = $item['target_id'];
        }
      } 
      if (count($ret) > 0) {

        return implode('+', $ret);
      }
    }

    if ($this->options['hideviewifempty']) {
      $this->view->build_info['fail'] = TRUE;
    }

      return null;
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
    return ['url'];
  }

}
