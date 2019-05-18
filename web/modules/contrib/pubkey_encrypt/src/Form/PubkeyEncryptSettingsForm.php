<?php

namespace Drupal\pubkey_encrypt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\pubkey_encrypt\PubkeyEncryptManager;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for the Pubkey Encrypt main settings form.
 */
class PubkeyEncryptSettingsForm extends ConfigFormBase {

  /**
   * Pubkey Encrypt manager service.
   *
   * @var \Drupal\pubkey_encrypt\PubkeyEncryptManager
   */
  protected $pubkeyEncryptManager;

  /**
   * Constructs a PubkeyEncryptSettingsForm object.
   *
   * @param \Drupal\pubkey_encrypt\PubkeyEncryptManager $pubkey_encrypt_manager
   *   Pubkey Encrypt service.
   */
  public function __construct(PubkeyEncryptManager $pubkey_encrypt_manager) {
    $this->pubkeyEncryptManager = $pubkey_encrypt_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pubkey_encrypt.pubkey_encrypt_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pubkey_encrypt_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pubkey_encrypt.admin_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $enabled_roles = $this->config('pubkey_encrypt.admin_settings')
      ->get('enabled_roles');

    $role_options = [];
    foreach (Role::loadMultiple() as $role) {
      $role_options[$role->id()] = $role->label();
    }
    unset($role_options[AccountInterface::ANONYMOUS_ROLE]);
    unset($role_options[AccountInterface::AUTHENTICATED_ROLE]);

    $form['enabled_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled roles'),
      '#description' => $this->t("Uncheck the roles for which you want Pubkey Encrypt to disable all its processes. This could boost the performance of various operations like creation of a user etc."),
      '#options' => $role_options,
      '#default_value' => $enabled_roles,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabled_roles = array_filter($form_state->getValue('enabled_roles'));

    // Save the configuration.
    $this->config('pubkey_encrypt.admin_settings')
      ->set('enabled_roles', $enabled_roles)
      ->save();

    parent::submitForm($form, $form_state);

    // A user may have activated any previously disabled roles. Update all Role
    // keys to cater for this change.
    $this->pubkeyEncryptManager->updateAllRoleKeys();
  }

}
