<?php

namespace Drupal\hidden_tab\Entity\Helper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Service\HiddenTabEntityHelper;
use Drupal\user\UserInterface;

/**
 * Implements Base\RefrencerEntityInterface.
 *
 * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface
 */
trait RefrencerEntityTrait {

  /**
   * See targetPageId().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetPageId()
   */
  protected $target_hidden_tab_page;

  /**
   * See targetPageEntity().
   *
   * @var \Drupal\hidden_tab\Entity\HiddenTabPageInterface
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetPageEntity()
   */
  protected $targetPageEntity;

  /**
   * See targetUserId().
   *
   * @var string|integer
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetUserId()
   */
  protected $target_user;

  /**
   * See targetUserEntity().
   *
   * @var \Drupal\user\UserInterface
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetUserEntity()
   */
  protected $targetUserEntity;

  /**
   * See targetEntityId().
   *
   * @var mixed
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetEntityId()
   */
  protected $target_entity;

  /**
   * See targetEntity().
   *
   * @var \Drupal\Core\Entity\EntityInterface
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetEntity()
   */
  protected $targetEntity;

  /**
   * See targetEntityType().
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetEntityType()
   */
  protected $target_entity_type;

  /**
   * See targetEntityBundle().
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetEntityBundle()
   */
  protected $target_entity_bundle;

  /**
   * See targetPageId() in RefrencerEntityTrait.
   *
   * @return string
   *   See targetPageId() in RefrencerEntityTrait.
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetPageId()
   */
  public function targetPageId(): ?string {
    return $this->target_hidden_tab_page;
  }

  /**
   * See targetPageEntity() in RefrencerEntityTrait.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabPageInterface|null
   *   See targetPageEntity() in RefrencerEntityTrait.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetPageEntity()
   */
  public function targetPageEntity(): ?HiddenTabPageInterface {
    if (!isset($this->targetPageEntity) && $this->targetPageId() !== NULL && $this->targetPageId() !== '') {
      $this->targetPageEntity = HiddenTabEntityHelper::instance()
        ->page($this->targetPageId());
    }
    return $this->targetPageEntity;
  }

  /**
   * See targetPageId() in RefrencerEntityTrait.
   *
   * @return string|null
   *   See targetPageId() in RefrencerEntityTrait.
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetPageId()
   */
  public function targetUserId(): ?string {
    return $this->target_user;
  }

  /**
   * See targetUserEntity() in RefrencerEntityTrait.
   *
   * @return \Drupal\user\UserInterface|null See targetPageId() in
   *   RefrencerEntityTrait. See targetUserEntity() in RefrencerEntityTrait.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetUserEntity()
   */
  public function targetUserEntity(): ?UserInterface {
    if (!isset($this->targetUserEntity) && $this->targetUserId() !== NULL && $this->targetUserId() !== '') {
      $this->targetUserEntity = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->load($this->targetUserId());
    }
    return $this->targetUserEntity;
  }

  /**
   * See targetEntityId() in RefrencerEntityTrait.
   *
   * @return string|null
   *   See targetEntityId() in RefrencerEntityTrait.
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetEntityId()
   */
  public function targetEntityId(): ?string {
    return $this->target_entity;
  }

  /**
   * See targetEntity() in RefrencerEntityTrait.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   See targetEntity() in RefrencerEntityTrait.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetEntity()
   */
  public function targetEntity(): ?EntityInterface {
    if (!isset($this->targetEntity) && $this->targetEntityId() && $this->targetEntityId() !== NULL && $this->targetEntityId() !== '') {
      $this->targetEntity = \Drupal::entityTypeManager()
        ->getStorage($this->targetEntityType())
        ->load($this->targetEntityId());
    }
    return $this->targetEntity;
  }

  /**
   * See targetEntityType() in RefrencerEntityTrait.
   *
   * @return string|null
   *   See targetEntityType() in RefrencerEntityTrait.
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetEntityType()
   */
  public function targetEntityType(): ?string {
    return $this->target_entity_type;
  }

  /**
   * See targetEntityBundle() in RefrencerEntityTrait.
   *
   * @return string|null
   *   See targetEntityBundle() in RefrencerEntityTrait.
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface::targetEntityBundle()
   */
  public function targetEntityBundle(): ?string {
    return $this->target_entity_bundle;
  }

}
