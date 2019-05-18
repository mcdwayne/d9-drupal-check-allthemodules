<?php

namespace Drupal\getresponse_forms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\getresponse_forms\GetresponseFormsInterface;
use Drupal\getresponse_forms\FieldInterface;
use Drupal\getresponse_forms\FieldPluginCollection;

/**
 * Defines the GetresponseForms (take this to mean a GetResponse Forms form) entity.
 *
 * @ingroup getresponse_forms
 *
 * @ConfigEntityType(
 *   id = "getresponse_forms",
 *   label = @Translation("Getresponse Forms Form"),
 *   fieldable = FALSE,
 *   handlers = {
 *     "access" = "Drupal\getresponse_forms\GetresponseFormsFormAccessControlHandler",
 *     "view_builder" = "Drupal\getresponse_forms\GetresponseFormsFormViewBuilder",
 *     "list_builder" = "Drupal\getresponse_forms\Controller\GetresponseFormsListBuilder",
 *     "form" = {
 *       "add" = "Drupal\getresponse_forms\Form\GetresponseFormsForm",
 *       "edit" = "Drupal\getresponse_forms\Form\GetresponseFormsForm",
 *       "delete" = "Drupal\getresponse_forms\Form\GetresponseFormsDeleteForm"
 *     }
 *   },
 *   config_prefix = "getresponse_forms",
 *   admin_permission = "administer getresponse_forms",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title"
 *   },
 *   links = {
 *     "collection" = "/admin/config/services/getresponse/forms",
 *     "edit-form" = "/admin/config/services/getresponse/forms/{getresponse_forms}",
 *     "delete-form" = "/admin/config/services/getresponse/forms/{getresponse_forms}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "title",
 *     "description",
 *     "mode",
 *     "path",
 *     "submit_button",
 *     "confirmation_message",
 *     "destination",
 *     "gr_lists",
 *     "fields",
 *     "notification_email"
 *   }
 * )
 */
class GetresponseForms extends ConfigEntityBase implements GetresponseFormsInterface, EntityWithPluginCollectionInterface {

  // DEPRECATED?
  public $id;

  /**
   * The Signup Form Machine Name.
   *
   * @var string
   */
  public $name;

  /**
   * The Signup Form Title (label).
   *
   * @var string
   */
  public $title;

  /**
   * Notification e-mail address to send notice of signups to.
   *
   * @var string
   */
  public $notification_email;

  /**
   * The Signup Form GetResponse Lists.
   *
   * @var array
   */
  public $gr_lists;

  /**
   * The array of custom fields for this form.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * Holds the collection of GetResponse fields that are used by this form.
   *
   * @var \Drupal\getresponse_forms\FieldPluginCollection
   */
  protected $fieldCollection;

  /**
   * The Signup Form Mode (Block, Page, or Both).
   *
   * @var int
   */
  public $mode;

  /**
   * The Signup Form Settings array.
   *
   * @var array
   */
  public $settings;

  /**
   * The Signup Form Status.
   *
   * @var boolean
   */
  public $status;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id; // $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteField(FieldInterface $field) {
    $this->getFields()->removeInstanceId($field->getUuid());
    $this->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getField($field_id) {
    return $this->getFields()->get($field_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    if (!isset($this->fieldsCollection) || !$this->fieldsCollection) {
      $this->fieldsCollection = new FieldPluginCollection($this->getFieldPluginManager(), $this->fields);
      $this->fieldsCollection->sort();
    }
    return $this->fieldsCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['fields' => $this->getFields()];
  }

  /**
   * {@inheritdoc}
   */
  public function addField(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getFields()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name');
  }

  /**
   * Returns the GetResponse field plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The GetResponse field plugin manager.
   */
  protected function getFieldPluginManager() {
    return \Drupal::service('plugin.manager.getresponse_forms.field');
  }


}
