<?php

namespace Drupal\pfdp\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Directory form for private_files_download_permission.
 */
class DirectoryForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $settings = \Drupal::config('pfdp.settings');
    $pfdp_directory = $this->entity;
    // Prepare the form.
    $form = parent::form($form, $form_state);
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#field_prefix' => Settings::get('file_private_path'),
      '#size' => 60,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $pfdp_directory->path,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#disabled' => !$pfdp_directory->isNew(),
      '#default_value' => $pfdp_directory->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['path'],
      ],
    ];
    $form['bypass'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bypass'),
      '#default_value' => $pfdp_directory->bypass,
      '#description' => $this->t('Enable to make this module ignore the above path.'),
    ];
    $form['grant_file_owners'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Grant file owners'),
      '#default_value' => $pfdp_directory->grant_file_owners,
      '#description' => $this->t('Grant access to users who uploaded the files (i.e.: the file owners).'),
    ];
    if ($settings->get('by_user_checks')) {
      $form['users_wrapper'] = [
        '#type' => 'details',
        '#title' => $this->t('Enabled users'),
        '#open' => !empty($pfdp_directory->users),
      ];
      $form['users_wrapper']['users'] = [
        '#type' => 'checkboxes',
        '#options' => $this->getUsers(),
        '#default_value' => $pfdp_directory->users,
      ];
    }
    $form['roles_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Enabled roles'),
      '#open' => !empty($pfdp_directory->roles),
    ];
    $form['roles_wrapper']['roles'] = [
      '#type' => 'checkboxes',
      '#options' => user_role_names(FALSE),
      '#default_value' => $pfdp_directory->roles,
    ];
    // Return the form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Retrieve $path (which, being required, is surely not blank).
    $path = $form_state->getValue('path');
    // Perform slash validation:
    if (0 < Unicode::strlen($path)) {
      $first_character = Unicode::substr($path, 0, 1);
      $last_character = Unicode::substr($path, -1, 1);
      // ...there must be a leading slash.
      if (('/' !== $first_character) && ('\\' !== $first_character)) {
        $form_state->setErrorByName('path', $this->t('You must add a leading slash.'));
      }
      if (1 < Unicode::strlen($path)) {
        // ...there cannot be multiple consecutive slashes.
        if ((FALSE !== Unicode::strpos($path, '//')) || (FALSE !== Unicode::strpos($path, '\\\\'))) {
          $form_state->setErrorByName('path', $this->t('You cannot use multiple consecutive slashes.'));
        }
        // ...there cannot be trailing slashes.
        if (('/' === $last_character) || ('\\' === $last_character)) {
          $form_state->setErrorByName('path', $this->t('You cannot use trailing slashes.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $pfdp_directory = $this->entity;
    // Save the directory and display the status message.
    try {
      $pfdp_directory->save();
      $this->logger('pfdp')->info('The directory %path was saved successfully.', ['%path' => $pfdp_directory->path]);
      drupal_set_message($this->t('The directory %path was saved successfully.', ['%path' => $pfdp_directory->path]), 'status');
    }
    catch (EntityStorageException $exception) {
      $this->logger('pfdp')->error('The directory %path was not saved.', ['%path' => $pfdp_directory->path]);
      drupal_set_message($this->t('The directory %path was not saved.', ['%path' => $pfdp_directory->path]), 'error');
    }
    // Redirect to the proper url.
    $form_state->setRedirect('entity.pfdp_directory');
  }

  /**
   * Checks if a directory actually exists.
   */
  public function exists($id) {
    $pfdp_directory = $this->entityQuery->get('pfdp_directory')
      ->condition('id', $id)
      ->execute();
    return (bool) $pfdp_directory;
  }

  /**
   * Retrieves an array of users.
   */
  protected function getUsers() {
    $settings = \Drupal::config('pfdp.settings');
    $users = NULL;
    // Load user list from cache (if enabled and available).
    if (($settings->get('cache_users')) && ($cache = \Drupal::cache()->get('pfdp.cache.users'))) {
      $users = $cache->data;
    }
    else {
      // Get raw data from the database.
      $users = db_select('users_field_data', 't')
        ->fields('t', ['uid', 'name'])
        ->orderBy('t.name', 'ASC')
        ->condition('uid', RoleInterface::ANONYMOUS_ID, '<>')
        ->execute()
        ->fetchAllKeyed(0, 1);
      // Set cache data.
      \Drupal::cache()->set('pfdp.cache.users', $users);
    }
    // Return the user list.
    return $users;
  }

}
