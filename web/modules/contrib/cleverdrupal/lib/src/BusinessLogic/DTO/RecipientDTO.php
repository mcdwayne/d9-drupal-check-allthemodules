<?php

namespace CleverReach\BusinessLogic\DTO;

/**
 *
 */
class RecipientDTO {
  /**
   * @var \CleverReach\BusinessLogic\Entity\Recipient
   */
  private $recipientEntity;
  /**
   * @var \CleverReach\BusinessLogic\Entity\TagCollection
   */
  private $tagsForDelete;
  /**
   * @var bool
   */
  private $includeOrdersActivated;
  /**
   * @var bool
   */
  private $activatedFieldForSending;
  /**
   * @var bool
   */
  private $deactivatedFieldForSending;

  /**
   * RecipientDTO constructor.
   *
   * @param \CleverReach\BusinessLogic\Entity\Recipient $recipientEntity
   * @param \CleverReach\BusinessLogic\Entity\TagCollection $tagsForDelete
   * @param bool $shouldIncludeOrders
   * @param bool $shouldSendActivated
   * @param bool $shouldSendDeactivated
   */
  public function __construct($recipientEntity,
        $tagsForDelete,
        $shouldIncludeOrders,
        $shouldSendActivated,
        $shouldSendDeactivated
    ) {
    $this->recipientEntity = $recipientEntity;
    $this->tagsForDelete = $tagsForDelete;
    $this->includeOrdersActivated = $shouldIncludeOrders;
    $this->activatedFieldForSending = $shouldSendActivated;
    $this->deactivatedFieldForSending = $shouldSendDeactivated;
  }

  /**
   * @return \CleverReach\BusinessLogic\Entity\Recipient
   */
  public function getRecipientEntity() {
    return $this->recipientEntity;
  }

  /**
   * @return \CleverReach\BusinessLogic\Entity\TagCollection
   */
  public function getTagsForDelete() {
    return $this->tagsForDelete;
  }

  /**
   * @return bool
   */
  public function isIncludeOrdersActivated() {
    return $this->includeOrdersActivated;
  }

  /**
   * @param bool $shouldIncludeOrders
   */
  public function setIncludeOrdersActivated($shouldIncludeOrders) {
    $this->includeOrdersActivated = $shouldIncludeOrders;
  }

  /**
   * @return bool
   */
  public function shouldActivatedFieldBeSent() {
    return $this->activatedFieldForSending;
  }

  /**
   * @param bool $activatedFieldForSending
   */
  public function setActivatedFieldForSending($activatedFieldForSending) {
    $this->activatedFieldForSending = $activatedFieldForSending;
  }

  /**
   * @return bool
   */
  public function shouldDeactivatedFieldBeSent() {
    return $this->deactivatedFieldForSending;
  }

  /**
   * @param bool $deactivatedFieldForSending
   */
  public function setDeactivatedFieldForSending($deactivatedFieldForSending) {
    $this->deactivatedFieldForSending = $deactivatedFieldForSending;
  }

}
