<?php

namespace Drupal\simple_access\views\access;

/**
 * Views access plugin to make use of simple access.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;

/**
 *
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "simple_access_group",
 *   title = @Translation("Simple Access Group"),
 *   help = @Translation("Will be available to all users.")
 * )
 */
class SimpleAccessViewsAccess extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $roles = array_keys($account->roles);
    $roles[] = $account->id() ? DRUPAL_AUTHENTICATED_RID : DRUPAL_ANONYMOUS_RID;

    $groups = simple_access_groups_from_roles($roles);
    return array_intersect(array_filter($this->options['groups']), $groups);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    // TODO: Implement alterRouteDefinition() method.
  }

  /**
   * {@inheritdoc}
   */
  public function get_access_callback() {
    return ['simple_access_groups_check_user', [array_filter($this->options['groups'])]];
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    $count = count($this->options['groups']);
    if ($count < 1) {
      return t('No group(s) selected');
    }
    elseif ($count > 1) {
      return t('Multiple groups');
    }
    else {
      $gids = array_map(['simple_access_views_plugin_group', '_map_groups'], simple_access_get_groups());
      $gid = array_shift($this->options['groups']);
      return $gids[$gid];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['groups'] = ['default' => []];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $options = array_map(['simple_access_views_plugin_group', '_map_groups'], simple_access_get_groups());
    $form['groups'] = [
      '#type' => 'checkboxes',
      '#title' => t('Simple access groups'),
      '#default_value' => $this->options['groups'],
      '#options' => $options,
      '#description' => $this->t('Only the checked simple access groups will be able to access this display. Note that users with "access all views" can see any view, regardless of role.'),
    ];
  }

  /**
   * Callback for array_map.
   */
  public function _map_groups($a) {
    return $a['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    if (!array_filter($form_state['values']['access_options']['groups'])) {
      $form_state->setError($form['groups'], $this->t('You must select at least one group if type is "by group"'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // I hate checkboxes.
    $form_state['values']['access_options']['groups'] = array_filter($form_state['values']['access_options']['groups']);
  }

}
