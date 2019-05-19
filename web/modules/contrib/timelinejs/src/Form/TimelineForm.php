<?php

namespace Drupal\timelinejs\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Timeline edit forms.
 *
 * @ingroup timelinejs
 */
class TimelineForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\timelinejs\Entity\Timeline */
    $entity = $this->entity;

    $form = parent::buildForm($form, $form_state);

    // Define advanced form region.
    $form['advanced'] = [
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    ];

    // Define revision information section.
    $form['revision_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Revision information'),
      // Open by default when "Create new revision" is checked.
      '#open' => TRUE,
      '#group' => 'advanced',
      '#weight' => 20,
      '#access' => TRUE,
    ];

    $form['revision_log_message']['#group'] = 'revision_information';
    $form['revision_log_message']['#states'] = [
      'visible' => [
        ':input[name="new_revision"]' => ['checked' => TRUE],
      ],
    ];

    if (!$entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#group' => 'revision_information',
        '#weight' => 10,
      ];
    }

    $form['author_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Author information'),
      '#group' => 'advanced',
      '#weight' => 20,
      '#access' => TRUE,
    ];

    if (isset($form['user_id'])) {
      $form['user_id']['#group'] = 'author_information';
    }

    // TimelineJS Settings container.
    $form['timelinejs_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('TimelineJS Settings'),
      '#weight' => 10,
    ];

    if (isset($form['scale'])) {
      $form['scale']['#group'] = 'timelinejs_settings';
    }

    // Use the color input element.
    $form['default_bg_color']['widget'][0]['value']['#type'] = 'color';

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
        drupal_set_message($this->t('Created the %label Timeline.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Timeline.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.timeline.canonical', ['timeline' => $entity->id()]);
  }

}
