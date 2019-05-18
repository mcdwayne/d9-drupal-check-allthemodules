<?php
/**
 * Created by PhpStorm.
 * User: milan
 * Date: 2/2/19
 * Time: 2:03 AM
 */

namespace Drupal\hidden_tab\Entity\Helper;

/**
 * Implements DescribedEntityInterface.
 *
 * @see \Drupal\hidden_tab\Entity\Base\DescribedEntityInterface
 */
trait DescribedEntityTrait {

  /**
   * See description().
   *
   * @var string
   *   See description().
   */
  protected $description;

  /**
   * See description() in DescribedEntityInterface.
   *
   * @see \Drupal\hidden_tab\Entity\Base\DescribedEntityInterface::description()
   */
  public function description(): ?string {
    return $this->description;
  }

}
