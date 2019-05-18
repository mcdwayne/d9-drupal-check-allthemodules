<?php

namespace Drupal\exitpopup\Form;

/**
* @file
* Contains Drupal\exitpopup\Form\ExitPopUp.
*/

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;

/**
 * Class ExitPopUp.
 */
class ExitPopUp extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'exitpopup.settings',
    ];
  }

  /**
   * Get form id.
   */
  public function getFormId() {
    return 'exitpopup_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('exitpopup.settings');

    $form['exitpopup_html'] = [
      '#type' => 'text_format',
      '#format' => 'restricted_html',
      '#base_type' => 'textarea',
      '#rows' => 20,
      '#title' => ' HTML Template',
      '#description' => 'The HTML code to be placed within the popup. HTML can be added through this function or on the page itself within a element.',
      '#default_value' => $config->get('exitpopup_html.value'),
    ];

    $form['exitpopup_css'] = [
      '#type' => 'text_format',
      '#format' => 'restricted_html',
      '#rows' => 20,
      '#title' => 'Custom CSS',
      '#description' => 'write custom css for the above html code ',
      '#default_value' => $config->get('exitpopup_css.value'),
    ];

    $form['exitpopup_delay'] = [
      '#type' => 'number',
      '#title' => ' Delay on Display POP UP',
      '#description' => 'The time, in seconds, until the popup activates and begins watching for exit intent. If showOnDelay is set to true, this will be the time until the popup shows. ',
      '#default_value' => $config->get('exitpopup_delay'),
    ];

    $form['exitpopup_cookie_exp'] = [
      '#type' => 'number',
      '#title' => 'Cookie Expire Time (in Days)',
      '#description' => 'The number of days to set the cookie for. A cookie is used to track if the popup has already been shown to a specific visitor. If the popup has been shown, it will not show again until the cookie expires. A value of 0 will always show the popup. ',
      '#default_value' => $config->get('exitpopup_cookie_exp'),
    ];

    $form['exitpopup_width'] = [
      '#type' => 'number',
      '#title' => ' Width For the POP UP',
      '#description' => 'The width of the popup. This can be overridden by adding your own CSS for the #bio_ep element. ',
      '#default_value' => $config->get('exitpopup_width'),
    ];

    $form['exitpopup_height'] = [
      '#type' => 'number',
      '#title' => ' Height For the POP UP',
      '#description' => 'The width of the popup. This can be overridden by adding your own CSS for the #bio_ep element. ',
      '#default_value' => $config->get('exitpopup_height'),
    ];

    $defaultRoles = $config->get('roles');
    $roles = Role::loadMultiple();
    $options = [];
    foreach ($roles as $role) {
      $options[$role->id()] = $role->label();
    }

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => t('Select roles to show exit popup'),
      '#options' => $options,
      '#default_value' => isset($defaultRoles) ? $defaultRoles : FALSE,
    ];

    $form['cache'] = [
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Get the config object.
    $config = $this->config('exitpopup.settings');

    $exitpopup_html = $form_state->getValue('exitpopup_html')['value'];
    $exitpopup_css = $form_state->getValue('exitpopup_css')['value'];
    $exitpopup_delay = $form_state->getValue('exitpopup_delay');
    $exitpopup_width = $form_state->getValue('exitpopup_width');
    $exitpopup_height = $form_state->getValue('exitpopup_height');
    $exitpopup_cookie_exp = $form_state->getValue('exitpopup_cookie_exp');
    $roles = $form_state->getValue('roles');

    // Set the values the user submitted in the form.
    $config->set('exitpopup_html.value', $exitpopup_html)
      ->set('exitpopup_css.value', $exitpopup_css)
      ->set('exitpopup_delay', $exitpopup_delay)
      ->set('exitpopup_width', $exitpopup_width)
      ->set('exitpopup_height', $exitpopup_height)
      ->set('exitpopup_cookie_exp', $exitpopup_cookie_exp)
      ->set('roles', $roles)
      ->save();
  }

}
