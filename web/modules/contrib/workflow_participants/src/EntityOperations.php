<?php

namespace Drupal\workflow_participants;

use Drupal\content_moderation_notifications\ContentModerationNotificationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workflows\WorkflowInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A class for reacting to entity operations.
 */
class EntityOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the entity operations class.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Alter the workflow transition form to add 3rd-party settings.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function alterWorkflowTransitionsForm(array &$form, FormStateInterface $form_state) {
    // Add editor and reviewer checkboxes.
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $form_state->getFormObject()->getEntity();
    $transition_id = isset($form['id']['#value']) ? $form['id']['#value'] : '';
    $form['editor_transitions'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow editors to make this transition'),
      '#default_value' => in_array($transition_id, $workflow->getThirdPartySetting('workflow_participants', 'editor_transitions', [])),
    ];
    $form['reviewer_transitions'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow reviewers to make this transition'),
      '#default_value' => in_array($transition_id, $workflow->getThirdPartySetting('workflow_participants', 'reviewer_transitions', [])),
    ];
    $form['#entity_builders'][] = static::class . '::workflowTransitionsFormBuilder';
  }

  /**
   * Form builder for moderation state transitions.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   The workflow entity.
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @see static::alterWorkflowTransitionsForm
   */
  public static function workflowTransitionsFormBuilder($entity_type, WorkflowInterface $workflow, array &$form, FormStateInterface $form_state) {
    $transition_id = $form_state->getValue('id');
    foreach (['editor_transitions', 'reviewer_transitions'] as $enabled) {
      $transitions = $workflow->getThirdPartySetting('workflow_participants', $enabled, []);
      if ($form_state->getValue($enabled)) {
        $transitions[$transition_id] = $transition_id;
      }
      else {
        unset($transitions[$transition_id]);
      }
      $workflow->setThirdPartySetting('workflow_participants', $enabled, $transitions);
    }
  }

  /**
   * Add 3rd-party settings for notifications.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function alterNotificationsForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\content_moderation_notifications\ContentModerationNotificationInterface $content_moderation_notification */
    $content_moderation_notification = $form_state->getFormObject()->getEntity();

    // Add participant selection.
    $defaults = [
      'editors' => $content_moderation_notification->getThirdPartySetting('workflow_participants', 'editors', FALSE),
      'reviewers' => $content_moderation_notification->getThirdPartySetting('workflow_participants', 'reviewers', FALSE),
    ];
    $form['workflow_participants'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Workflow participants'),
      '#description' => $this->t('Send this notification to workflow participants of the selected type(s).'),
      '#options' => [
        'editors' => $this->t('Editors'),
        'reviewers' => $this->t('Reviewers'),
      ],
      '#default_value' => array_keys(array_filter($defaults)),
    ];
    $form['#entity_builders'][] = static::class . '::notificationFormBuilder';
  }

  /**
   * Form builder for content moderation notification entities.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param \Drupal\content_moderation_notifications\ContentModerationNotificationInterface $notification
   *   The notification entity.
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function notificationFormBuilder($entity_type, ContentModerationNotificationInterface $notification, array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('workflow_participants') as $key => $value) {
      $notification->setThirdPartySetting('workflow_participants', $key, (bool) $value);
    }
  }

  /**
   * Alter content moderation notification recipients to add participants.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity transitioning states.
   * @param array $data
   *   The content moderation notifications data.
   */
  public function alterNotificationRecipients(EntityInterface $entity, array &$data) {
    /** @var \Drupal\content_moderation_notifications\ContentModerationNotificationInterface $notification */
    $notification = $data['notification'];
    if ($notification->getThirdPartySetting('workflow_participants', 'reviewers', FALSE) || $notification->getThirdPartySetting('workflow_participants', 'editors', FALSE)) {
      /** @var \Drupal\workflow_participants\Entity\WorkflowParticipantsInterface $participants */
      $participants = $this->entityTypeManager->getStorage('workflow_participants')->loadForModeratedEntity($entity);
      if ($notification->getThirdPartySetting('workflow_participants', 'editors', FALSE)) {
        foreach ($participants->getEditors() as $account) {
          $data['to'][] = $account->getEmail();
        }
      }
      if ($notification->getThirdPartySetting('workflow_participants', 'reviewers', FALSE)) {
        foreach ($participants->getReviewers() as $account) {
          $data['to'][] = $account->getEmail();
        }
      }
    }
  }

}
