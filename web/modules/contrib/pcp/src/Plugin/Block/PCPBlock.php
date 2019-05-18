<?php

namespace Drupal\pcp\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'PCP' block.
 *
 * @Block(
 *   id = "pcp_block",
 *   admin_label = @Translation("Profile Complete Percentage"),
 *   category = @Translation("User")
 * )
 */
class PCPBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var AccountInterface
   */
  private $current_user;

  /**
   * PCPBlock constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param AccountInterface $current_user
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->current_user = $current_user;
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
  protected function blockAccess(AccountInterface $account) {
    return $account->isAuthenticated() ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user_id = $this->current_user->id();
    $account = User::load($user_id);

    module_load_include('inc', 'pcp', 'pcp');
    $pcp_data = pcp_get_complete_percentage_data($account);

    if (($pcp_data['hide_pcp_block'] && $pcp_data['incomplete'] == 0) || $pcp_data['total'] == 0) {
      return [];
    }

    $pcp_markup = [
      '#theme' => 'pcp_template',
      '#uid' => $pcp_data['uid'],
      '#total' => $pcp_data['total'],
      '#open_link' => $pcp_data['open_link'],
      '#completed' => $pcp_data['completed'],
      '#incomplete' => $pcp_data['incomplete'],
      '#next_percent' => $pcp_data['next_percent'],
      '#nextfield_name' => $pcp_data['nextfield_name'],
      '#nextfield_title' => $pcp_data['nextfield_title'],
      '#current_percent' => $pcp_data['current_percent'],
      '#attached' => [
        'library' => ['pcp/pcp.block'],
      ],
    ];

    return $pcp_markup;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
