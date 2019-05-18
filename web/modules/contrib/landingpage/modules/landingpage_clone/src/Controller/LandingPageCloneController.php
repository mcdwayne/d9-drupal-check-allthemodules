<?php

namespace Drupal\landingpage_clone\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Class LandingPageClonesController.
 *
 * @package Drupal\landingpage_clone\Controller
 */
class LandingPageCloneController extends ControllerBase {

  /**
   * Dynamict title of LandingPage Clone Form.
   */
  public function title(NodeInterface $node) {
    return $this->t('Do you want to clone "@title" LandingPage?', array('@title' => $node->label()));
  }

  /**
   * Access function of LandingPage Clone Form.
   */
  public function access(NodeInterface $node, AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('create landingpage content') && $node->getType() === 'landingpage');
  }

}
