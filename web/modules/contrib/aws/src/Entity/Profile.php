<?php

namespace Drupal\aws\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\encrypt\Entity\EncryptionProfile;

/**
 * Defines the profile entity class.
 *
 * @ConfigEntityType(
 *   id = "aws_profile",
 *   label = @Translation("Amazon Web Services Profile"),
 *   label_collection = @Translation("Amazon Web Services Profiles"),
 *   label_singular = @Translation("amazon web services profile"),
 *   label_plural = @Translation("amazon web services profiles"),
 *   label_count = @PluralTranslation(
 *     singular = "@count amazon web services profile",
 *     plural = "@count amazon web services profiles",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\aws\ProfileListBuilder",
 *     "form" = {
 *       "default" = "Drupal\aws\Form\ProfileForm",
 *       "edit" = "Drupal\aws\Form\ProfileForm",
 *       "delete" = "Drupal\aws\Form\ProfileDeleteConfirmForm",
 *     },
 *   },
 *   admin_permission = "administer aws",
 *   config_prefix = "profile",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "default",
 *     "aws_access_key_id",
 *     "aws_secret_access_key",
 *     "region"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/aws/profile/{aws_profile}",
 *     "add-form" = "/admin/config/services/aws/add-profile",
 *     "edit-form" = "/admin/config/services/aws/profile/{aws_profile}/edit",
 *     "delete-form" = "/admin/config/services/aws/profile/{aws_profile}/delete",
 *   }
 * )
 */
class Profile extends ConfigEntityBase implements ProfileInterface {

  /**
   * The ID of the profile.
   *
   * @var string
   */
  protected $id;

  /**
   * The name of the profile.
   *
   * @var string
   */
  protected $name;

  /**
   * Whether the profile is the default or not.
   *
   * @var bool
   */
  protected $default;

  /**
   * The Access Key of the profile.
   *
   * @var string
   */
  protected $aws_access_key_id;

  /**
   * The Secret Access Key of the profile.
   *
   * @var string
   */
  protected $aws_secret_access_key = '';

  /**
   * The Region of the profile.
   *
   * @var string
   */
  protected $region;

  protected $encryption;

  protected $encryptionProfile;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values) {
    parent::__construct($values, 'aws_profile');
    $this->encryption = \Drupal::service('encryption');
    $this->encryptionProfile = EncryptionProfile::load('aws_encryption_profile');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefault() {
    return $this->default;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefault($default) {
    $this->default = $default;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessKey() {
    return $this->aws_access_key_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessKey($aws_access_key_id) {
    $this->aws_access_key_id = $aws_access_key_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecretAccessKey() {
    return $this->encryption->decrypt($this->aws_secret_access_key, $this->encryptionProfile);
  }

  /**
   * {@inheritdoc}
   */
  public function setSecretAccessKey($aws_secret_access_key) {
    $this->aws_secret_access_key = $this->encryption->encrypt($aws_secret_access_key, $this->encryptionProfile);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegion() {
    return $this->region;
  }

  /**
   * {@inheritdoc}
   */
  public function setRegion($region) {
    $this->region = $region;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientArgs() {
    return [
      'credentials' => [
        'key' => $this->getAccessKey(),
        'secret' => $this->getSecretAccessKey(),
      ],
      'region' => $this->getRegion(),
      'version' => 'latest',
    ];
  }

}
