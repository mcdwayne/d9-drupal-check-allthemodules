<?php

namespace Drupal\dam\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;


/**
 * Defines the FTP Account type entity.
 *
 * @ConfigEntityType(
 *   id = "ftp_account_type",
 *   label = @Translation("FTP Account type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dam\Controller\FTPAccountTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dam\Form\FTPAccountTypeForm",
 *       "edit" = "Drupal\dam\Form\FTPAccountTypeForm",
 *       "delete" = "Drupal\dam\Form\FTPAccountTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\dam\FTPAccountTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "ftp_account_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "ftp_account",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/ftp/ftp_account_type/{ftp_account_type}",
 *     "add-form" = "/ftp/ftp_account_type/add",
 *     "edit-form" = "/ftp/ftp_account_type/{ftp_account_type}/edit",
 *     "delete-form" = "/ftp/ftp_account_type/{ftp_account_type}/delete",
 *     "collection" = "/dam/ftp_account_type/list"
 *   }
 * )
 */
class FTPAccountType extends ConfigEntityBundleBase implements FTPAccountTypeInterface {

  /**
   * The FTP Account type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The FTP Account type label.
   *
   * @var string
   */
  protected $label;

}
