<?php

namespace Drupal\menu_link_content_visibility;


use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Condition\ConditionInterface;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Plugin\Context\ContextHandler;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MenuLinkContentVisibilityCache {

  public static function createFromID(ContainerInterface $container, $id) {
    $entity_manager = $container->get('entity.manager');
    $menu_link_content = $entity_manager->getStorage('menu_link_content')->load($id);

    return new self(
      $menu_link_content,
      $container->get('plugin.manager.condition'),
      $container->get('context.repository'),
      $container->get('context.handler')
    );
  }

  /** @var  ConditionManager */
  private $condition_manager;

  /** @var  ContextRepositoryInterface */
  private $context_repository;

  /** @var  ContextHandler */
  private $context_handler;

  /** @var  MenuLinkContent */
  private $menu_link_content;

  /** @var  ConditionInterface[] */
  private $conditions;

  private function __construct($menu_link_content, ConditionManager $condition_manager, ContextRepositoryInterface $context_repository, ContextHandler $context_handler) {
    $this->menu_link_content = $menu_link_content;
    $this->condition_manager = $condition_manager;
    $this->context_repository = $context_repository;
    $this->context_handler = $context_handler;

    $this->conditions = $this->buildConditions();
  }

  public function getCacheContexts() {
    $cache_contexts = [];
    foreach($this->conditions as $condition) {
      $cache_contexts = Cache::mergeContexts($cache_contexts, $condition->getCacheContexts());
    }
    return $cache_contexts;
  }

  public function getCacheTags() {
    $cache_tags = [];
    foreach($this->conditions as $condition) {
      $cache_tags = Cache::mergeTags($cache_tags, $condition->getCacheTags());
    }
    return $cache_tags;
  }

  public function getCacheMaxAge() {
    $cache_max_age = Cache::PERMANENT;
    foreach($this->conditions as $condition) {
      $cache_max_age = Cache::mergeMaxAges($cache_max_age, $condition->getCacheMaxAge());
    }
    return $cache_max_age;
  }

  private function buildConditions() {
    $conditions = [];
    if ($visibility = unserialize($this->menu_link_content->get('visibility')->value)) {
      foreach ($visibility as $condition_id => $condition_configuration) {
        /** @var ConditionInterface $condition */
        $condition = $this->condition_manager->createInstance($condition_id, $condition_configuration);
        if ($condition instanceof ContextAwarePluginInterface) {
          $contexts = $this->context_repository->getRuntimeContexts(array_values($condition->getContextMapping()));
          try {
            $this->context_handler->applyContextMapping($condition, $contexts);
          }
          catch (ContextException $e) {

          }
        }

        $conditions[$condition_id] = $condition;
      }
    }
    return $conditions;
  }

}
