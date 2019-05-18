<?php

namespace Drupal\hidden_tab\Entity\Base;

use Drupal\Core\Entity\EntityInterface;

/**
 * An entity maintaining a text description field.
 */
interface DescribedEntityInterface extends EntityInterface {

  /**
   * Entity's description.
   *
   * @return string|null
   *   Entity's description.
   */
  public function description(): ?string;

}
