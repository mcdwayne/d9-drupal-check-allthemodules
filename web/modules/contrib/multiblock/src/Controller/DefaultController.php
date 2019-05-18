<?php 

namespace Drupal\multiblock\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the multiblock module.
 */
class DefaultController extends ControllerBase {

  public function multiblock_general() {
    if (func_num_args() && func_get_arg(0) == 'edit' && is_numeric($instance = func_get_arg(1))) {
      $req_block = multiblock_get_block($instance);
    }
    // Fetch blocks directly from modules using block.module function.
    $blocks = _block_rehash();
    // Sort blocks how we want them.
    usort($blocks, 'multiblock_block_sort');

    // Fetch "Add Instance" form.
    if (isset($req_block)) {
      $get_form = \Drupal::formBuilder()->getForm('multiblock_add_form', $blocks, $req_block);
      $form = drupal_render($get_form);
    }
    else {
      $get_form = \Drupal::formBuilder()->getForm('multiblock_add_form', $blocks);
      $form = drupal_render($get_form);
    }

    // Get an array of existing blocks.
    $multiblocks = multiblock_get_block(NULL, TRUE);
    return _theme('multiblock_general', [
      'add_block_form' => $form,
      'multiblocks' => $multiblocks,
      'edit' => isset($req_block),
    ]);
  }

}
