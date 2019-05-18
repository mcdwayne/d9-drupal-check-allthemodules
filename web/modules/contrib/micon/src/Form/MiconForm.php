<?php

namespace Drupal\micon\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\micon\Entity\MiconInterface;

/**
 * Class MiconForm.
 *
 * @package Drupal\micon\Form
 */
class MiconForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $micon = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $micon->label(),
      '#description' => $this->t("A descriptive label for the Micon package."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Class prefix'),
      '#description' => $this->t('The unique selector prefix of this package. It will be used for rendering the icons within class names and paths. It will replace any class prefix or font names specified within the IcoMoon zip package.'),
      '#default_value' => $micon->id(),
      '#field_prefix' => '.',
      '#machine_name' => [
        'label' => $this->t('Class prefix'),
        'exists' => '\Drupal\micon\Entity\Micon::load',
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
        'field_prefix' => '.',
      ],
      '#disabled' => !$micon->isNew(),
    ];

    $validators = array(
      'file_validate_extensions' => array('zip'),
      'file_validate_size' => array(file_upload_max_size()),
    );
    $form['file'] = array(
      '#type' => 'file',
      '#title' => $micon->isNew() ? $this->t('IcoMoon Font Package') : $this->t('Replace IcoMoon Font Package'),
      '#description' => array(
        '#theme' => 'file_upload_help',
        '#description' => $this->t('An IcoMoon font package. <a href="https://icomoon.io">Generate & Download</a>'),
        '#upload_validators' => $validators,
      ),
      '#size' => 50,
      '#upload_validators' => $validators,
      '#attributes' => array('class' => array('file-import-input')),
    );

    $form['#entity_builders']['update_status'] = [$this, 'updateStatus'];

    return $form;
  }

  /**
   * Entity builder updating the micon status with the submitted value.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\micon\MiconInterface $micon
   *   The micon updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\micon\MiconForm::form()
   */
  public function updateStatus($entity_type_id, MiconInterface $micon, array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (isset($element['#published_status'])) {
      $micon->setStatus($element['#published_status']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $micon = $this->entity;

    // Add a "Publish" button.
    $element['publish'] = $element['submit'];
    // If the "Publish" button is clicked, we want to update the status to
    // "published".
    $element['publish']['#published_status'] = TRUE;
    $element['publish']['#dropbutton'] = 'save';
    if ($micon->isNew()) {
      $element['publish']['#value'] = $this->t('Save and publish');
    }
    else {
      $element['publish']['#value'] = $micon->status() ? $this->t('Save and keep published') : $this->t('Save and publish');
    }
    $element['publish']['#weight'] = 0;

    // Add a "Unpublish" button.
    $element['unpublish'] = $element['submit'];
    // If the "Unpublish" button is clicked, we want to update the status to
    // "unpublished".
    $element['unpublish']['#published_status'] = FALSE;
    $element['unpublish']['#dropbutton'] = 'save';
    if ($micon->isNew()) {
      $element['unpublish']['#value'] = $this->t('Save as unpublished');
    }
    else {
      $element['unpublish']['#value'] = !$micon->status() ? $this->t('Save and keep unpublished') : $this->t('Save and unpublish');
    }
    $element['unpublish']['#weight'] = 10;

    // If already published, the 'publish' button is primary.
    if ($micon->status()) {
      unset($element['unpublish']['#button_type']);
    }
    // Otherwise, the 'unpublish' button is primary and should come first.
    else {
      unset($element['publish']['#button_type']);
      $element['unpublish']['#weight'] = -10;
    }

    // Remove the "Save" button.
    $element['submit']['#access'] = FALSE;

    $element['delete']['#access'] = $micon->access('delete');
    $element['delete']['#weight'] = 100;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $this->file = file_save_upload('file', $form['file']['#upload_validators'], FALSE, 0);

    // Ensure we have the file uploaded.
    if (!$this->file && $this->entity->isNew()) {
      $form_state->setErrorByName('file', $this->t('File to import not found.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $micon = $this->entity;

    if ($this->file) {
      try {
        $zip_path = $this->file->getFileUri();
        $micon->setArchive($zip_path);
      }
      catch (Exception $e) {
        $form_state->setErrorByName('file', $e->getMessage());
        return;
      }
    }

    $status = $micon->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Micon package.', [
          '%label' => $micon->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Micon package.', [
          '%label' => $micon->label(),
        ]));
    }
    drupal_flush_all_caches();
    $form_state->setRedirectUrl($micon->urlInfo('collection'));
  }

}
