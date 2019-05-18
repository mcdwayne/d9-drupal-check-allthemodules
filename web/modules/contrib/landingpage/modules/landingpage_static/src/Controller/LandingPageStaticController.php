<?php

namespace Drupal\landingpage_static\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Class LandingPageStaticController.
 *
 * @package Drupal\landingpage_static\Controller
 */
class LandingPageStaticController extends ControllerBase {

  /**
   * Dynamict title of LandingPage Static Form.
   */
  public function title(NodeInterface $node) {
    return $this->t('Export "@title" LandingPage in static HTML', array('@title' => $node->label()));
  }

  /**
   * Access function of LandingPage Static Form.
   */
  public function access(NodeInterface $node, AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('create landingpage content') && $node->getType() === 'landingpage');
  }

}
