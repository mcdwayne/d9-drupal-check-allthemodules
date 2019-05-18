<?php

namespace Drupal\login_notification\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\ConfigEntityType;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Define login notification.
 *
 * @ConfigEntityType(
 *   id = "login_notification",
 *   label = @Translation("Login Notification"),
 *   config_prefix = "type",
 *   admin_permission = "administer login notification",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label"
 *   },
 *   handlers = {
 *     "form" = {
 *       "add" = "\Drupal\login_notification\Form\LoginNotificationForm",
 *       "edit" = "\Drupal\login_notification\Form\LoginNotificationForm",
 *       "delete" = "\Drupal\login_notification\Form\LoginNotificationDeleteForm"
 *     },
 *     "list_builder" = "\Drupal\login_notification\Controller\LoginNotificationList",
 *     "route_provider" = {
 *       "html" = "\Drupal\login_notification\Entity\Routing\LoginNotificationRouteProvider"
 *     }
 *   },
 *   links = {
 *     "collection" = "/admin/config/system/login-notification",
 *     "add-form" = "/admin/config/system/login-notification/add",
 *     "edit-form" = "/admin/config/system/login-notification/{login_notification}",
 *     "delete-form" = "/admin/config/system/login-notification/{login_notification}/delete"
 *   }
 * )
 */
class LoginNotification extends ConfigEntityBase {

  /**
   * @var string
   */
  public $id;

  /**
   * @var string
   */
  public $label;

  /**
   * @var string
   */
  public $type = MessengerInterface::TYPE_STATUS;

  /**
   * @var string
   */
  public $message;

  /**
   * @var array
   */
  public $conditions = [];

  /**
   * @var bool
   */
  public $conditions_met_all = FALSE;

  /**
   * {@inheritdoc}
   */
  public function conditionsMetAll() {
    return $this->conditions_met_all;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($data = []) {
    return $this
      ->token()
      ->replace($this->message, $data, ['clear' => TRUE]);
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveConditions() {
    $conditions = [];

    foreach ($this->conditions as $plugin_id => $condition) {
      if (empty(array_filter($condition['configuration']))) {
        continue;
      }
      $conditions[$plugin_id] = $condition;
    }

    return $conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function render($data = []) {
    $this->messenger()->addMessage($this->getMessage($data), $this->type);
  }

  /**
   * Determine if configuration exist already.
   *
   * @param $id
   * @param array $element
   *
   * @return array|int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function entityExist($id, array $element) {
    return $this->getQuery()
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Get entity storage query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getQuery() {
    return $this->getStorage()->getQuery();
  }

  /**
   * Get entity storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  protected function getStorage() {
    return $this->entityTypeManager()
      ->getStorage($this->getEntityTypeId());
  }

  /**
   * Token object.
   *
   * @return \Drupal\Core\Utility\Token
   */
  protected function token() {
    return \Drupal::service('token');
  }

  /**
   * Messenger object.
   *
   * @return \Drupal\Core\Messenger\Messenger
   */
  protected function messenger() {
    return \Drupal::service('messenger');
  }
}
