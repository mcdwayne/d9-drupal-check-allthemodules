<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Interfaces\Attributes;
use CleverReach\Infrastructure\ServiceRegister;

/**
 *
 */
class AttributesSyncTask extends BaseSyncTask {
  const INITIAL_PROGRESS_PERCENT = 5;
  /**
   * @var array
   */
  private $globalAttributesIdsFromCR;

  /**
   * @inheritdoc
   */
  public function serialize() {
    return serialize($this->globalAttributesIdsFromCR);
  }

  /**
   * @inheritdoc
   */
  public function unserialize($serialized) {
    $this->globalAttributesIdsFromCR = unserialize($serialized);
  }

  /**
   * Runs task logic.
   *
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   */
  public function execute() {
    $globalAttributes = $this->getGlobalAttributesIdsFromCR();
    // After retrieving all global attributes set initial progress.
    $progressPercent = self::INITIAL_PROGRESS_PERCENT;
    $this->reportProgress($progressPercent);

    $attributesToSend = $this->getAllAttributes();

    // Calculate progress step after setting initially progress.
    $totalAttributes = count($attributesToSend);
    $progressStep = (100 - self::INITIAL_PROGRESS_PERCENT) / $totalAttributes;
    $i = 0;
    foreach ($attributesToSend as $attribute) {
      $i++;
      if (isset($globalAttributes[$attribute['name']])) {
        $attributeIdOnCR = $globalAttributes[$attribute['name']];
        $this->getProxy()->updateGlobalAttribute($attributeIdOnCR, $attribute);
      }
      else {
        $this->getProxy()->createGlobalAttribute($attribute);
      }

      $progressPercent += $progressStep;
      if ($i === $totalAttributes) {
        $this->reportProgress(100);
      }
      else {
        $this->reportProgress($progressPercent);
      }
    }
  }

  /**
   * Get global attributes ids from CleverReach.
   *
   * @return array
   *
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   */
  private function getGlobalAttributesIdsFromCR() {
    if (empty($this->globalAttributesIdsFromCR)) {
      $this->globalAttributesIdsFromCR = $this->getProxy()->getAllGlobalAttributes();
    }

    return $this->globalAttributesIdsFromCR;
  }

  /**
   * Return all global attributes formatted for sending to CR.
   *
   * @return array
   */
  private function getAllAttributes() {
    /** @var \CleverReach\BusinessLogic\Interfaces\Attributes $attributesService */
    $attributesService = ServiceRegister::getService(Attributes::CLASS_NAME);
    $attributes = [
        [
          'name' => 'email',
          'type' => 'text',
        ],
        [
          'name' => 'salutation',
          'type' => 'text',
        ],
        [
          'name' => 'title',
          'type' => 'text',
        ],
        [
          'name' => 'firstname',
          'type' => 'text',
        ],
        [
          'name' => 'lastname',
          'type' => 'text',
        ],
        [
          'name' => 'street',
          'type' => 'text',
        ],
        [
          'name' => 'zip',
          'type' => 'text',
        ],
        [
          'name' => 'city',
          'type' => 'text',
        ],
        [
          'name' => 'company',
          'type' => 'text',
        ],
        [
          'name' => 'state',
          'type' => 'text',
        ],
        [
          'name' => 'country',
          'type' => 'text',
        ],
        [
          'name' => 'birthday',
          'type' => 'date',
        ],
        [
          'name' => 'phone',
          'type' => 'text',
        ],
        [
          'name' => 'shop',
          'type' => 'text',
        ],
        [
          'name' => 'customernumber',
          'type' => 'text',
        ],
        [
          'name' => 'language',
          'type' => 'text',
        ],
        [
          'name' => 'newsletter',
          'type' => 'text',
        ],
    ];

    /** @var \CleverReach\BusinessLogic\Entity\ShopAttribute $attribute */
    foreach ($attributes as &$attribute) {
      $shopAttribute = $attributesService->getAttributeByName($attribute['name']);
      $attribute['description'] = $shopAttribute->getDescription();
      $attribute['preview_value'] = $shopAttribute->getPreviewValue();
      $attribute['default_value'] = $shopAttribute->getDefaultValue();
    }

    return $attributes;
  }

}
