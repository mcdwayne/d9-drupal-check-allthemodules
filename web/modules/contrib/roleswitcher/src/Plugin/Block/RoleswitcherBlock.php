<?php
/**
 * @file
 * Contains Drupal\roleswitcher\Plugin\Block\RoleswitcherBlock.
 */

namespace Drupal\roleswitcher\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\Role;
use Drupal;

/**
 * Provides a block with options to switch a role.
 *
 * @Block(
 *   id = "roleswitcher_block",
 *   admin_label = @Translation("Switch role")
 * )
 */
class RoleswitcherBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $options = array(
      'query' => drupal_get_destination()
    );
    $options['query']['token'] = \Drupal::csrfToken()->get('roleswitcher-switch');
    $output = "<ul>";

    $linkGenerator = Drupal::linkGenerator();

    /** @var Role $role */
    foreach (user_roles() as $role) {
      if ($role->id() != 'roleswitcher') {
        $url = Url::fromRoute('roleswitcher.switchrole', array('rid' => $role->id()), $options);
        $output .= "<li>" . $linkGenerator->generate($role->label(), $url) . "</li>";
      }
    }

    // Add reset roles link.
    $url = Url::fromRoute('roleswitcher.switchrole', array('rid' => 'reset'), $options);
    $output .= "<li>" . $linkGenerator->generate($this->t('Reset to defaults'), $url) . "</li>";

    $output .= "</ul>";
    return array(
      '#markup' => $output,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return $account->hasPermission('administer permissions');
  }
}