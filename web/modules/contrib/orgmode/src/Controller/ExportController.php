<?php
/**
 * @file
 * Contains \Drupal\orgmode\Controller\ExportController.
 */

namespace Drupal\orgmode\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\orgmode\Utils\ParserPHPOrg;
use Drupal\node\NodeInterface;
use Drupal;

/**
 * Class ExportController.
 *
 * @package Drupal\orgmode\Controller
 */
class ExportController extends ControllerBase {

  /**
   * Export2org.
   *
   * @param Drupal\node\NodeInterface $node
   *   A node entity.
   */
  public function export2org(NodeInterface $node) {
    $filename = 'node_' . $node->id() . '.org';
    header('Content-type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $parser = new ParserPHPOrg();
    print ($parser->nodeToOrg($node));

    Drupal::moduleHandler()->invokeAll('exit');
    exit();
  }

}
