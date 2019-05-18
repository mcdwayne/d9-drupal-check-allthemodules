<?php

namespace Drupal\editor_absolute_image_url\Form;

use Drupal\editor\Form\EditorImageDialog;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\editor\Entity\Editor;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Provides an image dialog for text editors.
 *
 * @internal
 */
class EditorImageDialogWithAbsolute extends EditorImageDialog {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   The text editor to which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, Editor $editor = NULL) {
    $form = parent::buildForm($form, $form_state, $editor);

    $form['absolute'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Absolute URL'),
      '#default_value' => TRUE,
      '#description' => $this->t('Use an absolute URL for the embedded image. Necessary for headless sites.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Convert any uploaded files from the FID values to data-entity-uuid
    // attributes and set data-entity-type to 'file'.
    $fid = $form_state->getValue(['fid', 0]);
    if (!empty($fid)) {
      $file = $this->fileStorage->load($fid);
      $file_url = file_create_url($file->getFileUri());

      $absolute = $form_state->getValue('absolute');
      if (!$absolute) {
        // Transform absolute image URLs to relative image URLs: prevent
        // problems on multisite set-ups and prevent mixed content errors.
        $file_url = file_url_transform_relative($file_url);
      }

      $form_state->setValue(['attributes', 'src'], $file_url);
      $form_state->setValue(['attributes', 'data-entity-uuid'], $file->uuid());
      $form_state->setValue(['attributes', 'data-entity-type'], 'file');
    }

    // When the alt attribute is set to two double quotes, transform it to the
    // empty string: two double quotes signify "empty alt attribute". See above.
    if (trim($form_state->getValue(['attributes', 'alt'])) === '""') {
      $form_state->setValue(['attributes', 'alt'], '');
    }

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#editor-image-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
