<?php

namespace Drupal\cloudwords\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\cloudwords\CloudwordsFile;
use Drupal\Core\Link;
use Drupal\Core\Url;

class CloudwordsFileReplaceForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudwords_file_replace_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, \Drupal\cloudwords\CloudwordsDrupalProject $cloudwords_project = NULL, \Drupal\cloudwords\CloudwordsFile $cloudwords_file = NULL) {
    $form['#title'] = $this->t("Replace %file ", ['%file' => $cloudwords_file->getFilename()]);

    $form_state->set(['cloudwords_project'], $cloudwords_project);
    $form_state->set(['cloudwords_file'], $cloudwords_file);

    if($cloudwords_project->getStatus()->getCode() == 'project_closed'){
      $form['project_closed_markup'] = [
        '#markup' => $this->t('Project is closed.  Materials cannot be replaced.  '),
        '#weight' => '1000',
      ];

      $form['cancel'] = [
        '#markup' => Link::fromTextAndUrl($this->t('Click here to go back to project'), Url::fromUri('internal:/admin/cloudwords/projects/' . $cloudwords_project->getId()))->toString(),
        '#weight' => '1000',
      ];
      return $form;
    }

    $form['reference'] = [
      '#type' => 'file',
      '#title' => $this->t('Project reference materials'),
      '#description' => $this->t('Upload additional reference materials as a zip file.'),
      '#file_info' => NULL,
      '#size' => 10,
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
    ];

    $form['actions']['cancel'] = [
         '#markup' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromUri('internal:/admin/cloudwords/projects/' . $cloudwords_project->getId()))->toString(),
         '#weight' => '1000',
    ];


    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $upload_dir = 'private://cloudwords/reference_material';

    if (!file_prepare_directory($upload_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      $form_state->setErrorByName('upload][reference', $this->t('Unable to create the upload directory.'));
    }

    if ($file = file_save_upload('reference', ['file_validate_extensions' => ['zip']], $upload_dir)) {
      if(empty($file[0])) {
        $form_state->setErrorByName('reference', $this->t('Only files with the following extensions are allowed: zip.'));
      }else{
        $form_state->set(['reference_material'], $file);
      }
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->get(['reference_material'])) {
      $cloudwords_project = $form_state->get(['cloudwords_project']);
      $cloudwords_file = $form_state->get(['cloudwords_file']);

      $reference_material = $form_state->get('reference_material');

      foreach($reference_material as $file) {
        $return = cloudwords_get_api_client()->update_project_reference($cloudwords_project->getId(), $cloudwords_file->getId(), \Drupal::service("file_system")->realpath($file->getFileUri()));
        if (!empty($return->getId())) {
          drupal_set_message($this->t('File %old was replaced with %new.', [
            '%old' => $cloudwords_file->getFilename(),
            '%new' => $return->getFilename(),
          ]));
        }else{
          drupal_set_message($this->t('File could not be replaced on the project.'));
        }
      }
      $form_state->setRedirect('cloudwords.cloudwords_project_overview_form', ['cloudwords_project' => $cloudwords_project->getId()]);
    }
  }

}
