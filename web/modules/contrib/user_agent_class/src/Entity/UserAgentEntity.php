<?php

namespace Drupal\user_agent_class\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the User agent entity entity.
 *
 * @ConfigEntityType(
 *   id = "user_agent_entity",
 *   label = @Translation("User agent"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\user_agent_class\UserAgentEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\user_agent_class\Form\UserAgentEntityForm",
 *       "edit" = "Drupal\user_agent_class\Form\UserAgentEntityForm",
 *       "delete" = "Drupal\user_agent_class\Form\UserAgentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\user_agent_class\UserAgentEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "user_agent_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/system/user-agent/{user_agent_entity}",
 *     "add-form" = "/admin/config/system/user-agent/add",
 *     "edit-form" = "/admin/config/system/user-agent/{user_agent_entity}/edit",
 *     "delete-form" = "/admin/config/system/user-agent/{user_agent_entity}/delete",
 *     "collection" = "/admin/config/system/user-agent"
 *   }
 * )
 */
class UserAgentEntity extends ConfigEntityBase implements UserAgentEntityInterface {

  /**
   * The User agent entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The User agent entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The User agent entity class.
   *
   * @var string
   */
  protected $class;

  /**
   * Enabled to check.
   *
   * @var string
   */
  protected $enableCheck;

  /**
   * Exclude to check (Because User-agent Chrome contain Safari trigger).
   *
   * @var string
   */
  protected $exclude;

  /**
   * {@inheritdoc}
   */
  public function getClassName() {
    return $this->get('class');
  }

  /**
   * {@inheritdoc}
   */
  public function setClassName($class) {
    $this->set('class', $class);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnableCheck() {
    return $this->get('enableCheck');
  }

  /**
   * {@inheritdoc}
   */
  public function setEnableCheck($enableChech) {
    $this->set('enableCheck', $enableChech);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExclude() {
    return $this->get('exclude');
  }

  /**
   * {@inheritdoc}
   */
  public function setExclude($exclude) {
    $this->set('exclude', $exclude);
    return $this;
  }

}
