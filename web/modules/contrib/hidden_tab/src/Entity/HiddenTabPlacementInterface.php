<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\hidden_tab\Entity\Base\DescribedEntityInterface;
use Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface;
use Drupal\hidden_tab\Entity\Base\StatusedEntityInterface;
use Drupal\hidden_tab\Entity\Base\TimestampedEntityInterface;

/**
 * Provides an interface defining a hidden_tab_placement entity type.
 *
 * Values MUST be able to return null, because of komponent form opening in
 * modal windows as edit form.
 */
interface HiddenTabPlacementInterface extends
  ConfigEntityInterface,
  RefrencerEntityInterface,
  StatusedEntityInterface,
  DescribedEntityInterface,
  TimestampedEntityInterface {

  /**
   * Weight of the placement among other placements in the same region.
   *
   * Used to order placements for rendering.
   *
   * @return int|null
   *   Weight of the placement among other placements in the same region.
   */
  public function weight(): int;

  /**
   * Region in the template of the corresponding page, placement is put into.
   *
   * @return string|null
   *   Region in the template of the corresponding page, placement is put into.
   */
  public function region(): ?string;

  /**
   * The permission user must posses to view the komponent of the placement.
   *
   * @return string|null
   *   The permission user must posses to see the komponent of the placement.
   */
  public function viewPermission(): ?string;

  /**
   * Type of the komponent, such as view, provided by plugins.
   *
   * @return string|null
   *   Type of the komponent, such as view, provided by plugins.
   */
  public function komponentType(): ?string;

  /**
   * Think of it as a display in a view. Komponent plugin defines it.
   *
   * @return string|null
   *   Think of it as a display in a view. Komponent plugin defines it.
   */
  public function komponent(): ?string;

  /**
   * Additional configuration storage for the plugin.
   *
   * @return string|null
   *   Additional configuration storage for the plugin.
   */
  public function komponentConfiguration();

  /**
   * Setter of komponentConfiguration().
   *
   * @param $configuration
   *   Configuration data, json_encoded later.
   */
  public function setKomponentConfiguration($configuration);

}
