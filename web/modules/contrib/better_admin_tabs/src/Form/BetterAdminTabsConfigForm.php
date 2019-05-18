<?php

namespace Drupal\better_admin_tabs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class BetterAdminTabsConfigForm.
 *
 * @package Drupal\better_admin_tabs\Form
 */
class BetterAdminTabsConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'better_admin_tabs_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $weight = 1;

    $form = parent::buildForm($form, $form_state);
    $local_tasks = $this->_getLocalTasks();

    foreach ($local_tasks as $key => $title) {
      $formkey = str_replace('.', '_', $key);

      $color = \Drupal::service('better_admin_tabs')->getDefaultColor($key);
      $icon = \Drupal::service('better_admin_tabs')->getDefaultIcon($key);

      $form[$formkey] = [
        '#type' => 'fieldset',
        '#title' => $title . ' (' . $key . ')',
      ];
      $form[$formkey][$formkey . '_color'] = [
        '#type' => 'textfield',
        '#title' => t('Color'),
        '#default_value' => $color,
        '#required' => TRUE,
        '#weight' => $weight,
        '#attributes' => ['style' => 'background-color: ' . $color . '; color: white'],
      ];
      $form[$formkey][$formkey . '_icon'] = [
        '#type' => 'textfield',
        '#title' => t('Icon'),
        '#default_value' => $icon,
        '#required' => TRUE,
        '#weight' => $weight,
      ];
      $form[$formkey][$formkey . '_preview'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => '',
        '#attributes' => [
          'style' => 'background: ' . $color . ' url("' . $icon . '") no-repeat center / 25px 25px; color: white; width: 50px; height: 50px; border-radius: 100%',
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('better_admin_tabs.settings');
    $local_tasks = $this->_getLocalTasks();

    foreach ($local_tasks as $key => $title) {
      $formkey = str_replace('.', '_', $key);
      $config->set($formkey . '_color', $form_state->getValue($formkey . '_color'));
      $config->set($formkey . '_icon', $form_state->getValue($formkey . '_icon'));
    }

    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'better_admin_tabs.settings',
    ];
  }

  private function _getLocalTasks() {
    $tasks = [];

    /** @var \Drupal\Core\Menu\LocalTaskManagerInterface $manager */
    $manager = \Drupal::service('plugin.manager.menu.local_task');
    $links = $manager
      ->getLocalTasks('entity.node.canonical', 0);

    foreach ($links['tabs'] as $key => $admin_tab) {
      $title = $admin_tab['#link']['title'];
      $routename = $admin_tab['#link']['url']->getRouteName();
      $tasks[$routename] = $title;
    }

    return $tasks;
  }

}
