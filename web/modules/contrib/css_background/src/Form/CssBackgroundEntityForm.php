<?php

namespace Drupal\css_background\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for CssBackground edit forms.
 *
 * @ingroup css_background
 */
class CssBackgroundEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Attach the JS.
    $form['#attached']['library'][] = 'css_background/edit_form';

    return $form;
  }

  /**
   * Alter the for for IEF.
   */
  public static function alterInlineEntityForm(array &$form, FormStateInterface $form_state) {
    // Hide housekeeping properites from IEF.
    $form['revision_log_message']['#access'] = FALSE;
    $form['user_id']['#access'] = FALSE;
    $form['created']['#access'] = FALSE;

    // Attach the JS.
    $form['#attached']['library'][] = 'css_background/edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(REQUEST_TIME);
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    $form_state->setRedirect('entity.css_background.canonical', ['css_background' => $entity->id()]);
  }

}
