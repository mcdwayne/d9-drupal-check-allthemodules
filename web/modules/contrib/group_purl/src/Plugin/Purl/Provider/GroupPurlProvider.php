<?php

namespace Drupal\group_purl\Plugin\Purl\Provider;

use Drupal\purl\Plugin\Purl\Provider\ProviderAbstract;
use Drupal\purl\Plugin\Purl\Provider\ProviderInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @PurlProvider(
 *   id = "group_purl_provider",
 *   title = @Translation("A provider pair for Group module.")
 * )
 */
class GroupPurlProvider extends ProviderAbstract implements ProviderInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  protected $storage;

  /**
   * @inheritDoc
   */
  public function getModifierData() {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('group');
    /** @var \Drupal\Core\Entity\Query\Sql\Query $query */
    $query = $storage->getQuery();
    $gids = $query->execute();

    /** @var \Drupal\Core\Path\AliasManager $alias_manager */
    $alias_manager = $this->container->get('path.alias_manager');

    $modifiers = [];

    foreach ($gids as $gid) {
      $path = $alias_manager->getAliasByPath('/group/' . $gid);
      $path = substr($path, 1);
      $modifiers[$path] = $gid;

    };

    return $modifiers;
  }

}
