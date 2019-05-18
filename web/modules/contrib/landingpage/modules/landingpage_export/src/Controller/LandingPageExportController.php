<?php

namespace Drupal\landingpage_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Class LandingPageExportController.
 *
 * @package Drupal\landingpage_export\Controller
 */
class LandingPageExportController extends ControllerBase {

  /**
   * Dynamict title of LandingPage Clone Form.
   */
  public function title(NodeInterface $node) {
    return $this->t('Export "@title" LandingPage', array('@title' => $node->label()));
  }

  /**
   * Access function of LandingPage Clone Form.
   */
  public function access(NodeInterface $node, AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('create landingpage content') && $node->getType() === 'landingpage');
  }

}
