<?php

namespace Drupal\block_permissions;

use Drupal\block\Entity\Block;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockManagerInterface;

/**
 * Controller for the block permissions.
 */
class BlockPermissionsAccessControlHandler implements ContainerInjectionInterface {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $manager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block')
    );

  }

  /**
   * Constructs the block access control handler instance.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $manager
   *   Plugin manager.
   */
  public function __construct(BlockManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * Returns the service container.
   *
   * This method is marked private to prevent sub-classes from retrieving
   * services from the container through it. Instead,
   * \Drupal\Core\DependencyInjection\ContainerInjectionInterface should be used
   * for injecting services.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The service container.
   */
  private function container() {
    return \Drupal::getContainer();
  }

  /**
   * Returns the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function currentUser() {
    if (!$this->currentUser) {
      $this->currentUser = $this->container()->get('current_user');
    }
    return $this->currentUser;
  }

  /**
   * Access check for the default block list manage page.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   AccessResult object.
   */
  public function blockListAccess() {
    $account = $this->currentUser();

    $theme = \Drupal::config('system.theme')->get('default');

    // Check if the user has the proper permissions.
    $access = AccessResult::allowedIfHasPermission($account, 'administer block settings for theme ' . $theme);

    // If the user doesn't have access to the default theme validate if the user
    // has the generic permission administer blocks, if so: log an error stating
    // the default access is required in this case.
    if (!$access->isAllowed()) {
      $genericAccess = AccessResult::allowedIfHasPermission($account, 'administer blocks');
      if ($genericAccess->isAllowed()) {
        \Drupal::logger('block_permissions')->error('User @user has the administer block settings permission but no access to the default theme @theme, this is required.', array('@user' => $account->getAccountName(), '@theme' => $theme));
      }
    }
    return $access;
  }

  /**
   * Access check for the block list for specific themes.
   *
   * @param string $theme
   *   The theme name.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *    An access result
   */
  public function blockThemeListAccess($theme) {
    $account = $this->currentUser();

    // Check if the user has the proper permissions.
    $access = AccessResult::allowedIfHasPermission($account, 'administer block settings for theme ' . $theme);

    return $access;
  }

  /**
   * Access check for the add block form.
   *
   * @param string $plugin_id
   *   The theme name.
   *
   * @return \Drupal\Core\Access\AccessResult
   *    An access result
   */
  public function blockAddFormAccess($plugin_id) {
    $account = $this->currentUser();
    $plugin = $this->manager->getDefinition($plugin_id);

    // Check if the user has the proper permissions.
    $access = AccessResult::allowedIfHasPermission($account, 'administer blocks provided by ' . $plugin['provider']);

    return $access;
  }

  /**
   * Access check for the block config form.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The theme name.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *    An access result
   */
  public function blockFormAccess(Block $block) {
    $account = $this->currentUser();

    $plugin = $block->getPlugin();
    $configuration = $plugin->getConfiguration();

    // Check if the user has the proper permissions.
    $access = AccessResult::allowedIfHasPermission($account, 'administer blocks provided by ' . $configuration['provider']);

    return $access;
  }

}
