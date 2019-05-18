<?php

namespace Drupal\webpay\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\Entity\EntityStorageInterface;
use Freshwork\Transbank\CertificationBag;
use Freshwork\Transbank\Log\LoggerFactory;
use Freshwork\Transbank\Log\TransbankCertificationLogger;

/**
 * Defines the Webpay config entity.
 *
 * @ConfigEntityType(
 *   id = "webpay_config",
 *   label = @Translation("Commerce configuration"),
 *   label_collection = @Translation("Commerces"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\webpay\WebpayConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webpay\Form\WebpayConfigForm",
 *       "edit" = "Drupal\webpay\Form\WebpayConfigForm",
 *       "delete" = "Drupal\webpay\Form\WebpayConfigDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\webpay\WebpayConfigHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "webpay_config",
 *   admin_permission = "webpay administer",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/webpay/webpay_config/{webpay_config}",
 *     "add-form" = "/admin/config/webpay/webpay_config/add",
 *     "edit-form" = "/admin/config/webpay/webpay_config/{webpay_config}",
 *     "delete-form" = "/admin/config/webpay/webpay_config/{webpay_config}/delete",
 *     "collection" = "/admin/config/webpay/webpay_config",
 *     "test" = "/admin/config/webpay/webpay_config/{webpay_config}/test",
 *     "logs" = "/admin/config/webpay/webpay_config/{webpay_config}/logs"
 *   }
 * )
 */
class WebpayConfig extends ConfigEntityBase implements WebpayConfigInterface {

  /**
   * The Webpay config ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Webpay config ID.
   *
   * @var string
   */
  protected $commerce_code;

  /**
   * The Webpay config name.
   *
   * @var string
   */
  protected $name;

  /**
   * The Webpay config commerce_code.
   *
   * @var string
   */
  protected $environment;

  /**
   * The path or uri of the client certificate file.
   */
  protected $client_certificate;

  /**
   * The path or uri of the private key file.
   */
  protected $private_key;

  /**
   * The path or uri of the server certificate file.
   */
  protected $server_certificate;

  /**
   * Log status
   */
  protected $log;

  /**
   * Get the environments options.
   */
  public static function environments() {
    return [
      CertificationBag::INTEGRATION => t('Certification/Integration'),
      CertificationBag::PRODUCTION => t('Production'),
    ];
  }

  /**
   * Get the Environment name.
   */
  public function getEnvironment() {
    $environments = self::environments();

    return isset($environments[$this->environment]) ? $environments[$this->environment] : NULL;
  }

  /**
   * Active the log system.
   */
  public function activeLog() {
    $path = self::getPathFiles($this->id() . '/logs', TRUE);

    LoggerFactory::setLogger(new TransbankCertificationLogger($path));
  }


  /**
   * Get all logs.
   */
  public function getLogs() {
    $path = self::getPathFiles($this->id() . '/logs');
    $files = file_scan_directory($path, '/.*\.txt$/');

    $logs = [];
    foreach ($files as $data) {
      $logs[$data->name] = file_get_contents($data->uri);
    }

    return $logs;;
  }

  /**
   * Helper function to get path files for webpay config.
   */
  public static function getPathFiles($suffix, $create = FALSE) {
    $fileSystem = \Drupal::service('file_system');
    $scheme = 'public';
    // Check if the private scheme is valid.
    if (!empty(PrivateStream::basePath())) {
      $scheme = 'private';
    }

    $path = $scheme . '://webpay-commerces/' . $suffix;

    if ($create) {
      if (file_prepare_directory($path, FILE_CREATE_DIRECTORY)) {
        // If the scheme is public, create the .htaccess.
        if ($scheme == 'public') {
          file_save_htaccess('public://webpay-commerces', TRUE);
        }
      }
      else {
        return FALSE;
      }

    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    foreach ($entities as $entity) {
      // Delete the folder of the entity.
      $path = self::getPathFiles($entity->id());
      file_unmanaged_delete_recursive($path);
    }
  }
}
