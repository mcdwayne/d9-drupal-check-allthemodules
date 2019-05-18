<?php

namespace Drupal\shownid\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a Shownid block
 *
 * @Block(
 *   id = "shownid_block",
 *   admin_label = @Translation("Shownid block"),
 * )
 */

class ShownidBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    return  array(
      '#markup' => self::getIdString(),
    );  
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    if ($account->hasPermission("access content")) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

  /**
   * Returns a formatted label with entity id.
   * @return String Nid or tid with label. False if none.
   */
  public static function getIdString() {
    $nidortid = self::getNidOrTid();
    switch ($nidortid['type']) {
      case 'node':
        return t("Nid") . ': ' . $nidortid['id'];
      case 'taxonomy_term':
        return t("Tid") . ': ' . $nidortid['id'];
      default:
        return FALSE;
      
    }
  }

  /**
   * Extracts the unique id for the active entity such as node or term.
   *
   * @return Array An array containing type and id keys.
   */
  public static function getNidOrTid() {
    $node = \Drupal::routeMatch()->getParameter('node');
    $type = 'none';
    if (!empty($node)) {
      $nidortid = $node->id();
      $type = 'node';
    }
    else {
      $term = \Drupal::routeMatch()->getParameter('taxonomy_term');
      if (!empty($term)) {
        $nidortid = $term->id();
        $type = 'taxonomy_term';
      }
    }
    if (empty($nidortid)) {
      $nidortid = 'n/a';
    }
    return array('type' => $type, 'id' => $nidortid);
  }
}
