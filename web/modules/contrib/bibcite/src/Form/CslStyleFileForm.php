<?php

namespace Drupal\bibcite\Form;

use Drupal\bibcite\Csl;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for installing bibcite_csl_style entity from file.
 */
class CslStyleFileForm extends CslStyleForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /*
     * CSL text will be extracted from file.
     */
    unset($form['csl']);

    $form['file'] = [
      '#type' => 'file',
      '#title' => $this->t('File'),
      '#description' => $this->t('Allowed types: @extensions.', ['@extensions' => 'csl, xml']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($file = $this->extractFile()) {
      $content = file_get_contents($file->getRealPath());
      $csl = new Csl($content);
      if ($csl->validate()) {
        $form_state->setValue('csl', $content);
        parent::validateForm($form, $form_state);
      }
      else {
        $form_state->setErrorByName('file', $this->t('The uploaded file does not contain valid CSL.'));
      }
    }
    else {
      $form_state->setErrorByName('file', $this->t('The file could not be uploaded.'));
    }
  }

  /**
   * Extract valid file from request.
   *
   * @return null|\Symfony\Component\HttpFoundation\File\UploadedFile
   *   Uploaded file or NULL if file not uploaded.
   */
  protected function extractFile() {
    $all_files = $this->getRequest()->files->get('files', []);
    if (!empty($all_files['file'])) {
      /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
      $file = $all_files['file'];
      if ($file->isValid()) {
        return $file;
      }
    }

    return NULL;
  }

}
