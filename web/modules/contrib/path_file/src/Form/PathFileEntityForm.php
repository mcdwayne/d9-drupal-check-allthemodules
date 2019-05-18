<?php

namespace Drupal\path_file\Form;

use Drupal\path_file\Entity\PathFileEntityInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Path file entity edit forms.
 *
 * @ingroup path_file
 */
class PathFileEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\path_file\Entity\PathFileEntity */
    $form = parent::buildForm($form, $form_state);

    $form['#entity_builders']['update_status'] = [$this, 'updateStatus'];

    return $form;
  }

  /**
   * Entity builder updating the node status with the submitted value.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\path_file\Entity\PathFileEntityInterface $path_file
   *   The path_file_entity updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\path_file\Form\PathFileEntityForm::form()
   */
  public function updateStatus($entity_type_id, PathFileEntityInterface $path_file, array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (isset($element['#published_status'])) {
      $path_file->setPublished($element['#published_status']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $path_file = $this->entity;

    // If saving is an option, privileged users get dedicated form submit
    // buttons to adjust the publishing status while saving in one go.
    // @todo This adjustment makes it close to impossible for contributed
    //   modules to integrate with "the Save operation" of this form. Modules
    //   need a way to plug themselves into 1) the ::submit() step, and
    //   2) the ::save() step, both decoupled from the pressed form button.
    if (\Drupal::currentUser()->hasPermission('Administer Path file entity entities')) {
      // Add a "Publish" button.
      $element['publish'] = $element['submit'];
      // If the "Publish" button is clicked,
      // we want to update the status to "published".
      $element['publish']['#published_status'] = TRUE;
      $element['publish']['#dropbutton'] = 'save';
      if ($path_file->isNew()) {
        $element['publish']['#value'] = t('Save and publish');
      }
      else {
        $element['publish']['#value'] = $path_file->isPublished() ? t('Save and keep published') : t('Save and publish');
      }
      $element['publish']['#weight'] = 0;

      // Add a "Unpublish" button.
      $element['unpublish'] = $element['submit'];
      // If the "Unpublish" button is clicked,
      // we want to update the status to "unpublished".
      $element['unpublish']['#published_status'] = FALSE;
      $element['unpublish']['#dropbutton'] = 'save';
      if ($path_file->isNew()) {
        $element['unpublish']['#value'] = t('Save as unpublished');
      }
      else {
        $element['unpublish']['#value'] = !$path_file->isPublished() ? t('Save and keep unpublished') : t('Save and unpublish');
      }
      $element['unpublish']['#weight'] = 10;

      // If already published, the 'publish' button is primary.
      if ($path_file->isPublished()) {
        unset($element['unpublish']['#button_type']);
      }
      // Otherwise, the 'unpublish' button is primary and should come first.
      else {
        unset($element['publish']['#button_type']);
        $element['unpublish']['#weight'] = -10;
      }

      // Remove the "Save" button.
      $element['submit']['#access'] = FALSE;
    }

    $element['delete']['#access'] = $path_file->access('delete');
    $element['delete']['#weight'] = 100;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message(
            $this->t(
                'Created the %label Path file entity.', [
                  '%label' => $entity->label(),
                ]
            )
        );
        break;

      default:
        drupal_set_message(
            $this->t(
                'Saved the %label Path file entity.', [
                  '%label' => $entity->label(),
                ]
            )
        );
    }
    $form_state->setRedirect('entity.path_file_entity.collection');
  }

}
