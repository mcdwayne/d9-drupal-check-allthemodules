<?php

/**
 * @file
 * Contains \Drupal\sms_ui|Form\GroupListUploadForm
 */

namespace Drupal\sms_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sms_ui\Ajax\ReloadGroupListCommand;

class GroupListUploadForm extends AjaxBaseForm {

  const FILE_UPLOAD_PATH = 'public://sms_ui/group_lists/';

  /**
   * {@inheritdoc}
   */
  protected function addAjaxCommands(AjaxResponse $response) {
    // @todo: Need to change the id selector to something more robust.
    $list = \Drupal::service('sms_ui.group_list')->getGroupList($this->currentUser());
    $response->addCommand(new ReloadGroupListCommand('#edit-group-list', $list));
    $this->addStatusMessages($response);
  }

  /**
   * {@inheritdoc}
   */
  protected function afterCloseRedirectUrl() {
    return Url::fromRoute('sms_ui.send_group');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_list_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['import'] = array (
      '#type' => 'fieldset',
      '#title' => $this->t('Group List Import'),
      '#collapsible' => TRUE,
      '#prefix' =>  $this->t('Import bulk phone numbers from a file. Each number should be on a separate line or separated by commas.'),
    );

    $form['import']['group_list'] = array (
      '#type' => 'file',
      '#title' => $this->t('Recipient list'),
//      '#required' => TRUE,  // @todo: Need to fix why this server-side validation fails after upload.
                              // @todo: The downside is that there is no client-side validation.
      '#size' => 40,
    );

    $form['import']['group_name'] = array (
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#size' => 40,
      '#required' => TRUE,
      '#maxlength' => 255,
    );

    $form['import']['submit'] = array (
      '#type' => 'submit',
      '#value' => $this->t('Upload group list'),
//      '#ajax' => array(
//        'url' => Url::fromRoute('<current>'),
//      ),
      // @todo: Ajax settings prevent client-side validation. Need to fix.
      '#attributes' => [
        'class' => ['use-ajax-submit'],
      ]
    );

    $form['#attributes']['enctype'] = 'multipart/form-data';

    // If the file location is not available, inform the user ahead of time.
    // @todo: Find a more secure location to safeguard user's data.
    // @todo: Consider placing this check in hook_requirements().
    $file_path = static::FILE_UPLOAD_PATH . $this->currentUser()->id();
    if (!file_exists($file_path) && !\Drupal::service('file_system')->mkdir($file_path, NULL, TRUE)) {
      drupal_set_message($this->t('The file location for uploads is not available, contact your administrator.'), 'error');
      $this->logger('sms_ui')->warning($this->t('Could not create upload directory @directory',
          ['@directory' => $file_path]));

      // @todo: Should we disable this functionality altogether in this condition?
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('op')->getUntranslatedString() === 'Upload group list') {
      // Ensure there is no name clash.
      $name = $form_state->getValue('group_name');
      if (in_array($name, \Drupal::service('sms_ui.group_list')->getGroupList($this->currentUser(), $name))) {
        $form_state->setErrorByName('group_name', $this->t('The group name @name is already taken.', ['@name' => $name]));
      }

      // Limit file size to 5MB and file type to csv / txt files.
      // @todo: Make this configurable.
      $validators = [
        'file_validate_size' => [1024 * 1024],
        'file_validate_extensions' => ['csv txt'],
      ];
      // Ensure file is uploaded.
      if ($this->getRequest()->files->get("files[group_list]", NULL, TRUE)) {
        /** @var \Drupal\file\Entity\File $file */
        $file_path = static::FILE_UPLOAD_PATH . $this->currentUser()->id();
        $file = file_save_upload('group_list', $validators, $file_path, 0, FILE_EXISTS_REPLACE);
        if (!$file) {
          $form_state->setErrorByName('group_list', $this->t('Could not upload file due to errors.'));
        }
        else {
          $form_state->set('uploaded_file', $file);
        }
      }
      else {
        $form_state->setErrorByName('group_list', $this->t("The file upload is required. Please upload a file."));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('op')->getUntranslatedString() === 'Upload group list') {
      if ($file = $form_state->get('uploaded_file')) {
        $group_name = $form_state->getValue('group_name');
        $file->setFilename($group_name);
        \Drupal::service('sms_ui.group_list')->addGroupList($this->currentUser(), $file);
        drupal_set_message($this->t('Saved bulk group list @name', ['@name' => $group_name]));
        $this->logger('sms_ui')->notice($this->t('User saved bulk group list @name.', ['@name' => $group_name]));
      }
      else {
        $group_name = $form_state->getValue('group_name');
        drupal_set_message($this->t('Could not upload group list @name.', ['@name' => $group_name]), 'error');
        $this->logger('sms_ui')->warning($this->t('Failed to save bulk group list @name.', ['@name' => $group_name]));
      }
    }
  }

}
