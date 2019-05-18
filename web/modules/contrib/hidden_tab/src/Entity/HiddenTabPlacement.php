<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Annotation\PluralTranslation;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\ConfigEntityType;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Entity\Helper\DescribedEntityTrait;
use Drupal\hidden_tab\Entity\Helper\RefrencerEntityTrait;
use Drupal\hidden_tab\Entity\Helper\StatusedEntityTrait;
use Drupal\hidden_tab\Entity\Helper\TimestampedEntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Defines the hidden_tab_placement entity type.
 *
 * Each page has a template and the template defines some regions. Different
 * komponents (komponents provided by plugins) may be placed into the regions.
 * This entity stores information about the komponents placed into these
 * regions.
 *
 * @ConfigEntityType(
 *   id = "hidden_tab_placement",
 *   label = @Translation("Hidden Tab Placement"),
 *   label_collection = @Translation("Hidden Tab Placements"),
 *   label_singular = @Translation("Hidden Tab Placement"),
 *   label_plural = @Translation("Hidden Tab Placements"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Hidden Tab Placement",
 *     plural = "@count Hidden Tab Placements",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\hidden_tab\Entity\HiddenTabPlacementListBuilder",
 *     "access" = "Drupal\hidden_tab\Entity\HiddenTabPlacementAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\hidden_tab\Form\HiddenTabPlacementForm",
 *       "edit" = "Drupal\hidden_tab\Form\HiddenTabPlacementForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "default" = "Drupal\hidden_tab\Form\HiddenTabPlacementForm"
 *     }
 *   },
 *   config_prefix = "hidden_tab_placement",
 *   admin_permission = "administer hidden_tab_placement",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/hidden-tab-placement/add",
 *     "edit-form" = "/admin/structure/hidden-tab-placement/{hidden_tab_placement}",
 *     "delete-form" = "/admin/structure/hidden-tab-placement/{hidden_tab_placement}/delete",
 *     "collection" = "/admin/structure/hidden-tab-placement"
 *   }
 * )
 */
class HiddenTabPlacement extends ConfigEntityBase implements HiddenTabPlacementInterface {

  use RefrencerEntityTrait;
  use StatusedEntityTrait;
  use DescribedEntityTrait;
  use TimestampedEntityTrait;
  use EntityChangedTrait;

  /**
   * See weight() for more information.
   *
   * @var int|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface::weight()
   */
  protected $weight;

  /**
   * See region() for more information.
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface::region()
   */
  protected $region;

  /**
   * See viewPermission() for more information.
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface::viewPermission()
   */
  protected $view_permission;

  /**
   * See komponentType() for more information.
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface::komponentType()
   */
  protected $komponent_type;

  /**
   * See komponent() for more information.
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface::komponentType()
   */
  protected $komponent;

  /**
   * Additional configuration storage for the plugin.
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface::komponentConfiguration()
   */
  protected $komponent_configuration;

  /**
   * {@inheritdoc}
   */
  public function weight(): int {
    return $this->weight ? $this->weight : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function region(): ?string {
    return $this->region;
  }

  /**
   * {@inheritdoc}
   */
  public function viewPermission(): ?string {
    return $this->view_permission;
  }

  /**
   * {@inheritdoc}
   */
  public function komponentType(): ?string {
    return $this->komponent_type;
  }

  /**
   * {@inheritdoc}
   */
  public function komponent(): ?string {
    return $this->komponent;
  }

  /**
   * {@inheritdoc}
   */
  public function komponentConfiguration(): ?string {
    return $this->komponent_configuration
      ? json_decode($this->komponent_configuration, TRUE)
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setKomponentConfiguration($configuration) {
    $this->komponent_configuration = json_encode($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation,
                         AccountInterface $account = NULL,
                         $return_as_object = FALSE,
                         ?EntityInterface $context_entity = NULL,
                         ?ParameterBag $bag = NULL) {
    /** @var \Drupal\hidden_tab\Entity\HiddenTabPlacementAccessControlHandler $am */
    $am = $this->entityTypeManager()
      ->getAccessControlHandler($this->entityTypeId);
    if ($operation == 'create') {
      return $am->createAccess(
        $this->bundle(), $account, [], $return_as_object);
    }
    return $am->access(
      $this, $operation, $account, $return_as_object, $context_entity, $bag);
  }
}
