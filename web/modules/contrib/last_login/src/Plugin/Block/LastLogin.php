<?php

namespace Drupal\last_login\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a block to display the Last login time of current user.
 *
 * @Block(
 * id = "last_login",
 * admin_label = @Translation("Last Login Time"),
 * category = @Translation("Last Login"),
 * )
 */
class LastLogin extends BlockBase implements ContainerFactoryPluginInterface {
  protected $session;
  protected $account;

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   Provides configuration array.
   * @param string $plugin_id
   *   Plugin id.
   * @param object $plugin_definition
   *   Plugin defination.
   * @param Symfony\Component\HttpFoundation\Session\Session $session
   *   Provides session.
   * @param Drupal\Core\Session\AccountProxyInterface $account
   *   Provides account variable.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Session $session, AccountProxyInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->session = $session;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $uid = $this->account->id();
    if ($uid > 0) {
      $data['#markup'] = '<div class="last-access">' . $this->t("Last Login:") . $this->session->get('last_login') . '</div>';
      $data['#cache']['max-age'] = 0;
      return $data;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('session'),
      $container->get('current_user')
    );
  }

}
