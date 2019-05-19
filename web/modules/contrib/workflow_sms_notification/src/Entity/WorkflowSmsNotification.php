<?php

namespace Drupal\workflow_sms_notification\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\workflow\Entity\Workflow;

/**
 * Workflow configuration entity to persistently store configuration.
 *
 * @ConfigEntityType(
 *   id = "workflow_sms_notification",
 *   label = @Translation("Workflow SMS notification"),
 *   label_singular = @Translation("Workflow SMS notification"),
 *   label_plural = @Translation("Workflow SMS notifications"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Workflow SMS notification",
 *     plural = "@count Workflow SMS notifications",
 *   ),
 *   module = "workflow_sms_notification",
 *   handlers = {
 *     "list_builder" = "Drupal\workflow_sms_notification\Controller\WorkflowSmsNotificationListBuilder",
 *     "form" = {
 *        "add" = "Drupal\workflow_sms_notification\Form\WorkflowSmsNotificationForm",
 *        "edit" = "Drupal\workflow_sms_notification\Form\WorkflowSmsNotificationForm",
 *        "delete" = "Drupal\workflow_sms_notification\Form\WorkflowSmsNotificationDeleteForm",
 *      }
 *   },
 *   admin_permission = "administer workflow",
 *   config_prefix = "workflow_sms_notification",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id",
 *     "wid",
 *     "state",
 *     "message",
 *     "roles",
 *     "author",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/workflow/workflow/{workflow_type}",
 *     "collection" = "/admin/config/workflow/workflow/{workflow_type}/sms_notifications",
 *     "add-form" = "/admin/config/workflow/workflow/{workflow_type}/sms_notifications/add",
 *     "edit-form" = "/admin/config/workflow/workflow/sms_notifications/{workflow_notification}/edit",
 *     "delete-form" = "/admin/config/workflow/workflow/sms_notifications/{workflow_notification}/delete",
 *   },
 * )
 */
class WorkflowSmsNotification extends ConfigEntityBase {

  /**
   * The machine name.
   *
   * @var string
   */
  public $id;

  /**
   * The attached Workflow state id.
   *
   * @var int
   */
  protected $state;

  /**
   * The machine_name of the attached Workflow.
   *
   * @var string
   */
  protected $wid;

  /**
   * The attached Workflow.
   *
   * @var Workflow
   */
  protected $workflow;

  /**
   * Roles to send to.
   *
   * @var string[]
   */
  protected $roles = [];


  /**
   * Send to entity owner.
   *
   * @var bool
   */
  protected $author = FALSE;

  /**
   * Body with value and format keys.
   *
   * @var string[]
   */
  protected $message = [];

  /**
   * CRUD functions.
   */

  /**
   * Returns the Workflow ID of this SMS Notification.
   *
   * @return string
   *   Workflow Id.
   */
  public function getWorkflowId() {
    return $this->wid;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkflowId($wid) {
    $this->wid = $wid;
    return $this;
  }

  /**
   * Returns the Workflow object of this State.
   *
   * @return Workflow
   *   Workflow object.
   */
  public function getWorkflow() {
    if (!isset($this->workflow)) {
      $this->workflow = Workflow::load($this->wid);
    }
    return $this->workflow;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkflow(Workflow $workflow) {
    $this->wid = $workflow->id();
    $this->workflow = $workflow;
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthor() {
    return $this->author;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->state;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    return $this->roles;
  }

  /**
   * {@inheritdoc}
   */
  public function setRoles($roles) {
    $this->roles = $roles;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage(array $message) {
    $this->message = $message;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthor($author) {
    $this->author = $author;
    return $this;
  }

}
