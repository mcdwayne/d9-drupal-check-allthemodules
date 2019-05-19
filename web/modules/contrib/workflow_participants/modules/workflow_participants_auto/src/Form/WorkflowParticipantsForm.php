<?php

namespace Drupal\workflow_participants_auto\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Form controller for automatic Workflow participants.
 *
 * @ingroup workflow_participants
 */
class WorkflowParticipantsForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\workflows\WorkflowInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $fields = ['editors' => $this->t('Editors'), 'reviewers' => $this->t('Reviewers')];
    foreach ($fields as $id => $label) {
      $users = $this->entity->getThirdPartySetting('workflow_participants_auto', $id, []);
      $form[$id] = [
        '#type' => 'entity_autocomplete',
        '#title' => $label,
        '#description' => $this->t('Enter a comma separated list of user names.'),
        '#target_type' => 'user',
        // The entity autocomplete element only allows 128 characters, which
        // won't be enough if you have more than a couple participants.
        '#maxlength' => NULL,
        '#size' => 128,
        '#selection_settings' => [
          'include_anonymous' => FALSE,
          'include_blocked'   => FALSE,
        ],
        '#tags' => TRUE,
        '#default_value' => User::loadMultiple($users),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $id => $users) {
      $uids = [];
      if (!empty($users)) {
        foreach ($users as $user) {
          $uids[] = $user['target_id'];
        }
      }
      $this->entity->setThirdPartySetting('workflow_participants_auto', $id, $uids);
    }
    $this->entity->save();
  }

}
