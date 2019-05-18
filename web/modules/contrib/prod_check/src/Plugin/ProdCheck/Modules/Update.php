<?php

namespace Drupal\prod_check\Plugin\ProdCheck\Modules;

use Drupal\prod_check\Plugin\ProdCheck\ProdCheckBase;

/**
 * Update status check
 *
 * @ProdCheck(
 *   id = "update",
 *   title = @Translation("Update status"),
 *   category = "modules",
 *   provider = "update"
 * )
 */
class Update extends ProdCheckBase {

  /**
   * {@inheritdoc}
   */
  public function state() {
    return !$this->moduleHandler->moduleExists('update');
  }

  /**
   * {@inheritdoc}
   */
  public function severity() {
    return $this->processor->warning();
  }

  /**
   * {@inheritdoc}
   */
  public function successMessages() {
    return [
      'value' => $this->t('Disabled'),
      'description' => $this->t('Your settings are OK for production use.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function failMessages() {
    return [
      'value' => $this->t('Enabled'),
      'description' => $this->generateDescription(
        $this->title(),
        'update.status',
        'You have enabled the %link module. It would be better to turn this off on production, contrary to what Drupal core claims, and keep it running on development. Updating and testing should happen on development before deploying to production anyway.'
      ),
    ];
  }

}
