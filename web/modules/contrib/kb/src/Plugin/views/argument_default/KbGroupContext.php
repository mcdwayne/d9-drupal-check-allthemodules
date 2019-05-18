<?php
/**
 * Created by PhpStorm.
 * User: laboratory.mike
 */

namespace Drupal\kb\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\group\Entity\GroupContent;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to extract a group ID.
 *
 * @ViewsArgumentDefault(
 *   id = "kb_group_context",
 *   title = @Translation("KB Group Context")
 * )
 */
class KbGroupContext extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The group entity from the route.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Constructs a new GroupIdFromUrl instance.
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
      $container->get('group.group_route_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    if (!empty($this->group) && $id = $this->group->id()) {
      return $id;
    }
    else {
      $current_path = \Drupal::service('path.current')->getPath();
      $url_object = \Drupal::service('path.validator')->getUrlIfValid($current_path);
      $route_parameters = $url_object->getrouteParameters();
      if (isset($route_parameters['node'])) {
        $nid = $route_parameters['node'];
        $node = Node::load($nid);
        $gc = GroupContent::loadByEntity($node);
        // Currently, an array of group content is returned. We convert this to a string
        $gckeys = array_keys($gc);
        $gids = [];
        foreach ($gckeys as $gckey) {
          $grp = isset($gc[$gckey]) ? $gc[$gckey]->getGroup(): FALSE;
          $grp ? $gids[] = $grp->id() : NULL;
        }
        $gid = implode('+', $gids);
        return $gid ? $gid : FALSE;
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
    // We cache the result on the route instead of the URL so that path aliases
    // can all use the same cache context. If you look at ::getArgument() you'll
    // see that we actually get the group ID from the route, not the URL.
    return ['route'];
  }

}
