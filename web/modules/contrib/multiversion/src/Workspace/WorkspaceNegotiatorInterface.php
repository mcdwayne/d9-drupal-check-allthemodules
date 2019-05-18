<?php

namespace Drupal\multiversion\Workspace;

use Drupal\Core\Session\AccountInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Symfony\Component\HttpFoundation\Request;

interface WorkspaceNegotiatorInterface {

  /**
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
  public function setCurrentUser(AccountInterface $current_user);

  /**
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $entity_manager
   */
  public function setWorkspaceManager(WorkspaceManagerInterface $entity_manager);

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return boolean
   */
  public function applies(Request $request);

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return string
   */
  public function getWorkspaceId(Request $request);

  /**
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @return boolean
   */
  public function persist(WorkspaceInterface $workspace);

}
