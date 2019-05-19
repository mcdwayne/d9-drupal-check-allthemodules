<?php

namespace Drupal\webfactory_master\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\webfactory_master\SatelliteEntityInterface;

/**
 * Defines the Satellite entity.
 *
 * @ConfigEntityType(
 *   id = "satellite_entity",
 *   label = @Translation("Satellite entity"),
 *   handlers = {
 *     "list_builder" = "Drupal\webfactory_master\SatelliteEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webfactory_master\Form\SatelliteEntityForm",
 *       "edit" = "Drupal\webfactory_master\Form\SatelliteEntityForm",
 *       "delete" = "Drupal\webfactory_master\Form\SatelliteEntityDeleteForm",
 *       "deploy" = "Drupal\webfactory_master\Form\SatelliteEntityDeployForm"
 *     }
 *   },
 *   config_prefix = "satellite_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/webfactory/satellite_entity/{satellite_entity}",
 *     "edit-form" = "/admin/config/services/webfactory/satellite_entity/{satellite_entity}/edit",
 *     "delete-form" = "/admin/config/services/webfactory/satellite_entity/{satellite_entity}/delete",
 *     "deploy-form" = "/admin/config/services/webfactory/satellite_entity/{satellite_entity}/deploy",
 *     "collection" = "/admin/config/services/webfactory/satellite_entity"
 *   }
 * )
 */
class SatelliteEntity extends ConfigEntityBase implements SatelliteEntityInterface {

  const NOT_DEPLOYED = 'NOT_DEPLOYED';
  const DEPLOYED     = 'DEPLOYED';
  const PENDING      = 'PENDING';

  /**
   * The Satellite entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Satellite entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Satellite entity host.
   *
   * @var string
   */
  protected $host;

  /**
   * The Satellite entity directory.
   *
   * @var string
   */
  protected $directory;

  /**
   * The Satellite entity installation profile.
   *
   * @var string
   */
  protected $profile;

  /**
   * Store deploy status.
   *
   * @var string
   */
  protected $status;

  /**
   * The Satellite entity theme.
   *
   * @var string
   */
  protected $theme;

  /**
   * The Satellite entity language.
   *
   * @var string
   */
  protected $language;

  /**
   * The Satellite entity mail.
   *
   * @var string
   */
  protected $mail;

  /**
   * The Satellite entity site name.
   *
   * @var string
   */
  protected $siteName;

  /**
   * The Satellite entity database driver.
   *
   * @var string
   */
  protected $dbDriver;

  /**
   * The Satellite entity database username.
   *
   * @var string
   */
  protected $dbUsername;

  /**
   * The Satellite entity database password.
   *
   * @var string
   */
  protected $dbPassword;

  /**
   * The Satellite entity database host.
   *
   * @var string
   */
  protected $dbHost;

  /**
   * The Satellite entity database port.
   *
   * @var string
   */
  protected $dbPort;

  /**
   * The Satellite entity database name.
   *
   * @var string
   */
  protected $dbDatabase;

  /**
   * The Satellite entity prefix name.
   *
   * @var string
   */
  protected $dbPrefix;

  /**
   * Return associated user.
   *
   * @return \Drupal\user\Entity\User|null
   *   Get a user.
   */
  public function getWsUser() {
    $user = NULL;

    $ws_user = $this->get('wsUser');
    $entities = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $ws_user]);

    if (!empty($entities)) {
      $user = array_pop($entities);
    }

    return $user;
  }

  /**
   * Remove user associated with satellite.
   *
   * @param EntityStorageInterface $storage
   *   The storage object.
   * @param array $entities
   *   List of satellite entities.
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    foreach ($entities as $entity) {
      if ($entity->isUninstalling() || $entity->isSyncing()) {
        // During extension uninstall and configuration synchronization
        // deletions are already managed.
        break;
      }
      // Delete ws user.
      $user = $entity->getWsUser();
      if (!is_null($user)) {
        $user->delete();
      }

      // Delete token from security config.
      // TODO: centralize this kind of operation.
      /** @var \Drupal\Core\Config\Config $security_config */
      $security_config = \Drupal::service('config.factory')->getEditable('webfactory_master.security');
      $new_satellites = $satellites = $security_config->get('satellites');
      foreach ($satellites as $id => $data) {
        if ($id == $entity->id()) {
          unset($new_satellites[$id]);
        }
      }
      $security_config->set('satellites', $new_satellites)->save();
    }
  }

  /**
   * Check status is deployed.
   *
   * @return bool
   *   True if is deployed, False otherwise.
   */
  public function isDeployed() {
    return $this->status === self::DEPLOYED;
  }

  /**
   * Check status is pending.
   *
   * @return bool
   *   True if is pending, False otherwise.
   */
  public function isPending() {
    return $this->status == self::PENDING;
  }

  /**
   * Set status as pending.
   */
  public function markAsPending() {
    $this->status = self::PENDING;
  }

  /**
   * Set status as deployed.
   */
  public function markAsDeployed() {
    $this->status = self::DEPLOYED;
  }

}
