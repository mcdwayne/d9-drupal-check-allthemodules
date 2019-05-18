<?php
/**
 * @file
 * Contains \Drupal\feadmin_menu\Plugin\FeAdminTool\MenuFeAdminTool.
 * 
 * Sponsored by: www.freelance-drupal.com
 */

namespace Drupal\feadmin_menu\Plugin\FeAdminTool;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\feadmin\FeAdminTool\FeAdminToolBase;

/**
 * Provides a front-end administration tool for blocks.
 *
 * @FeAdminTool(
 *   id = "feadmin_menu",
 *   label = @Translation("Menu administration"),
 *   description = @Translation("This tool let's you move menu items within menus by drag&drop.")
 * )
 */
class MenuFeAdminTool extends FeAdminToolBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('administer menu');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#attached' => array(
        'library' => array('feadmin_menu/feadmin_menu'),
      ),
    );
  }

  /**
   * Form constructor.
   *
   * Plugin forms are embedded in other forms. In order to know where the plugin
   * form is located in the parent form, #parents and #array_parents must be
   * known, but these are not available during the initial build phase. In order
   * to have these properties available when building the plugin form's
   * elements, let this method return a form element that has a #process
   * callback and build the rest of the form in the callback. By the time the
   * callback is executed, the element's #parents and #array_parents properties
   * will have been set by the form API. For more documentation on #parents and
   * #array_parents, see \Drupal\Core\Render\Element\FormElement.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The form structure.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return array();
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }
}
