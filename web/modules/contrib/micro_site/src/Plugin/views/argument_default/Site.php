<?php

namespace Drupal\micro_site\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;

/**
 * Default argument plugin to extract a user from request.
 *
 * @ViewsArgumentDefault(
 *   id = "site",
 *   title = @Translation("Site ID from route context")
 * )
 */
class Site extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a new User instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, SiteNegotiatorInterface $site_negotiator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->negotiator = $site_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('micro_site.negotiator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['site'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['site'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Also look for a node and use the node site id'),
      '#default_value' => $this->options['site'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {

    // If there is a user object in the current route.
    if ($site = $this->routeMatch->getParameter('site')) {
      if ($site instanceof SiteInterface) {
        return $site->id();
      }
    }

    // If option to use node site id; and node in current route.
    if (!empty($this->options['site']) && $node = $this->routeMatch->getParameter('node')) {
      if ($node instanceof NodeInterface) {
        if ($node->hasField('site_id')) {
          $site = $node->get('site_id')->referencedEntities();
          $site = current($site);
          if ($site instanceof SiteInterface) {
            return $site->id();
          }

          return $node->site_id->value;
        }
      }
    }
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
    return ['url.site'];
  }

}
