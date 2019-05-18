<?php

namespace Drupal\block_cache_metadata;

use Drupal\block\Entity\Block;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BlockCacheMetaDataHelper.
 */
class BlockCacheMetaDataHelper {

  /**
   * Sets Thirdpartysettings.
   *
   * Updates the block if needed.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block Entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param string $module
   *   String block_cache_metadata.
   */
  public static function updateBlock(Block $block, FormStateInterface $form_state, $module) {
    $block_update = FALSE;
    if (isset($form_state->getUserInput()['settings']['block_cache_metadata_details']['cache_tags'])) {
      $new_cache_tags = trim(str_replace("\r\n", ',', $form_state->getUserInput()['settings']['block_cache_metadata_details']['cache_tags']));
      $block->setThirdPartySetting('block_cache_metadata', 'cache_tags', explode(",", $new_cache_tags));
      $block_update = TRUE;
    }
    if (isset($form_state->getUserInput()['settings']['block_cache_metadata_details']['cache_max_age'])) {
      $new_cache_max_age = $form_state->getUserInput()['settings']['block_cache_metadata_details']['cache_max_age'];
      $block->setThirdPartySetting('block_cache_metadata', 'cache_max_age', $new_cache_max_age);
      $block_update = TRUE;
    }
    if (isset($form_state->getUserInput()['settings']['block_cache_metadata_details']['cache_contexts'])) {
      $new_cache_contexts = trim(str_replace("\r\n", ',', $form_state->getUserInput()['settings']['block_cache_metadata_details']['cache_contexts']));
      $block->setThirdPartySetting('block_cache_metadata', 'cache_contexts', explode(",", $new_cache_contexts));
      $block_update = TRUE;
    }
    if ($block_update) {
      $block->save();
    }
  }

}
