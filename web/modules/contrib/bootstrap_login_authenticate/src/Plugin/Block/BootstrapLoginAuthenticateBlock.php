<?php

namespace Drupal\bootstrap_login_authenticate\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\bootstrap_login_authenticate\BootstrapLoginAuthenticateTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;

/**
 * Provides a 'Bootstrap Login Authenticate' block.
 *
 * @Block(
 *   id = "bootstrap_login_authenticate_block",
 *   admin_label = @Translation("Bootstrap Login Authenticate")
 * )
 */
class BootstrapLoginAuthenticateBlock extends BlockBase implements ContainerFactoryPluginInterface {
  use BootstrapLoginAuthenticateTrait;

  /**
   * The instantiated account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxy $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Checking User anonymous then open block.
    $user = $this->account->isAnonymous();
    if ($user) {
      $output = [
        '#attached' => [
          'library' => ['bootstrap_login_authenticate/bootstrap_login_authenticate'],
        ],
        '#theme' => 'bootstrap_login_authenticate_login_output',
        '#login' => $this->getLoginBlock(),
        '#register' => $this->getRegisterBlock(),
        '#forgot_password' => $this->getPasswordResetBlock(),
      ];

      return $output;
    }
  }

}
