<?php

namespace Drupal\contacts_dbs\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the DBS Status configuration entity.
 *
 * @ConfigEntityType(
 *   id = "dbs_workforce",
 *   label = @Translation("DBS workforce"),
 *   label_collection = @Translation("DBS workforces"),
 *   label_singular = @Translation("DBS workforce"),
 *   label_plural = @Translation("DBS workforces"),
 *   label_count = @PluralTranslation(
 *     singular = "@count DBS workforce",
 *     plural = "@count DBS workforces"
 *   ),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\contacts_dbs\Form\DBSWorkforceForm",
 *       "edit" = "Drupal\contacts_dbs\Form\DBSWorkforceForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "list_builder" = "Drupal\contacts_dbs\DBSWorkforceListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer dbs workforces",
 *   config_prefix = "workforce",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "valid",
 *     "alternatives",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/dbs-workforce/add",
 *     "edit-form" = "/admin/structure/dbs-workforce/manage/{dbs_workforce}",
 *     "delete-form" = "/admin/structure/dbs-workforce/manage/{dbs_workforce}/delete",
 *     "collection" = "/admin/structure/dbs-workforce",
 *   },
 * )
 */
class DBSWorkforce extends ConfigEntityBase implements DBSWorkforceInterface {

  /**
   * The machine name of this dbs workforce.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the dbs workforce.
   *
   * @var string
   */
  protected $label;

  /**
   * The number of years a workforce is valid for.
   *
   * @var int
   */
  protected $valid;

  /**
   * Alternative workforces that can be used in place of this one.
   *
   * @var string[]
   */
  protected $alternatives = [];

  /**
   * {@inheritdoc}
   */
  public function getValidity() {
    return $this->valid;
  }

  /**
   * {@inheritdoc}
   */
  public function setValidity(int $valid) {
    return $this->set('valid', $valid);
  }

  /**
   * {@inheritdoc}
   */
  public function getAlternatives() {
    return $this->alternatives;
  }

  /**
   * Gets the list of available workforce entities.
   *
   * @return array
   *   The workforce labels keyed by entity id.
   */
  public static function getOptions() {
    return array_map(function (DBSWorkforce $item) {
      return $item->label();
    }, \Drupal::service('entity_type.manager')->getStorage('dbs_workforce')->loadMultiple());
  }

}
