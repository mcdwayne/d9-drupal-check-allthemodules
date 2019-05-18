<?php

namespace Drupal\acquia_contenthub\Event;

use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired when serializing individual entity fields for syndication.
 *
 * Subscribers to this event should manipulate $this->fieldData  as necessary
 * in order place values in the proper array format for the CDF serialization
 * process. An empty $this->fieldData will prevent this key/value from being
 * syndicated.
 *
 * @see \Drupal\acquia_contenthub\AcquiaContentHubEvents
 */
class SerializeCdfEntityFieldEvent extends Event {

  /**
   * The entity being serialized.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * The name of the field being serialized.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The field being serialized.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  protected $field;

  /**
   * The array of data to serialize for this field.
   *
   * @var array
   */
  protected $fieldData;

  /**
   * The main return object of the serialization process.
   *
   * @var \Acquia\ContentHubClient\CDF\CDFObject
   */
  protected $cdf;

  /**
   * The "exclude" flag.
   *
   * If set to TRUE, the field will not be added to the CDF object.
   *
   * @var bool
   */
  protected $isExcludedField = FALSE;

  /**
   * SerializeCdfEntityFieldEvent constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to which the field belongs.
   * @param string $field_name
   *   The name of the field.
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field item list object.
   * @param \Acquia\ContentHubClient\CDF\CDFObject $cdf
   *   The CDF Object being created.
   */
  public function __construct(ContentEntityInterface $entity, $field_name, FieldItemListInterface $field, CDFObject $cdf) {
    $this->entity = $entity;
    $this->fieldName = $field_name;
    $this->field = $field;
    $this->cdf = $cdf;
  }

  /**
   * The entity to which the field belongs.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * The name of the field.
   *
   * @return string
   *   Field name.
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * The field item list object.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   Field item list object.
   */
  public function getField() {
    return $this->field;
  }

  /**
   * The field item list object for a particular language.
   *
   * @param string $langcode
   *   The language of the field value to get.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   Field item list object.
   */
  public function getFieldTranslation($langcode) {
    $entity = $this->getEntity()->getTranslation($langcode);
    return $entity->{$this->getField()->getName()};
  }

  /**
   * The field data to serialize.
   *
   * @return array
   *   Field data.
   */
  public function getFieldData() {
    return $this->fieldData;
  }

  /**
   * Set the field data to serialize.
   *
   * @param array $data
   *   Field data.
   */
  public function setFieldData(array $data) {
    $this->fieldData = $data;
  }

  /**
   * Returns the CDF Object.
   *
   * @return \Acquia\ContentHubClient\CDF\CDFObject
   *   CDF object.
   */
  public function getCdf() {
    return $this->cdf;
  }

  /**
   * Sets the "exclude" flag.
   */
  public function setExcluded() {
    $this->isExcludedField = TRUE;
  }

  /**
   * Returns the "exclude" flag state.
   *
   * @return bool
   *   "Exclude" flag state.
   */
  public function isExcluded() {
    return $this->isExcludedField;
  }

}
