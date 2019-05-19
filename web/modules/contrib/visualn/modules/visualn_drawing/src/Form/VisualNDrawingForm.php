<?php

namespace Drupal\visualn_drawing\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for VisualN Drawing edit forms.
 *
 * @ingroup visualn_drawing
 */
class VisualNDrawingForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // @todo: see NodeForm::form()

    $form = parent::form($form, $form_state);

    $form['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#title' => t('Settings'),
      '#weight' => 100,
    );



    if (!empty($form['thumbnail'])) {
      $form['thumbnail_settings'] = [
        '#type' => 'details',
        '#title' => t('Thumbnail Settings'),
        '#group' => 'advanced',
      ];
      $form['thumbnail']['#group'] = 'thumbnail_settings';
    }
    // @todo: though Node::form() just checks isset($form['uid'])
    if (isset($form['user_id']) && $form['user_id']['#access']) {
      $form['author'] = [
	'#type' => 'details',
	'#title' => t('Authoring infromation'),
	'#group' => 'advanced',
      ];
      $form['user_id']['#group'] = 'author';
    }
    if (isset($form['user_id']) && $form['user_id']['#access']) {
      $form['base'] = [
        '#type' => 'details',
        '#title' => t('Base Settings'),
        '#group' => 'advanced',
      ];
      $form['status']['#group'] = 'base';
    }
    $form['revision_settings'] = [
      '#type' => 'details',
      '#title' => t('Revisioning infromation'),
      '#group' => 'advanced',
    ];


    $form['revision_log_message']['#group'] = 'revision_settings';


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\visualn_drawing\Entity\VisualNDrawing */
    $form = parent::buildForm($form, $form_state);

    // @todo: seems that this differs from the standard way of attaching the field
    //   see ContentEntityForm::addRevisionableFormFields()
    // @todo: add access check
    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        // @todo: review default value
        '#default_value' => FALSE,
        '#weight' => 10,

        '#group' => 'revision_settings',
        // @todo: reuse #access from revision_log_message element as a temporary solution
        '#access' => $form['revision_log_message']['#access'],
      ];
      $form['revision_log_message']['#states'] = [
        'visible' => [
          ':input[name="new_revision"]' => [
            'checked' => TRUE,
          ],
        ],
      ];

      if (!$form['revision_log_message']['#access']) {
        $form['revision_settings']['#access'] = FALSE;
      }
    }
    elseif ($form['revision_log_message'] || !$form['revision_log_message']['#access']) {
      $form['revision_settings']['#access'] = FALSE;
    }

    $entity = $this->entity;

    return $form;
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

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN Drawing.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN Drawing.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.visualn_drawing.canonical', ['visualn_drawing' => $entity->id()]);
  }

}
