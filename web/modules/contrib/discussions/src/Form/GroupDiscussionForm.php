<?php

namespace Drupal\discussions\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupContent;

/**
 * Form controller for creating a discussion in a group.
 *
 * @ingroup discussions
 */
class GroupDiscussionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['comment'] = [
      '#type' => 'text_format',
      '#title' => t('Initial Comment'),
      '#cols' => 60,
      '#resizable' => TRUE,
      '#rows' => 5,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $user = \Drupal::currentUser();

    // Set user ID property of the discussion.
    $entity->set('uid', $user->id());

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        // Add discussion to group.
        $group = $form_state->get('group');
        $plugin = $form_state->get('plugin');

        $group_content = GroupContent::create([
          'type' => $plugin->getContentTypeConfigId(),
          'gid' => $group->id(),
        ]);

        $group_content->set('entity_id', $entity->id());

        $group_content->save();

        // Add initial comment to discussion.
        /** @var \Drupal\discussions\GroupDiscussionService $group_discussion_service */
        $group_discussion_service = \Drupal::service('discussions.group_discussion');
        $group_discussion_service->addComment($entity->id(), 0, $user->id(), $form_state->getValue('comment'));

        drupal_set_message($this->t('Created %label.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved %label.', [
          '%label' => $entity->label(),
        ]));
    }

    $form_state->setRedirect('entity.group.canonical', [
      'group' => $group->id(),
    ]);
  }

}
