<?php

namespace Drupal\drupal_yext\YextContent;

use Drupal\node\Entity\Node;

/**
 * A Yext-specific node entity.
 */
class YextTargetNode extends YextEntity implements NodeMigrateDestinationInterface {

  /**
   * {@inheritdoc}
   */
  public function generate() {
    $type = $this->yext()->yextNodeType();

    $node = Node::create([
      'type' => $type,
      'title' => 'Generated ' . $type,
    ]);

    $node->save();

    $this->setEntity($node);
  }

  /**
   * {@inheritdoc}
   */
  public function getYextLastUpdate() : int {
    $value = $this->fieldValue($this->yext()->uniqueYextLastUpdatedFieldName());
    if (empty($value)) {
      // Never updated. This can happen on the first try.
      return 0;
    }
    if (!is_numeric($value)) {
      throw new \Exception('Yext last updated should be numeric, not ' . $value);
    }
    return (int) $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getYextRawDataString() : string {
    return $this->fieldValue($this->fieldmap()->raw());
  }

  /**
   * {@inheritdoc}
   */
  public function getYextRawDataArray() : array {
    $json = $this->getYextRawDataString();
    $decoded = json_decode($json, TRUE);
    if (!is_array($decoded)) {
      return [];
    }
    return $decoded;
  }

  /**
   * Get the type of this node if possible.
   *
   * @return string
   *   The node type.
   *
   * @throws Exception
   */
  public function nodeType() : string {
    return $this->drupalEntity()->getType();
  }

  /**
   * {@inheritdoc}
   */
  public function setBio(string $bio) {
    $this->drupal_entity->{$this->fieldmap()->bio()} = [
      'value' => $bio,
      'format' => 'basic_html',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setGeo(array $geo) {
    if ($geofield = $this->fieldmap()->geo()) {
      if (!empty($geo['lat'])) {
        $value = 'POINT (' . $geo['lon'] . ' ' . $geo['lat'] . ')';
        $this->drupal_entity->set($geofield, $value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setCustom(string $id, string $value) {
    $this->drupal_entity->set($id, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setHeadshot(string $url) {
    if ($url) {
      $this->imageFromWebToField($url, $this->drupal_entity, $this->fieldmap()->headshot());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setName(string $name) {
    $this->drupal_entity->setTitle($name);
  }

  /**
   * {@inheritdoc}
   */
  public function setYextId(string $id) {
    $this->drupal_entity->set($this->yext()->uniqueYextIdFieldName(), $id);
  }

  /**
   * {@inheritdoc}
   */
  public function setYextLastUpdate(int $timestamp) {
    $this->drupal_entity->set($this->yext()->uniqueYextLastUpdatedFieldName(), $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function setYextRawData(string $data) {
    $this->drupal_entity->set($this->fieldmap()->raw(), $data);
  }

}
