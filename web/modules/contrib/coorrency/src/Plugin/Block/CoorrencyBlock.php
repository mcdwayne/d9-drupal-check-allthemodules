<?php

namespace Drupal\coorrency\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a Coorrency block with which you can convert values anywhere.
 *
 * @Block(
 *   id = "coorrency_block",
 *   admin_label = @Translation("Coorrency"),
 * )
 */
class CoorrencyBlock extends BlockBase {
  // The CoorrencyBlock class extends BlockBase and, as such,
  // has four methods that must be implemented:
  // build(), blockAccess(), blockForm(), and blockSubmit().
  // The first one merely renders the form defined in our previous step.

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Return the form @ Form/CoorrencyBlockForm.php.
    return \Drupal::formBuilder()->getForm('Drupal\coorrency\Form\CoorrencyBlockForm');
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'convert coorrency');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue(
      'coorrency_block_settings',
      $form_state->getValue('coorrency_block_settings')
    );
  }

}
