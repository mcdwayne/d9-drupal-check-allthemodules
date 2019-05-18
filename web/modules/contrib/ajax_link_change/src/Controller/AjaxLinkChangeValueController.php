<?php

namespace Drupal\ajax_link_change\Controller;

use Drupal\ajax_link_change\Ajax\AjaxLinkChangeCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class AjaxLinkChangeValueController.
 */
class AjaxLinkChangeValueController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * Load the object Entity.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The current user who send request.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * The current user request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */

  protected $request;

  /**
   * The log module.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */

  protected $log;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Load the object Entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Load the current request.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $log
   *   Log the error.
   */
  public function __construct(EntityTypeManager $entityTypeManager, AccountInterface $user, RequestStack $request, LoggerChannelFactory $log) {
    $this->entityTypeManager = $entityTypeManager;
    $this->user = $user;
    $this->request = $request;
    $this->log = $log;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entityTypeManager = $container->get('entity_type.manager');
    $user = $container->get('current_user');
    $request = $container->get('request_stack');
    $log = $container->get('logger.factory');
    return new static($entityTypeManager, $user, $request, $log);
  }

  /**
   * Set the value of field.
   *
   * @param string $entity_type
   *   the Type of Entity.
   * @param int $entity_id
   *   The ID of Entity .
   * @param string $field_name
   *   The name od field how wile be edited.
   * @param string $value_ON
   *   The value of field when he was on ON state.
   * @param string $value_OFF
   *   The value of field when he was on OFF state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A Object .
   */
  public function ajaxLinkChange($entity_type, $entity_id, $field_name, $value_ON, $value_OFF) {
    // Check origin of request.
    $is_ajax = $this->request->getCurrentRequest()->isXmlHttpRequest();
    if (!$is_ajax) {
      throw new AccessDeniedHttpException();
    }
    $ajax_Response = new AjaxResponse();
    $returnValue = NULL;
    $currentValue = NULL;
    try {
      $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
      // The entity doesn't exist.
      if ($entity == NULL) {
        throw new \Exception($this->t("No entity content"));
      }
      // The user doesn't have permission to edit the entity.
      if ($entity->access('update', $this->user) == FALSE && $entity->access('edit', $this->user) == FALSE) {
        throw new \Exception($this->t("You don't have the access to edit"));
      }
      // The field doesn't exist.
      if ($entity->hasField($field_name) == FALSE) {
        throw new \Exception($this->t("Check the field existence"));
      }
      // The field not accessible for the user.
      if ($entity->{$field_name}->access('edit', $this->user) == FALSE) {
        throw new \Exception($this->t("Check  the field access"));
      }

      // Get the type of field.
      $field_type = $entity->get($field_name)->getFieldDefinition()->getType();
      if ($field_type == 'entity_reference') {
        $currentValue = $entity->get($field_name)->target_id;
      }
      else {
        $currentValue = $entity->get($field_name)->value;
      }

      // If value equal of $value_ON.
      if ($currentValue == $value_ON) {
        // The value of field take the value of  $value_OFF.
        $entity->{$field_name}->setValue($value_OFF);
        $returnValue = $value_OFF;
      }
      else {
        // The value of field take the value of  $value_ON.
        $entity->{$field_name}->setValue($value_ON);
        $returnValue = $value_ON;
      }
      // Save the entity.
      $entity->save();
      $ajax_Response->addCommand(new AjaxLinkChangeCommand($returnValue));
    }
    catch (\Exception $e) {
      $ajax_Response->addCommand(new AjaxLinkChangeCommand($currentValue));
      $this->log->get("Ajax Link Change")->error($e->getMessage());
    }

    return $ajax_Response;
  }

}
