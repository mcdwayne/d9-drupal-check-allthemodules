<?php

namespace Drupal\webform_composite\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform_composite\WebformCompositeInterface;

/**
 * Defines the Webform Composite entity.
 *
 * @ConfigEntityType(
 *   id = "webform_composite",
 *   label = @Translation("Webform Composite"),
 *   label_singular = @Translation("Webform Composite"),
 *   label_plural = @Translation("Webform Composites"),
 *   label_count = @PluralTranslation(
 *     singular = "@count composite",
 *     plural = "@count composites",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\webform_composite\Controller\WebformCompositeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webform_composite\Form\WebformCompositeForm",
 *       "edit" = "Drupal\webform_composite\Form\WebformCompositeForm",
 *       "source" = "Drupal\webform_composite\Form\WebformCompositeSourceForm",
 *       "delete" = "Drupal\webform_composite\Form\WebformCompositeDeleteForm",
 *     }
 *   },
 *   admin_permission = "administer webform",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/webform/config/composite/{webform_composite}",
 *     "source-form" = "/admin/structure/webform/config/composite/{webform_composite}/source",
 *     "delete-form" = "/admin/structure/webform/config/composite/{webform_composite}/delete",
 *   },
 *   config_export = {
 *     "id" = "id",
 *     "label" = "label",
 *     "elements" = "elements",
 *     "description" = "description",
 *   }
 * )
 */
class WebformComposite extends ConfigEntityBase implements WebformCompositeInterface {

  /**
   * The Composite ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Composite label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Composite webforms elements as YAML.
   *
   * @var string
   */
  protected $elements;

  /**
   * The webform description.
   *
   * @var string
   */
  protected $description;

  /**
   * The Composite elements decoded.
   *
   * @var array
   */
  protected $elementsDecoded;

  /**
   * {@inheritdoc}
   */
  public function getElementsRaw() {
    return $this->elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsDecoded() {
    if (!isset($this->elementsDecoded)) {
      // Decode elements from YAML.
      $elements = Yaml::decode($this->elements);
      // Since YAML supports simple values.
      $elements = (is_array($elements)) ? $elements : [];
      foreach ($elements as &$element) {
        if (isset($element["#states"])) {
          // Strip element states data. This causes unexpected behavior.
          unset($element["#states"]);
        }
      }
      $this->elementsDecoded = $elements;
    }
    return $this->elementsDecoded;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

}
