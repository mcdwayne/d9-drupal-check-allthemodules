<?php

namespace Drupal\workflow_notifications\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\workflow\WorkflowTypeAttributeTrait;

/**
 *  Defines a Workflow Notification entity
 *
 * @ConfigEntityType(
 *   id = "workflow_notify",
 *   label = @Translation("Workflow Notification"),
 *   handlers = {
 *     "access" = "Drupal\workflow_notifications\WorkflowNotificationControlHandler",
 *     "list_builder" = "Drupal\workflow_notifications\Controller\WorkflowNotificationListBuilder",
 *     "form" = {
 *       "add" = "Drupal\workflow_notifications\Form\WorkflowNotificationForm",
 *       "edit" = "Drupal\workflow_notifications\Form\WorkflowNotificationForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   config_prefix = "workflow_notify",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "wid" = "wid",
 *     "from_sid" = "from_sid",
 *     "to_sid" = "to_sid",
 *     "when_to_trigger" = "when_to_trigger",
 *     "mail_to" = "mail_to",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "wid",
 *     "from_sid",
 *     "to_sid",
 *     "when_to_trigger",
 *     "days",
 *     "roles",
 *     "mail_ids",
 *     "subject",
 *     "message",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/workflow/workflow/{workflow_type}/notifications/{workflow_notify}/edit",
 *     "delete-form" = "/admin/config/workflow/workflow/{workflow_type}/notifications/{workflow_notify}/delete",
 *     "collection" = "/admin/config/workflow/workflow/{workflow_type}/notifications",
 *   },
 * )
 */
class WorkflowNotification extends ConfigEntityBase implements WorkflowNotificationInterface {

  /*
   * Add variables and get/set methods for Workflow property.
   */
  use WorkflowTypeAttributeTrait;

  public $id;
  public $label;

  public $from_sid = '';
  public $to_sid = '';
  public $when_to_trigger = 'on_state_change';
  public $days = 0;
  public $roles = [];
  public $mail_ids = [];
  public $subject;
  public $message = ['value' => '', 'format' => 'basic_html',];

  /**
   * {@inheritdoc}
   */
  public static function loadMultipleByProperties($from_sid, $to_sid, $wid, $trigger, $days) {
    $result = \Drupal::entityQuery("workflow_notify");
    $from_state = $result->orConditionGroup()
      ->condition('from_sid', $from_sid, '=')
      ->condition('from_sid', '', '=');
    $to_state = $result->orConditionGroup()
      ->condition('to_sid', $to_sid, '=')
      ->condition('to_sid', '', '=');
    $result->condition($from_state)
      ->condition($to_state)
      ->condition('wid', $wid, '=')
      ->condition('when_to_trigger', $trigger, '=');
    if (!empty($days)) {
      $result->condition('days', $days, '=');
    }

    $ids = $result->execute();
    $workflow_notifications = self::loadMultiple($ids);
    return $workflow_notifications;
  }

  /**
   * {@inheritdoc}
   */
  public function urlInfo($rel = 'canonical', array $options = []) {
    // This function is deprecated, so add modification in other function.
    return $this::toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    // Perhaps this can be done in routing.yml file.
    $url = parent::toUrl($rel, $options);
    $url->setRouteParameter('workflow_type', $this->getWorkflowId());
    return $url;
  }

}
