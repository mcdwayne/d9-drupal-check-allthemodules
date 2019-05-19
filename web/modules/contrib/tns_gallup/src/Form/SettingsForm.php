<?php

namespace Drupal\tns_gallup\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;

/**
 * Configuration form for the TNS Gallup module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tns_gallup_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tns_gallup.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tns_gallup.settings');

    $form['site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site ID'),
      '#default_value' => $config->get('site_id'),
      '#description' => $this->t('Your site ID as provided by TNS Gallup.'),
      '#required' => TRUE,
    ];

    $role_names = array_map(function (Role $role) {
      return $role->get('label');
    }, Role::loadMultiple());
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Role specific visibility'),
      '#options' => $role_names,
      '#default_value' => array_values((array) $config->get('roles')),
      '#description' => $this->t('Include script only for the selected role(s). If you select none of the roles, then all roles will see the script. If a user has any of the roles checked, the script will be included for the user.'),
    ];

    $form['visibility'] = [
      '#type' => 'radios',
      '#title' => $this->t('Visibility'),
      '#options' => [
        0 => $this->t('Add to every page except the listed pages.'),
        1 => $this->t('Add to the listed pages only.'),
      ],
      '#default_value' => $config->get('visibility'),
      '#description' => $this->t('Add script to specific pages'),
    ];

    $form['pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Page specific visibility pages'),
      '#default_value' => $config->get('pages'),
      '#description' => $this->t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are blog for the blog page and /blog/* for every personal blog. <front> is the front page."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fields = [
      'site_id',
      'roles',
      'visibility',
      'pages',
    ];

    foreach ($fields as $field) {
      $this
        ->config('tns_gallup.settings')
        ->set($field, $form_state->getValue($field))
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
