<?php

namespace Drupal\private_taxonomy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Private Taxonomy Settings form.
 */
class PrivateTaxonomySettingsForm extends ConfigFormBase {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory) {
    $this->setConfigFactory($config_factory);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'private_taxonomy_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['private_taxonomy.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('private_taxonomy.settings');

    $form['prefix'] = [
      '#markup' => $this->t('Decide which user will own the terms to be cloned and create the terms.  Then set the user on this page.  Check the box to clone the terms to existing users.  This can only be done once.  Check the box for new users to clone terms to users that are added.'),
    ];

    $user_name = $config->get('cloning_user_name');
    $form['cloning_user_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User terms to clone'),
      '#autocomplete_path' => 'user/autocomplete',
      '#size' => 15,
      '#default_value' => $user_name,
      '#description' => $this->t('User name with terms that are to be cloned for other users.'),
    ];

    $form['users'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Users'),
    ];

    $sql = $this->database->select('user_term', 'user_term');
    $sql->join('users_field_data', 'u', 'u.uid = user_term.uid');
    $count = $sql->condition('u.name', $user_name, '!=')
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($count == 0) {
      $form['users']['existing_users_cloning'] = [
        '#type' => 'checkbox',
        '#title' => '&nbsp;' . $this->t('Create default terms for existing users.'),
      ];
    }
    else {
      $form['users']['existing_users_cloning'] = [
        '#markup' => $this->t('Users with private terms already exist.'),
      ];
    }

    $form['users']['enable_new_users'] = [
      '#type' => 'checkbox',
      '#title' => '&nbsp;' . $this->t('Enable creation of default terms for new users.'),
      '#default_value' => $config->get('enable_new_users'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $user_name = $form_state->getValue('cloning_user_name');
    if (mb_strlen($user_name) > 0) {
      $uid = $this->database->select('users_field_data', 'users_field_data')
        ->condition('users_field_data.name', $user_name)
        ->fields('users_field_data', ['uid'])
        ->execute()
        ->fetchField();
      if (!is_numeric($uid)) {
        $form_state->setError($form['cloning_user_name'],
          $this->t('Invalid user name'));
      }
    }
    else {
      if ($form_state->getValue('enable_new_users')) {
        $form_state->setError($form['cloning_user_name'],
          $this->t('Missing user name'));
      }
      if ($form_state->getValue('existing_users_cloning')) {
        $form_state->setError($form['cloning_user_name'],
          $this->t('Missing user name'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('private_taxonomy.settings')
      ->set('enable_new_users', $form_state->getValue('enable_new_users'))
      ->set('cloning_user_name', $form_state->getValue('cloning_user_name'))
      ->save();
    if ($form_state->getValue('existing_users_cloning')) {
      $user_name = $form_state->getValue('cloning_user_name');
      $count = private_taxonomy_create_terms($user_name);
      $this->messenger()->addMessage($this->t('Terms cloned for @count users', ['@count' => $count]), 'status');
    }

    parent::submitForm($form, $form_state);
  }

}
