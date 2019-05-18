<?php

namespace Drupal\contest\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\contest\ContestHelper;
use Drupal\contest\ContestStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Running Contests' block.
 *
 * @Block(
 *   id = "contest_recent_block",
 *   admin_label = @Translation("Running Contests"),
 *   category = @Translation("Contest")
 * )
 */
class ContestRecentBlock extends BlockBase implements ContainerFactoryPluginInterface {
  protected $request;

  /**
   * Constructs a new SwitchUserBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Symfony\Component\HttpFoundation\RequestStack $request
   *   The request dependency injection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('request_stack'));
  }

  /**
   * Build a contest list block.
   *
   * @return array
   *   A Drupal form array.
   */
  public function build() {
    $block = [];
    $contests = ContestStorage::getMostRecentContest();
    $format = ContestHelper::getDefaultFormat();
    $max = 100;

    if (empty($contests)) {
      return [];
    }
    foreach ($contests as $contest) {
      $page = $this->request->getCurrentRequest()->attributes->get('contest');

      if ($contest->id->value == $page->id->value && strpos(get_class($contest), 'Contest') !== FALSE) {
        continue;;
      }
      $block['#markup'] .= '<h3 class="contest-block-title">' . $contest->label() . '</h3>';
      $block['#markup'] .= text_summary($contest->body->value, $format, $max);
    }
    if (!empty($block)) {
      $block['#title'] = $this->t('Contests');
    }
    return $block;
  }

  /**
   * The cache tag string.
   *
   * @return string
   *   A cache tag string.
   */
  public function getCacheTags() {
    return ['contest_recent_block'];
  }

  /**
   * The user access to a contest block.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account.
   *
   * @return bool
   *   True if the user has access to the block.
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access contests');
  }

}
