<?php

namespace Drupal\multiversion\Workspace;

use Symfony\Component\HttpFoundation\Request;

class DefaultWorkspaceNegotiator extends WorkspaceNegotiatorBase {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkspaceId(Request $request) {
    return $this->container->getParameter('workspace.default');
  }

}
