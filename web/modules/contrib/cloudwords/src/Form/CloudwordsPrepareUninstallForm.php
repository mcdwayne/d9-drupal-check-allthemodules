<?php

namespace Drupal\cloudwords\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

class CloudwordsPrepareUninstallForm extends ConfirmFormBase {

  protected $confirm_text_entry = 'CONFIRM';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudwords_project_cancel_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you want to prepare uninstalling Cloudwords?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('system.modules_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription () {
    return $this->t('This will delete all Cloudwords translatable and project data within this site.  This process cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['confirm'] = [
      '#title' => $this->t('Enter "@confirm" to confirm deleting all Cloudwords translatable and project data.', ['@confirm' => $this->confirm_text_entry]),
      '#type' => 'textfield',
    ];
    return $form;
  }
  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if($form_state->getValue('confirm') != $this->confirm_text_entry){
      $form_state->setErrorByName('confirm', 'Confirm text required to delete all Cloudwords modules entities');
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $projects_storage = \Drupal::entityTypeManager()->getStorage('cloudwords_project');
    $projects_entities = $projects_storage->loadByProperties([]);
    $projects_storage->delete($projects_entities);

    $translatable_storage = \Drupal::entityTypeManager()->getStorage('cloudwords_translatable');
    $translatable_entities = $translatable_storage->loadByProperties([]);
    $translatable_storage->delete($translatable_entities);

    drupal_set_message($this->t('Cloudwords Projects and Translatables deleted.  You may now uninstall the Cloudwords module'));
    $form_state->setRedirect('system.modules_uninstall');
  }
}
