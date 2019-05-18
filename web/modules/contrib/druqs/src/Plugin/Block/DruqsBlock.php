<?php

namespace Drupal\druqs\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides the druqs search field as a block.
 *
 * @Block(
 *   id = "druqs",
 *   admin_label = @Translation("Druqs"),
 *   category = @Translation("Administration"),
 * )
 */
class DruqsBlock extends BlockBase {

  /**
   * Account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * Constructs an authentication subscriber.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   Account proxy.
   */
  public function __construct(AccountProxyInterface $account_proxy) {
    $this->accountProxy = $account_proxy;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    if ($this->accountProxy->hasPermission('access druqs')) {
      $build = [
        'tab' => [
          '#type' => 'search',
          '#attributes' => [
            'id' => 'druqs-input',
            'placeholder' => $this->t('Quick search'),
          ],
          '#suffix' => '<div id="druqs-results"></div>',
        ],
        '#wrapper_attributes' => [
          'class' => ['druqs-tab'],
        ],
        '#attached' => [
          'library' => ['druqs/drupal.druqs'],
        ],
      ];
    }

    return $build;
  }

}
