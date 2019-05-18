<?php

namespace Drupal\group_purl\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\core\Plugin\Context\ContextProviderInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * @ViewsArgumentDefault(
 *  id = "group_purl_context",
 *  title = @Translation("Group ID from Purl"),
 *  short_title = @Translation("A context detected by Purl."),
 * )
 */
class GroupPurlContext extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The group entity from the route.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Constructs a new GroupPurlContext instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The group route context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextProviderInterface $context_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\Core\Plugin\Context\ContextInterface[] $contexts */
    $contexts = $context_provider->getRuntimeContexts(['group']);
    $this->group = $contexts['group']->getContextValue();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('group_purl.context_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    if (!empty($this->group) && $id = $this->group->id()) {
      return $id;
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
    // We cache the result on the route instead of the URL so that path aliases
    // can all use the same cache context. If you look at ::getArgument() you'll
    // see that we actually get the group ID from the route, not the URL.
    return ['purl'];
  }


}
