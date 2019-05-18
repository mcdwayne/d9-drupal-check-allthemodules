<?php

namespace Drupal\child_entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

abstract class ChildContentEntityForm extends ContentEntityForm {

  /**
   * @var EntityInterface
   */
  private $parentEntity;

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = null) {
    $this->setParentEntity($this->getParentEntityFromRoute());
    return parent::buildForm($form, $form_state);
  }

  /**
   * @inheritDoc
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\child_entity\Entity\ChildContentEntityBase $entity */
    $entity = $this->entity;
    $entity->setParentEntity($this->getParentEntityFromRoute());
    $this->setParentEntity($this->getParentEntityFromRoute());
    return parent::save($form, $form_state);
  }


  /**
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function getParentEntity(){
    return $this->parentEntity;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return $this
   */
  protected function setParentEntity(EntityInterface $entity){
    $this->parentEntity = $entity;
    return $this;
  }

  /**
   * @return EntityInterface
   */
  private function getParentEntityFromRoute() {
    /** @var \Drupal\child_entity\Entity\ChildContentEntityBase $entity */
    $entity = $this->entity;
    return \Drupal::getContainer()
      ->get('request_stack')
      ->getMasterRequest()
      ->get($entity->getParentKeyInRoute());
  }
}