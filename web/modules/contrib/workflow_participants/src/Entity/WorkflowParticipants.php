<?php

namespace Drupal\workflow_participants\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeAccessControlHandlerInterface;
use Drupal\workflows\TransitionInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Defines the Workflow participants entity.
 *
 * @ingroup workflow_participants
 *
 * @ContentEntityType(
 *   id = "workflow_participants",
 *   label = @Translation("Workflow participants"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\workflow_participants\Form\WorkflowParticipantsForm"
 *     },
 *     "access" = "Drupal\workflow_participants\WorkflowParticipantsAccessControlHandler",
 *     "storage" = "Drupal\workflow_participants\WorkflowParticipantsStorage",
 *     "storage_schema" = "Drupal\workflow_participants\WorkflowParticipantsStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   base_table = "workflow_participants",
 *   admin_permission = "administer workflow participants",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class WorkflowParticipants extends ContentEntityBase implements WorkflowParticipantsInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['moderated_entity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Moderated entity'))
      ->setDescription(t('The entity being moderated.'))
      ->setCardinality(1);

    $fields['editors'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Editors'))
      ->setDescription(t('Users that can edit the item being moderated.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default:user')
      ->setSetting('handler_settings', [
        'include_anonymous' => FALSE,
        'include_blocked'   => FALSE,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 3,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 40,
          'placeholder' => '',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -6,
      ]);

    $fields['reviewers'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Reviewers'))
      ->setDescription(t('Users that can review the item being moderated.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default:user')
      ->setSetting('handler_settings', [
        'include_anonymous' => FALSE,
        'include_blocked'   => FALSE,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 3,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 40,
          'placeholder' => '',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -6,
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getModeratedEntity() {
    return $this->get('moderated_entity')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditors() {
    $editors = [];
    foreach ($this->get('editors') as $editor) {
      if ($editor->target_id) {
        $editors[$editor->target_id] = $editor->entity;
      }
    }
    return $editors;
  }

  /**
   * {@inheritdoc}
   */
  public function getReviewers() {
    $reviewers = [];
    foreach ($this->get('reviewers') as $reviewer) {
      if ($reviewer->target_id) {
        $reviewers[$reviewer->target_id] = $reviewer->entity;
      }
    }
    return $reviewers;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Filter to unique editors and reviewers.
    $this->set('editors', array_values($this->getEditors()));
    $this->set('reviewers', array_values($this->getReviewers()));
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Check if entity grant records need updating.
    $access_handler = $this->entityTypeManager()->getAccessControlHandler($this->getModeratedEntity()->getEntityTypeId());
    if ($access_handler instanceof NodeAccessControlHandlerInterface) {
      $access_handler->writeGrants($this->getModeratedEntity());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEditorIds() {
    $editors = [];
    foreach ($this->get('editors') as $editor) {
      if ($editor->target_id) {
        $editors[$editor->target_id] = $editor->target_id;
      }
    }
    return $editors;
  }

  /**
   * {@inheritdoc}
   */
  public function getReviewerIds() {
    $reviewers = [];
    foreach ($this->get('reviewers') as $reviewer) {
      if ($reviewer->target_id) {
        $reviewers[$reviewer->target_id] = $reviewer->target_id;
      }
    }
    return $reviewers;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), $this->getModeratedEntity()->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function userMayTransition(WorkflowInterface $workflow, TransitionInterface $transition, AccountInterface $account) {
    // Allowed state transitions are stored on the workflow entity, since the
    // transition object isn't a config entity.
    $editor_transitions = $workflow->getThirdPartySetting('workflow_participants', 'editor_transitions', []);
    $reviewer_transitions = $workflow->getThirdPartySetting('workflow_participants', 'reviewer_transitions', []);

    return (
      // Editors can make this transition, and account is an editor.
      (in_array($transition->id(), $editor_transitions) && $this->isEditor($account))
      // Reviewers can make this transition, and the account is a reviewer.
      || (in_array($transition->id(), $reviewer_transitions) && $this->isReviewer($account)));
  }

  /**
   * {@inheritdoc}
   */
  public function isEditor(AccountInterface $account) {
    return in_array($account->id(), $this->getEditorIds());
  }

  /**
   * {@inheritdoc}
   */
  public function isReviewer(AccountInterface $account) {
    return in_array($account->id(), $this->getReviewerIds());
  }

}
