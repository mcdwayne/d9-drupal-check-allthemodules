<?php

namespace Drupal\workflow_sms_notify\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\workflow\WorkflowTypeAttributeTrait;
use Drupal\workflow_notifications\Entity\WorkflowNotificationInterface;

/**
 * @configEntityType(
 *   id = "workflow_sms_notify",
 *   label = @Translation("Workflow SMS Notifications"),
 *   handlers = {
 *     "access" = "Drupal\workflow_notifications\WorkflowNotificationControlHandler",
 *     "list_builder" = "Drupal\workflow_sms_notify\Controller\WorkflowSmsNotificationListBuilder",
 *     "form" = {
 *       "add" = "Drupal\workflow_sms_notify\Form\WorkflowSmsNotificationForm",
 *       "edit" = "Drupal\workflow_sms_notify\Form\WorkflowSmsNotificationForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   config_prefix = "workflow_sms_notify",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "wid" = "wid",
 *     "from_sid" = "from_sid",
 *     "to_sid" = "to_sid",
 *     "when_to_trigger" = "when_to_trigger",
 *     "days" = "days",
 *     "roles" = "roles",
 *     "phone_num" = "phone_num",
 *     "subject" = "subject",
 *     "message" = "message",
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
 *     "phone_num",
 *     "subject",
 *     "message",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/workflow/workflow/{workflow_type}/sms-notifications/{workflow_sms_notify}/edit",
 *     "delete-form" = "/admin/config/workflow/workflow/{workflow_type}/sms-notifications/{workflow_sms_notify}/delete",
 *     "collection" = "/admin/config/workflow/workflow/{workflow_type}/sms-notifications",
 *   },
 * )
 */

 class WorkflowSmsNotify extends ConfigEntityBase implements WorkflowNotificationInterface {
     
   /*
   * Add variables and get/set methods for Workflow property.
   */
    use WorkflowTypeAttributeTrait;
    public $id;
    public $label;
    public $from_sid;
    public $to_sid;
    public $when_to_trigger = 'on_state_change';
    public $days = 0;
    public $roles = [];
    public $phone_num = [];
    public $subject;
    public $message = ['value' => '', 'format' => 'basic_html',];

    public static function loadMultipleByProperties($from_sid, $to_sid, $wid, $trigger, $days) {
      $result = \Drupal::entityQuery("workflow_sms_notify");
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
      $workflow_sms_notifications = self::loadMultiple($ids);
      return $workflow_sms_notifications;
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