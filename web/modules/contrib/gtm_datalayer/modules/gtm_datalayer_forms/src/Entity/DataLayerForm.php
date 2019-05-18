<?php

namespace Drupal\gtm_datalayer_forms\Entity;

use Drupal\gtm_datalayer\Entity\DataLayer;

/**
 * Defines a GTM dataLayer configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "gtm_datalayer_form",
 *   label = @Translation("GTM dataLayer Form"),
 *   label_singular = @Translation("GTM dataLayer Form"),
 *   label_plural = @Translation("GTM dataLayer Forms"),
 *   label_count = @PluralTranslation(
 *     singular = "@count GTM dataLayer Form",
 *     plural = "@count GTM dataLayer Forms"
 *   ),
 *   admin_permission = "administer gtm datalayer",
 *   handlers = {
 *     "list_builder" = "Drupal\gtm_datalayer_forms\DataLayerFormListBuilder",
 *     "form" = {
 *       "add" = "Drupal\gtm_datalayer_forms\Form\DataLayerFormAddForm",
 *       "edit" = "Drupal\gtm_datalayer_forms\Form\DataLayerFormEditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider"
 *     },
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/datalayers/forms/add",
 *     "edit-form" = "/admin/structure/datalayers/forms/{gtm_datalayer_form}",
 *     "delete-form" = "/admin/structure/datalayers/forms/{gtm_datalayer_form}/delete",
 *     "enable" = "/admin/structure/datalayers/forms/{gtm_datalayer_form}/enable",
 *     "disable" = "/admin/structure/datalayers/forms/{gtm_datalayer_form}/disable",
 *     "collection" = "/admin/structure/datalayers/forms"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "form",
 *     "plugin",
 *     "weight",
 *     "access_conditions",
 *     "access_logic"
 *   }
 * )
 */
class DataLayerForm extends DataLayer implements DataLayerFormInterface {

  /**
   * The form ID of the GTM dataLayer.
   *
   * @var string
   */
  protected $form;

  /**
   * {@inheritdoc}
   */
  public function getFrom() {
    return $this->form;
  }

  /**
   * {@inheritdoc}
   */
  public function setFrom($form) {
    $this->form_id = $form;

    return $this;
  }

}
