<?php

namespace Drupal\workflow_moderation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\workflow\Entity\Workflow;
use Drupal\workflow\Entity\WorkflowState;
use Drupal\workflow_moderation\ModerationInformationInterface;
use Drupal\workflow_moderation\RevisionTracker;

/**
 * Class WorkflowModerationForm.
 */
class WorkflowModerationForm extends FormBase {

  /**
   * The moderation information service.
   *
   * @var \Drupal\workflow_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * WorkflowModerationForm constructor.
   *
   * @param \Drupal\workflow_moderation\ModerationInformationInterface $moderationInfo
   *   The moderation information service.
   */
  public function __construct(ModerationInformationInterface $moderationInfo) {
    $this->moderationInfo = $moderationInfo;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workflow_moderation.moderation_information')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_latest_revision';

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $contentId      = \Drupal::request()->get('node');
    $entity         = entity_load('node', $contentId);
    $latestRevision = $this->moderationInfo->getLatestRevision($entity->getEntityTypeId(), $entity->id());
    $currentStateId = workflow_node_current_state($latestRevision, $this->moderationInfo->getFieldName($entity));
    $workflow       = Workflow::load($this->moderationInfo->getWorkFlowId($entity));
    $currentState   = $workflow->getState($currentStateId);
    $form_state->set('entity', $latestRevision);
    // Get next state.
    $current_state = WorkflowState::load($currentStateId);
    $options       = $current_state->getOptions($latestRevision, $this->moderationInfo->getFieldName($entity));
    unset($options[$currentStateId]);
    $form['current']      = [
      '#type'     => 'item',
      '#title'    => t('Current State'),
      '#markup'   => $currentState->label,
    ];
    $form['new_state']    = [
      '#type'     => 'select',
      '#title'    => t('Moderate'),
      '#required' => TRUE,
      '#options'  => $options,
    ];
    $form['revision_log'] = [
      '#type'     => 'textfield',
      '#title'    => t('Log message'),
      '#size'     => 30,
    ];
    $form['revision_id']  = [
      '#type'     => 'item',
      '#title'    => t('Latest Revision Id'),
      '#markup'   => $latestRevision->vid->value
    ];
    $form['submit']       = [
      '#type'   => 'submit',
      '#value'  => t('Apply'),
    ];
    $nodeView = entity_view($latestRevision, 'default');
    
    $form['node_view']    = [
      '#theme'                => 'entity_latest_revision',
      '#latest_revision_view' => drupal_render($nodeView),
    ];
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var ContentEntityInterface $entity */
    $entity    = $form_state->get('entity');
    $fieldName = $this->moderationInfo->getFieldName($entity);
    $newState  = $form_state->getValue('new_state');
    $entity->set($fieldName, $newState);
    $entity->set('revision_log', $form_state->getValue('revision_log'));
    $entity->save();
    $revisionTracker = new RevisionTracker();
    $revisionTracker->setLatestRevision($entity->getEntityTypeId(), $entity->id(), $entity->language()->getId(), $entity->getRevisionId());
    drupal_set_message($this->t('The moderation state has been updated.'));
    $form_state->setRedirectUrl($entity->toUrl('canonical'));

  }

}
