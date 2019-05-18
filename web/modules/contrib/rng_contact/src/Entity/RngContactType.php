<?php

namespace Drupal\rng_contact\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Defines the contact type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "rng_contact_type",
 *   label = @Translation("Contact type"),
 *   admin_permission = "administer rng_contact types",
 *   config_prefix = "rng_contact_type",
 *   bundle_of = "rng_contact",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\rng_contact\Lists\RngContactTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\rng_contact\Form\RngContactTypeForm",
 *       "add" = "Drupal\rng_contact\Form\RngContactTypeForm",
 *       "edit" = "Drupal\rng_contact\Form\RngContactTypeForm",
 *       "delete" = "Drupal\rng_contact\Form\RngContactTypeDeleteForm"
 *     },
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/rng_contact/contact_types/add",
 *     "canonical" = "/admin/structure/rng_contact/contact_types/manage/{rng_contact_type}",
 *     "delete-form" = "/admin/structure/rng_contact/contact_types/manage/{rng_contact_type}/delete",
 *     "edit-form" = "/admin/structure/rng_contact/contact_types/manage/{rng_contact_type}",
 *     "admin-form" = "/admin/structure/rng_contact/contact_types/manage/{rng_contact_type}",
 *     "collection" = "/admin/structure/rng_contact/contact_types"
 *   }
 * )
 */
class RngContactType extends ConfigEntityBundleBase implements RngContactTypeInterface {

  /**
   * The machine name of this contact type.
   *
   * @var string
   */
  public $id;

  /**
   * The human readable name of this contact type.
   *
   * @var string
   */
  public $label;

  /**
   * A brief description of this contact type.
   *
   * @var string
   */
  public $description;

  /**
   * ID of email field to use when sending mailings via Courier.
   *
   * @var string
   */
  protected $courier_email_field;

  /**
   * {@inheritdoc}
   */
  public function getCourierEmailField() {
    return $this->courier_email_field;
  }

  /**
   * {@inheritdoc}
   */
  public function setCourierEmailField($courier_email_field) {
    $this->courier_email_field = $courier_email_field;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    $email_field_id = $this->getCourierEmailField();
    $email_field = FieldConfig::loadByName('rng_contact', $this->id(), $email_field_id);
    if ($email_field) {
      $this->addDependency('config', $email_field->getConfigDependencyName());
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    foreach ($dependencies['config'] as $entity) {
      if ($entity instanceof FieldConfigInterface) {
        // Courier email field is being deleted.
        if ($entity->getName() === $this->getCourierEmailField()) {
          $this->setCourierEmailField(NULL);
          $changed = TRUE;
        }
      }
    }

    return $changed;
  }

}
