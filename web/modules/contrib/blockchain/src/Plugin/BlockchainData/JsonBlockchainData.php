<?php

namespace Drupal\blockchain\Plugin\BlockchainData;

use Drupal\blockchain\Plugin\BlockchainDataBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * NodeBlockchainData based on json serializable class.
 *
 * @BlockchainData(
 *  id = "json",
 *  label = @Translation("Json data"),
 *  targetClass = "Drupal\blockchain\Plugin\BlockchainData\JsonDataContainer"
 * )
 */
class JsonBlockchainData extends BlockchainDataBase {

  protected $class;

  /**
   * Getter for class definition.
   *
   * @return JsonBlockchainDataInterface
   *   Fully instantiated class.
   */
  protected function getClassInstance() {

    if (!$this->class) {
      $this->class = $this->pluginDefinition['targetClass'];
    }

    return new $this->class();
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {

    if ($data instanceof JsonBlockchainDataInterface) {
      $data = $data->toJson();
      $this->data = $this->dataToSleep($data);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {

    $class = $this->getClassInstance();

    if ($this->data) {
      $data = $this->dataWakeUp($this->data);
      $class->fromJson($data);
    }

    return $class;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawData() {

    if ($this->data) {
      return $this->data;
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getWidget() {

    return $this->getData()->getWidget();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormatter() {

    return $this->getData()->getFormatter();
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {

    foreach ($items as $key => $item) {
      $values = $item->value;
      $entity = $this->getClassInstance();
      $entity->fromArray($values);
      $this->setData($entity);
      $item->value = $this->getRawData();
    }

  }

}
