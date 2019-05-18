<?php

namespace Drupal\box\Form;

use Drupal\box\Entity\BoxType;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Box edit forms.
 *
 * @ingroup box
 */
class BoxForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\box\Entity\BoxInterface $box */
    $box = $this->entity;

    $form = parent::buildForm($form, $form_state);

    if (!$box->isNew()) {
      $form['machine_name']['#disabled'] = TRUE;
    }

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit @type</em> @title', [
        '@type' => $box->bundleLabel(),
        '@title' => $box->label()
      ]);
    }

    $form['advanced'] = [
      '#type' => 'container',
      '#weight' => 99,
      '#attributes' => [
        'class' => ['box-form-advanced', 'entity-meta'],
      ],
    ];

    $form['meta'] = [
      '#type' => 'container',
      '#group' => 'advanced',
      '#weight' => -10,
      '#attributes' => ['class' => ['entity-meta__header']],
      '#tree' => TRUE,
    ];
    $form['meta']['published'] = [
      '#type' => 'item',
      '#markup' => $box->isPublished() ? $this->t('Published') : $this->t('Not published'),
      '#access' => !$box->isNew(),
      '#wrapper_attributes' => ['class' => ['entity-meta__title']],
    ];
    $form['meta']['changed'] = [
      '#type' => 'item',
      '#title' => $this->t('Last saved'),
      '#markup' => !$box->isNew() ? \Drupal::service('date.formatter')->format($box->getChangedTime(), 'short') : $this->t('Not saved yet'),
      '#wrapper_attributes' => ['class' => ['entity-meta__last-saved', 'container-inline']],
    ];
    $form['meta']['author'] = [
      '#type' => 'item',
      '#title' => $this->t('Author'),
      '#markup' => $box->getOwner()->getDisplayName(),
      '#wrapper_attributes' => ['class' => ['entity-meta__author', 'container-inline']],
    ];

    $form['revision_information']['#type'] = 'container';
    $form['revision_information']['#group'] = 'meta';

    /** @var \Drupal\box\Entity\BoxTypeInterface $box_type */
    $box_type = BoxType::load($box->bundle());
    if ($box_type->isRevisionLogRequired()) {
      /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
      $entity_type = $box->getEntityType();
      $override_revision_settings = $box->get($entity_type->getKey('revision'))->access('update');
      if (!$override_revision_settings) {
        // Get log message field's key from definition.
        $log_message_field = $entity_type->getRevisionMetadataKey('revision_log_message');
        if ($log_message_field && isset($form[$log_message_field])) {
          $form[$log_message_field]['widget'][0]['value']['#required'] = TRUE;
          unset($form[$log_message_field]['#states']);
          $form['revision_information']['#access'] = TRUE;
        }
      }
    }

    $form['#theme'] = ['box_edit_form'];
    $form['#attached']['library'][] = 'box/drupal.box.form_box';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\box\Entity\BoxInterface $box */
    $box = &$this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $box->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $box->setRevisionCreationTime(REQUEST_TIME);
      $box->setRevisionUserId(\Drupal::currentUser()->id());
    }
    else {
      $box->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    $args = [
      '@type' => $box->bundleLabel(),
      '%title' => $box->label(),
    ];

    switch ($status) {
      case SAVED_NEW:
        $this->logger('box')->notice('@type: added %title.', $args);
        drupal_set_message($this->t('@type box %title has been created.', $args));
        break;

      default:
        $this->logger('box')->notice('@type: updated %title.', $args);
        drupal_set_message($this->t('Box %title has been updated.', $args));
    }
    $form_state->setRedirect('entity.box.collection', ['box' => $box->id()]);
    return $status;
  }

}
