<?php

namespace Drupal\hidden_tab\Entity\Base;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\user\UserInterface;

/**
 * An entity targeting a page, a user and an entity.
 *
 * An entity, referencing a page (usually mandatory), a user (usually
 * optionally) and an entity (usually optional).
 */
interface RefrencerEntityInterface extends EntityInterface {

  /**
   * Id of targeted hidden tab page.
   *
   * @return string|null
   *   Id of targeted hidden tab page.
   */
  public function targetPageId(): ?string;

  /**
   * Targeted hidden tab page, loaded.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabPageInterface|null
   *   Target hidden tab page entity loaded.
   */
  public function targetPageEntity(): ?HiddenTabPageInterface;

  /**
   * Targeted user, loaded.
   *
   * @return \Drupal\user\UserInterface|null
   *   Target user id.
   */
  public function targetUserId(): ?string;

  /**
   * Id of targeted user.
   *
   * @return \Drupal\user\UserInterface|null
   *   Target user entity loaded.
   */
  public function targetUserEntity(): ?UserInterface;

  /**
   * Id of targeted entity.
   *
   * @return string|null
   *   Id of target entity.
   */
  public function targetEntityId(): ?string;

  /**
   * Targeted entity, loaded.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Target entity loaded.
   */
  public function targetEntity(): ?EntityInterface;

  /**
   * Entity type of targeted entity.
   *
   * @return string|null
   *   Target entity type.
   */
  public function targetEntityType(): ?string;

  /**
   * Bundle of entity type of targeted entity.
   *
   * @return string|null
   *   Selected bundle.
   */
  public function targetEntityBundle(): ?string;

}
