<?php

namespace Drupal\search_api_solr_pro\Item;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\LoggerTrait;

/**
 * Provides a default implementation for a search item.
 */
class ItemPro implements \IteratorAggregate, ItemInterface {

  use LoggerTrait;

  /**
   * The search index with which this item is associated.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The ID of this item.
   *
   * @var string
   */
  protected $itemId;

  /**
   * The complex data item this Search API item is based on.
   *
   * @var \Drupal\Core\TypedData\ComplexDataInterface
   */
  protected $originalObject;

  /**
   * The ID of this item's datasource.
   *
   * @var string
   */
  protected $datasourceId;

  /**
   * The datasource of this item.
   *
   * @var \Drupal\search_api\Datasource\DatasourceInterface
   */
  protected $datasource;

  /**
   * The language code of this item.
   *
   * @var string
   */
  protected $language;

  /**
   * The extracted fields of this item.
   *
   * @var \Drupal\search_api\Item\FieldInterface[]
   */
  protected $fields = [];

  /**
   * Whether the fields were already extracted for this item.
   *
   * @var bool
   */
  protected $fieldsExtracted = FALSE;

  /**
   * The HTML text with highlighted text-parts that match the query.
   *
   * @var string
   */
  protected $excerpt;

  /**
   * The score this item had as a result in a corresponding search query.
   *
   * @var float
   */
  protected $score = 1.0;

  /**
   * The boost of this item at indexing time.
   *
   * @var float
   */
  protected $boost = 1.0;

  /**
   * Extra data set on this item.
   *
   * @var array
   */
  protected $extraData = [];

  /**
   * Constructs an Item object.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The item's search index.
   * @param string $id
   *   The ID of this item.
   * @param \Drupal\search_api\Datasource\DatasourceInterface|null $datasource
   *   (optional) The datasource of this item. If not set, it will be determined
   *   from the ID and loaded from the index.
   */
  public function __construct($id, DatasourceInterface $datasource = NULL) {
    $this->index = null;
    $this->itemId = $id;
    if ($datasource) {
      $this->datasource = $datasource;
      $this->datasourceId = $datasource->getPluginId();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasourceId() {
    if (!isset($this->datasourceId)) {
      list($this->datasourceId) = Utility::splitCombinedId($this->itemId);
    }
    return $this->datasourceId;
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasource() {
    return $this->datasource;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndex() {
    return $this->index;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage() {
    return $this->language;
  }

  /**
   * {@inheritdoc}
   */
  public function setLanguage($language) {
    $this->language = $language;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->itemId;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalObject($load = TRUE) {
    return $this->originalObject;
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginalObject(ComplexDataInterface $original_object) {
    $this->originalObject = $original_object;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getField($field_id, $extract = true) {
    return isset($fields[$field_id]) ? $fields[$field_id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($extract = true) {
    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function setField($field_id, FieldInterface $field = NULL) {
    if ($field) {
      if ($field->getFieldIdentifier() !== $field_id) {
        throw new \InvalidArgumentException('The field identifier passed must be consistent with the identifier set on the field object.');
      }
      $this->fields[$field_id] = $field;
    }
    else {
      unset($this->fields[$field_id]);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $fields) {
    $this->fields = $fields;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldsExtracted() {
    return $this->fieldsExtracted;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldsExtracted($fields_extracted) {
    $this->fieldsExtracted = $fields_extracted;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getScore() {
    return $this->score;
  }

  /**
   * {@inheritdoc}
   */
  public function setScore($score) {
    $this->score = $score;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBoost() {
    return $this->boost;
  }

  /**
   * {@inheritdoc}
   */
  public function setBoost($boost) {
    $this->boost = $boost;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExcerpt() {
    return $this->excerpt;
  }

  /**
   * {@inheritdoc}
   */
  public function setExcerpt($excerpt) {
    $this->excerpt = $excerpt;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasExtraData($key) {
    return array_key_exists($key, $this->extraData);
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraData($key, $default = NULL) {
    return array_key_exists($key, $this->extraData) ? $this->extraData[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllExtraData() {
    return $this->extraData;
  }

  /**
   * {@inheritdoc}
   */
  public function setExtraData($key, $data = NULL) {
    if (isset($data)) {
      $this->extraData[$key] = $data;
    }
    else {
      unset($this->extraData[$key]);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(AccountInterface $account = NULL) {
    //we are not loading the entity so there is no access to check
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->getFields());
  }

  /**
   * Implements the magic __toString() method to simplify debugging.
   */
  public function __toString() {
    $out = 'Item ' . $this->getId();
    if ($this->getScore() != 1) {
      $out .= "\nScore: " . $this->getScore();
    }
    if ($this->getBoost() != 1) {
      $out .= "\nBoost: " . $this->getBoost();
    }
    if ($this->getExcerpt()) {
      $excerpt = str_replace("\n", "\n  ", $this->getExcerpt());
      $out .= "\nExcerpt: $excerpt";
    }
    if ($this->getFields()) {
      $out .= "\nFields:";
      foreach ($this->getFields() as $field) {
        $field = str_replace("\n", "\n  ", "$field");
        $out .= "\n- " . $field;
      }
    }
    if ($this->getAllExtraData()) {
      $data = str_replace("\n", "\n  ", print_r($this->getAllExtraData(), TRUE));
      $out .= "\nExtra data: " . $data;
    }
    return $out;
  }

}
