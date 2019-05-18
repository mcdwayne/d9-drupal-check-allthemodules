<?php

namespace Drupal\node_clone\Controller;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Controller\FormController;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Access\NodeAddAccessCheck;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;

/**
 * Wrapping controller for entity forms that serve as the main page body.
 *
 * This class is very similar to \Drupal\Core\Entity\HtmlEntityFormController
 */
class NodeCloneFormController extends FormController {

  use StringTranslationTrait;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\node\Access\NodeAddAccessCheck
   */
  protected $nodeAddAccessCheck;

  /**
   * Constructs a new \Drupal\Core\Routing\Enhancer\FormEnhancer object.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $resolver
   *   The controller resolver.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   * @param \Drupal\node\Access\NodeAddAccessCheck $node_add_access
   *   Node add access checker.
   */
  public function __construct(ControllerResolverInterface $resolver, FormBuilderInterface $form_builder, EntityManagerInterface $manager, NodeAddAccessCheck $node_add_access) {
    parent::__construct($resolver, $form_builder);
    $this->entityManager = $manager;
    $this->nodeAddAccessCheck = $node_add_access;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormArgument(RouteMatchInterface $route_match) {
    return 'node';
  }

  /**
   * {@inheritdoc}
   *
   * Instead of a class name or service ID, $form_arg will be a string
   * representing the entity and operation being performed.
   * Consider the following route:
   * @code
   *   path: '/foo/{node}/bar'
   *   defaults:
   *     _entity_form: 'node.edit'
   * @endcode
   * This means that the edit form for the node entity will used.
   * If the entity type has a default form, only the name of the
   * entity {param} needs to be passed:
   * @code
   *   path: '/foo/{node}/baz'
   *   defaults:
   *     _entity_form: 'node'
   * @endcode
   */
  protected function getFormObject(RouteMatchInterface $route_match, $form_arg) {
    // If no operation is provided, use 'default'.
    $form_arg .= '.default';
    list ($entity_type_id, $operation) = explode('.', $form_arg);

    $form_object = $this->entityManager->getFormObject($entity_type_id, $operation);

    // Allow the entity form to determine the entity object from a given route
    // match.
    $entity = $form_object->getEntityFromRouteMatch($route_match, $entity_type_id);
    // Clone the entity using the awesome createDuplicate() core function
    $new_entity = $entity->createDuplicate();
    $new_entity->setTitle($this->t('Clone of ') . $new_entity->getTitle());
    $form_object->setEntity($new_entity);

    return $form_object;
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\node\NodeInterface
   * @return bool
   */
  public function access(AccountInterface $account, NodeInterface $node) {
    if ($account->hasPermission('clone node') || ($node->uid->value === $account->id() && $account->hasPermission('clone own nodes'))) {
      $access = new AccessResultAllowed();
    }
    else {
      $access = new AccessResultForbidden();
    }
    $access->addCacheableDependency($node);
    $access->cachePerPermissions();
    if ($access->isAllowed()) {
      $access = $access->andIf($node->access('view', $account, TRUE));
    }
    if ($access->isAllowed()) {
      $node_type = NodeType::load($node->getType());
      $access = $access->andIf($this->nodeAddAccessCheck->access($account, $node_type));
    }
    return $access;
  }
}

