<?php

namespace Drupal\multiversion\Workspace;

use Drupal\Core\Session\AccountInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class WorkspaceNegotiatorBase implements WorkspaceNegotiatorInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * {@inheritdoc}
   */
  public function setCurrentUser(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkspaceManager(WorkspaceManagerInterface $entity_manager) {
    $this->workspaceManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function persist(WorkspaceInterface $workspace) {
    return TRUE;
  }

}
