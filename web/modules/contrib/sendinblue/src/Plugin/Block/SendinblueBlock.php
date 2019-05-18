<?php

namespace Drupal\sendinblue\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\sendinblue\Form\SubscribeForm;

/**
 * Display all instances for 'YourBlock' block plugin.
 *
 * @Block(
 *   id = "sendinblue_block",
 *   admin_label = @Translation("Sendinblue block"),
 *   deriver = "Drupal\sendinblue\Plugin\Derivative\SendinblueBlock"
 * )
 */
class SendinblueBlock extends BlockBase {

  /**
   * Build the content for mymodule block.
   */
  public function build() {
    $getPluginDefinition = $this->getPluginDefinition();
    $form = new SubscribeForm($getPluginDefinition['mcsId']);

    return \Drupal::formBuilder()->getForm($form);
  }

}
