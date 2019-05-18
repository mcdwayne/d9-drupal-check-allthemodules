<?php

/**
 * @file
 * Contains \Drupal\masquerade_nominate\Plugin\Block\MasqueradeBlock
 * actually might be able to use the normal masquerade block...
 * .
 */

namespace Drupal\masquerade_nominate\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\masquerade\Masquerade;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 * Provides an 'Aggregator feed' block with the latest items from the feed.
 *
 * @Block(
 *   id = "masquerade_nominate",
 *   admin_label = @Translation("Masquerade as..."),
 *   category = @Translation("User")
 * )
 */
class MasqueradeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  private $masquerade;
  private $targets;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, Masquerade $masquerade, AccountInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->masquerade = $masquerade;
    $this->targets = masquerade_nominate_masquerade_as($account);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('masquerade'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return (empty($this->targets) || $this->masquerade->isMasquerading()) ?
      AccessResult::forbidden() :
      AccessResult::allowed();
      //if cached, results should be sensitive to isMasqueradding
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    $links = [];
    foreach (User::loadMultiple($this->targets) as $account) {
      $links['#items'] = \Drupal::l(
        $account->getUserName(),
        Url::fromRoute(
          'entity.user.masquerade',
          ['user' => $account->id()]
          //['query' => \Drupal::destination()->getAsArray()]//this makes it worth hardly caching the block
        )
      );
    }
    if (count($links)) {
      return [
        '#theme' => 'item_list',
        '#items' => $links
      ];
    }
  }
}
