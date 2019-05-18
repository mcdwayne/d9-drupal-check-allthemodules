<?php

namespace Drupal\bud\Controller;

use Drupal\block\BlockInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;

/**
 * Bud Controller.
 */
class BudController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function move(BlockInterface $block = NULL, $direction = NULL) {
    global $theme;

    $theme = $this->config('system.theme')->get('default');
    $destination = drupal_get_destination();
    $redirect = '/' . $destination['destination'];

    $themes = \Drupal::service('theme_handler')->listInfo();

    $region = $block->get('region');

    $entities = _block_rehash($theme);
    // Sort the blocks using \Drupal\block\Entity\Block::sort().
    uasort($entities, array('Drupal\block\Entity\Block', 'sort'));

    $blo = array();
    // Build blocks first for each region.
    foreach ($entities as $entity_id => $entity) {
      if ($entity->get('region') == $region) {
        $definition = $entity->getPlugin()->getPluginDefinition();
        $blocks[$entity_id] = array(
          'label' => $entity->label(),
          'entity_id' => $entity_id,
          'weight' => $entity->get('weight'),
          'entity' => $entity,
          'category' => $definition['category'],
        );

        $blo[$entity_id] = array(
          'weight' => $entity->get('weight'),
        );
      }
    }

    $i = 0;
    $inc = count($blocks);

    // Reorder them properly using weight.
    foreach ($blocks as $idx => $blockObject) {
      /*
       * TODO: Fix this and see why it doesn't work in D8.
       * if (!$blockObject->access()) {
       *   unset($blocks[$idx]);
       *   continue;
       * }
       */
      $i += $inc;
      $blockObject['entity']->set('weight', $i);
      $blockObject['entity']->save();
    }

    $index1 = array_search($block->id, array_keys($blocks));
    $blocks = array_values($blocks);

    if ($direction == 'up') {
      $index2 = $index1 - 1;
    }
    else {
      $index2 = $index1 + 1;
    }

    if ($index2 < 0 || $index2 >= count($blocks)) {
      return new RedirectResponse($redirect);
    }

    $block1 = $blocks[$index1];
    $block2 = $blocks[$index2];

    $temp = $block1['entity']->get('weight');
    $block1['entity']->set('weight', $block2['entity']->get('weight'));
    $block2['entity']->set('weight', $temp);
    $block1['entity']->save();
    $block2['entity']->save();

    return new RedirectResponse($redirect);
  }

  /**
   * {@inheritdoc}
   */
  public function disable(BlockInterface $block) {
    $block->disable()->save();
    $destination = drupal_get_destination();
    $redirect = '/' . $destination['destination'];
    return new RedirectResponse($redirect);
  }

}
