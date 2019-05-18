<?php

namespace Drupal\nodeletter_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\nodeletter\Form\NewsletterSubmitForm;

/**
 * Provides a 'Submit Newsletter Sending' block.
 *
 * @Block(
 *   id = "nodeletter_sending_submit_block",
 *   admin_label = @Translation("Nodeletter Sending Submit"),
 *   category = "Nodeletter",
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 *
 * )
 */
class NewsletterSubmitBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    if (!$node) {
      return [];
    }
    $nodeletter = nodeletter_service();
    if (!$nodeletter->nodeTypeEnabled($node->getType())) {
      return [];
    }

    $form = NewsletterSubmitForm::create(\Drupal::getContainer());
    $content = \Drupal::formBuilder()->getForm($form, $node);
    return $content;
  }

}
