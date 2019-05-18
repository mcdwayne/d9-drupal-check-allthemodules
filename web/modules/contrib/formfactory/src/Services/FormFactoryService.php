<?php

namespace Drupal\formfactory\Services;

use Drupal\kits\KitInterface;

/**
 * Class FormFactoryService
 *
 * @package Drupal\formfactory
 */
class FormFactoryService implements FormFactoryInterface {
  /**
   * @var array
   */
  private $form;

  /**
   * @var KitInterface[]
   */
  private $kits;

  /**
   * @param array $form
   *
   * @return FormFactoryInterface
   */
  public function load(array $form) {
    $this->form = &$form;
    return $this;
  }

  /**
   * @param bool $isTree
   *
   * @return \Drupal\formfactory\Services\FormFactoryInterface|void
   */
  public function setTree($isTree = TRUE) {
    $this->form['#tree'] = $isTree;
  }

  /**
   * @return array
   */
  public function getForm() {
    $artifact = $this->form;
    foreach($this->kits as $kit) {
      if ($kit::IS_CHILDREN_GROUPED) {
        $artifact[$kit->getID()] = $kit->getArray();
        $artifact += $kit->getChildrenArray();
      }
      else {
        $artifact[$kit->getID()] = $kit->getArray();
      }
    }
    return $artifact;
  }

  /**
   * @param KitInterface $kit
   *
   * @return FormFactoryInterface
   */
  public function append(KitInterface $kit) {
    $this->kits[] = $kit;
    return $this;
  }
}
