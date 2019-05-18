<?php

/**
 * @file
 * Contains Drupal\nyan\NyanController.
 */

namespace Drupal\nyan;

use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Returns responses for nyan module routes.
 *
 * @todo Why container aware?
 * @todo Why 'controller' and not something with 'router' in the name?
 */
class NyanController extends ContainerAware {

  /**
   * Settings form for configuring nyan.
   */
  public function settings() {
    // To display a form, we'd normally use drupal_get_form(). But to use a
    // method instead of a function as the callback, this pattern is used.
    // Borrowed from \Drupal\block\BlockListController::render().
    // @todo Once http://drupal.org/node/1903176 is committed, use this:
    //   return drupal_get_callback_form('nyan_settings_form', array($this, 'buildForm'));
    $form_state = array();
    $form_state['build_info']['args'] = array();
    $form_state['build_info']['callback'] = array($this, 'buildForm');
    return drupal_build_form('nyan_settings_form', $form_state);
  }

  /**
   * Form constructor for the nyan settings form.
   */
  public function buildForm($form, &$form_state) {
    $form['audio'] = array(
      '#type' => 'fieldset',
      '#title' => t('Audio'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['audio']['enabled'] = array(
      '#type' =>'checkbox',
      '#title' => t('Enable audio'),
      '#default_value' => config('nyan.settings')->get('enabled'),
    );
    $form['audio']['controls_visible'] = array(
      '#type' =>'checkbox',
      '#title' => t('Show controls'),
      '#default_value' => config('nyan.settings')->get('controls.visible'),
      '#states' => array(
        'visible' => array(
          ':input[name="enabled"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['audio']['volume_default'] = array(
      '#type' => 'select',
      '#title' => t('Initial volume'),
      '#description' => t('If you prefer the initial volume louder or quieter, you can set it here.'),
      '#default_value' => config('nyan.settings')->get('volume.default'),
      '#options' => array(
        '.10' => '10%',
        '.20' => '20%',
        '.30' => '30%',
        '.40' => '40%',
        '.50' => '50%',
        '.60' => '60%',
        '.70' => '70%',
        '.80' => '80%',
        '.90' => '90%',
        '1'   => '100%',
      ),
      '#required' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="enabled"]' => array('checked' => TRUE),
        ),
      ),
    );

    // Use NyanController::submitForm() as the submission handler.
    $form['#submit'][] = array($this, 'submitForm');

    // This will attach a submit button.
    return system_config_form($form, $form_state);
  }

  /**
   * Form submission handler for NyanController::buildForm().
   */
  public function submitForm($form, &$form_state) {
    // Save the submitted nyan types in our config file.
    config('nyan.settings')
      ->set('enabled', $form_state['values']['enabled'])
      ->set('controls.visible', $form_state['values']['controls_visible'])
      ->set('volume.default', $form_state['values']['volume_default'])
      ->save();
  }
}
