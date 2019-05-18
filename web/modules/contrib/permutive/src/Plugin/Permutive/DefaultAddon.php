<?php

namespace Drupal\permutive\Plugin\Permutive;

use Drupal\node\NodeInterface;
use Drupal\permutive\Plugin\PermutiveBase;
use Drupal\permutive\Plugin\PermutiveDataInterface;

/**
 * The default web addon.
 *
 * @Permutive(
 *   label = "Default addon",
 *   id = "default_addon",
 *   priority = 0,
 *   type = "addon",
 *   clientType = "web",
 * )
 */
class DefaultAddon extends PermutiveBase {

  /**
   * {@inheritdoc}
   */
  public function alterData(PermutiveDataInterface $data) {
    $route_match = \Drupal::routeMatch();
    $token = \Drupal::token();
    $name = $token->replace(
      '[site:name]',
      [],
      ['clear' => TRUE]
    );
    $data->set('page.publisher.name', $name);

    // Node values.
    $node = $route_match->getParameter('node');
    if ($node instanceof NodeInterface) {
      $data->set('page.content.headline', $token->replace(
        '[node:title]',
        ['node' => $node],
        ['clear' => TRUE]
      ));
      $data->set('page.content.description', $token->replace(
        '[node:summary]',
        ['node' => $node],
        ['clear' => TRUE]
      ));
      $data->set('page.publisher.type', $node->getType());
    }
  }

}
