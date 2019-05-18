<?php

namespace Drupal\menu_link_content_visibility;


use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Condition\ConditionInterface;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Plugin\Context\ContextHandler;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MenuLinkContentVisibilityChecker {

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

  private function __construct($menu_link_content, ConditionManager $condition_manager, ContextRepositoryInterface $context_repository, ContextHandler $context_handler) {
    $this->menu_link_content = $menu_link_content;
    $this->condition_manager = $condition_manager;
    $this->context_repository = $context_repository;
    $this->context_handler = $context_handler;
  }

  public function isVisible() {
    if ($visibility = unserialize($this->menu_link_content->get('visibility')->value)) {
      foreach ($visibility as $condition_id => $condition_configuration) {
        if (!$this->evaluateCondition($condition_id, $condition_configuration)) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  private function evaluateCondition($condition_id, $condition_configuration) {
    /** @var ConditionInterface $condition */
    $condition = $this->condition_manager->createInstance($condition_id, $condition_configuration);
    if ($condition instanceof ContextAwarePluginInterface) {
      $contexts = $this->context_repository->getRuntimeContexts(array_values($condition->getContextMapping()));
      try {
        $this->context_handler->applyContextMapping($condition, $contexts);
      } catch (ContextException $e) {
        return TRUE;
      }
    }

    return $condition->evaluate();
  }

}
