<?php

/**
 * @file
 * Contains \Drupal\Tests\retriever\Fixtures\ClassWithSuggestedDependencies
 */

namespace Drupal\Tests\retriever\Fixtures;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Form\NodeDeleteForm;
use Drupal\user\UserAccessControlHandler;

/**
 * Defines a class with suggested dependencies.
 */
class ClassWithSuggestedDependencies {

  /**
   * Constructs a new instance.
   *
   * @suggestedDependency drupalContainerParameter:filter_protocols $filter_protocols
   * @suggestedDependency drupalContainerService:entity_type.manager $entity_type_manager
   * @suggestedDependency drupalEntityTypeHandler:user.access $user_access_control_handler
   * @suggestedDependency drupalEntityTypeHandler:node.form.delete $node_delete_form
   *
   * @param string[] $filter_protocols
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\user\UserAccessControlHandler $user_access_control_handler
   *   Require this specific class to make sure we don't get any other entity
   *   access control handler.
   * @param \Drupal\node\Form\NodeDeleteForm $node_delete_form
   *   Require this specific class to make sure we don't get any other entity
   *   form.
   */
  public function __construct(array $filter_protocols, EntityTypeManagerInterface $entity_type_manager, UserAccessControlHandler $user_access_control_handler, NodeDeleteForm $node_delete_form) {
  }

}
