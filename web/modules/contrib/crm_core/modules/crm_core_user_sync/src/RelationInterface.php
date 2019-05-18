<?php

namespace Drupal\crm_core_user_sync;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\crm_core_contact\IndividualInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining a relation entity type.
 */
interface RelationInterface extends ContentEntityInterface {

  /**
   * Returns the relation user entity.
   *
   * @return \Drupal\user\UserInterface
   *   The relation user entity.
   */
  public function getUser();

  /**
   * Sets the relation user entity.
   *
   * @param \Drupal\user\UserInterface $account
   *   The relation user entity.
   *
   * @return $this
   */
  public function setUser(UserInterface $account);

  /**
   * Returns the relation user ID.
   *
   * @return int|null
   *   The relation user ID, or NULL in case the user ID field has not been set.
   */
  public function getUserId();

  /**
   * Sets the relation user ID.
   *
   * @param int $uid
   *   The relation user id.
   *
   * @return $this
   */
  public function setUserId($uid);

  /**
   * Returns the relation individual entity.
   *
   * @return \Drupal\crm_core_contact\IndividualInterface
   *   The relation individual entity.
   */
  public function getIndividual();

  /**
   * Sets the relation individual entity.
   *
   * @param \Drupal\crm_core_contact\IndividualInterface $individual
   *   The relation individual entity.
   *
   * @return $this
   */
  public function setIndividual(IndividualInterface $individual);

  /**
   * Returns the relation individual ID.
   *
   * @return int|null
   *   The relation individual ID, or NULL in case the individual ID field has
   *   not been set.
   */
  public function getIndividualId();

  /**
   * Sets the relation individual ID.
   *
   * @param int $individual_id
   *   The relation individual id.
   *
   * @return $this
   */
  public function setIndividualId($individual_id);

}
