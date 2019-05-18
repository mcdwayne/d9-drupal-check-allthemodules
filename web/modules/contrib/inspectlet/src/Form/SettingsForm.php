<?php

namespace Drupal\inspectlet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Hotjar settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inspectlet_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['inspectlet.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $settings = $this->config('inspectlet.settings');

    $form = [];

    // Install Code.
    $form['general'] = [
      '#type'  => 'details',
      '#title' => $this->t('Install Code'),
      '#open'  => TRUE,
    ];

    $form['general']['install_code'] = [
      '#type'          => 'textarea',
      '#rows'          => 10,
      '#title'         => $this->t('Your Install Code'),
      '#description'   => $this->t('Please copy and paste the install code from Inspectlet here.'),
      '#default_value' => $settings->get('install_code'),
    ];

    // Pages.
    $form['pages'] = [
      '#type'  => 'details',
      '#title' => $this->t('Pages'),
      '#open'  => TRUE,
    ];

    $form['pages']['visibility_pages'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Add tracking to specific pages'),
      '#options'       => [
        'exclude_listed' => $this->t('Every page except the listed pages'),
        'include_listed' => $this->t('The listed pages only'),
      ],
      '#default_value' => $settings->get('visibility_pages'),
    ];

    $form['pages']['pages'] = [
      '#type'          => 'textarea',
      '#rows'          => 10,
      '#title'         => $this->t('Pages'),
      '#title_display' => 'invisible',
      '#description'   => $this->t('Specify pages by using their paths. Enter one path per line. The \'*\' character is a wildcard. An example path is /user/* for every user page. <front> is the front page.'),
      '#default_value' => $settings->get('pages'),
    ];

    // Roles.
    $form['roles'] = [
      '#type'  => 'details',
      '#title' => $this->t('Roles'),
      '#open'  => TRUE,
    ];

    $form['roles']['roles'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Add tracking for select roles only'),
      '#default_value' => $settings->get('roles'),
      '#options'       => user_role_names(),
      '#description'   => $this->t('If none of the roles are selected, all roles will be tracked.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $this->config('inspectlet.settings')
      ->set('install_code', $values['install_code'])
      ->set('visibility_pages', $values['visibility_pages'])
      ->set('pages', $values['pages'])
      ->set('roles', $values['roles'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
