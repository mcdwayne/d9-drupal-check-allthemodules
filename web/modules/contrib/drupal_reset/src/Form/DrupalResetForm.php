<?php

namespace Drupal\drupal_reset\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\drupal_reset\DropDatabase;
use Drupal\drupal_reset\DeleteFiles;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class DrupalResetForm.
 *
 * @package Drupal\drupal_reset\Form
 */
class DrupalResetForm extends ConfigFormBase {

  /**
   * Drupal\drupal_reset\DropDatabase definition.
   *
   * @var \Drupal\drupal_reset\DropDatabase
   */
  protected $drupalResetDropDatabase;

  /**
   * Drupal\drupal_reset\DeleteFiles definition.
   *
   * @var \Drupal\drupal_reset\DeleteFiles
   */
  protected $drupalResetDeleteFiles;

  /**
   * DrupalResetForm constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\drupal_reset\DropDatabase $drupal_reset_drop_database
   * @param \Drupal\drupal_reset\DeleteFiles $drupal_reset_delete_files
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
      DropDatabase $drupal_reset_drop_database,
    DeleteFiles $drupal_reset_delete_files
    ) {
    parent::__construct($config_factory);
        $this->drupalResetDropDatabase = $drupal_reset_drop_database;
    $this->drupalResetDeleteFiles = $drupal_reset_delete_files;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
            $container->get('drupal_reset.drop_database'),
      $container->get('drupal_reset.delete_files')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'drupal_reset.drupalreset',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupal_reset_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('drupal_reset.drupalreset');
    if ($this->drupalResetDropDatabase->validateIsSupported()) {
      $form['drupal_reset_agree'] = [
        '#type' => 'radios',
        '#title' => $this->t('Select what to reset'),
        '#description' => $this->t('Options available to delete the files directory and/or the database tables for the current site, resetting the site so that Drupal is ready to be reinstalled from scratch.'),
        '#options' => [
          'reset_all' => $this->t('Delete all database tables and files'),
          'reset_database' => $this->t('Delete only database tables'),
          'reset_files' => $this->t('Delete only files'),
        ],
        '#default_value' => $config->get('drupal_reset_agree'),
      ];

      $form = parent::buildForm($form, $form_state);
      $form['actions']['submit']['#value'] = $this->t('Reset this site');
    }
    else {
      $form['drupal_reset_message'] = [
        '#markup' => '<p>Your database configuration is not supported by Drupal Reset. There must be one database (no master/slave) and the table prefix must be set to a string (not an array); use the empty string if you do not want a prefix. See your settings.php file.</p>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('drupal_reset.drupalreset')
      ->set('drupal_reset_agree', $form_state->getValue('drupal_reset_agree'))
      ->save();

    $reset_what = $form_state->getValue('drupal_reset_agree');

    if ($reset_what === 'reset_all' || $reset_what === 'reset_files') {
      // Delete the files.
      $this->drupalResetDeleteFiles->deletefiles();
      drupal_set_message(t('All files deleted.'));
    }

    if ($reset_what === 'reset_all' || $reset_what === 'reset_database') {
      // Drop the database
      $this->drupalResetDropDatabase->dropdatabase();

      // Redirect to install page.
      $form_state->setRedirectUrl(Url::fromUserInput('/install.php'));
    }
  }

}
