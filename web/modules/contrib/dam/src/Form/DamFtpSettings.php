<?php

namespace Drupal\dam\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dam\Controller\DamController;
use Drupal\dam\Entity\FTPAccount;

/**
* FTP settings for the Digital Assets manager.
*
* @package Drupal\dam\Form
*/
class DamFtpSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dam_ftp_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dam.ftp_settings',
    ];
  }

  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dam.ftp_settings');

    $form['dam_root_folder'] = [
      '#type' => 'textfield',
      '#title' => t('Asset Folder'),
      '#description' => t('Enter the system path to the asset folder used for uploading files.'),
      '#default_value' => $config->get('dam_root_folder'),
      '#required' => TRUE,
    ];

    $form['dam_move_files'] = [
      '#type' => 'checkbox',
      '#title' => t('Move files into new folder'),
      '#description'=> t('Check this if you wish to move all files from the current location to the new root folder.')
    ];

    $form['ftp_server'] = [
      '#type' => 'textfield',
      '#title' => t('Servername'),
      '#description' => t('Enter the host name or ip that will be used to connect for sftp transfers'),
      '#default_value' =>  $config->get('ftp_server'),
      '#required' => TRUE
    ];

    $form['ftp_port'] = [
      '#type' => 'textfield',
      '#title' => t('Port'),
      '#description' => t('Enter the port that will be used to connect for sftp transfers'),
      '#default_value' =>  $config->get('ftp_port'),
      '#required' => TRUE
    ];

    $form['log'] = [
      '#type' => 'textfield',
      '#title' => t('Logfile'),
      '#description' => t('Enter the location of your logfile that will be used for sftp transfers'),
      '#default_value' =>  $config->get('log'),
      '#required' => TRUE
    ];

    $form['dam_ftp_details'] = [
      '#type' => 'fieldset',
      '#title' => t('Information')
    ];

    $ftpAccount = FTPAccount::getUserAccount(\Drupal::currentUser()->id());

    $form['dam_ftp_details']['details'] = [
      '#theme' => 'dam_ftp_account_info',
      '#account' => $ftpAccount
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save Settings')
    ];

    $form['update'] = [
      '#type' => 'submit',
      '#value' => t('Update'),
      '#submit' => [ '::damFilesUpdate' ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('dam.ftp_settings');
    if ($config->get('dam_root_folder') !== $form_state->getValue('dam_root_folder') && $form_state->getValue('dam_move_files') == 1) {
      // We need to setup the directory and then move the files to it.

      // Prevent use while we do this.
      $this->config('dam.ftp_settings')->set('dam_available', FALSE)->save();

      DamController::moveFiles($config->get('dam_root_folder'), $form_state->getValue('dam_root_folder'));

      // Re-enable system
      $this->config('dam.ftp_settings')->set('dam_available', TRUE)->save();
    }

    $this->config('dam.ftp_settings')
      ->set('dam_root_folder', $form_state->getValue('dam_root_folder'))
      ->set('ftp_server', $form_state->getValue('ftp_server'))
      ->set('ftp_port', $form_state->getValue('ftp_port'))
      ->set('log', $form_state->getValue('log'))
      ->save();
  }

  /**
  * Updates the Digital Asset manager with what is on the drive.
  */
  public function damFilesUpdate(array &$form, FormStateInterfae $form_state) {
    DamController::refreshSystem();
  }

}
