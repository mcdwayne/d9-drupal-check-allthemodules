<?php

namespace Drupal\formfactory\Services;

use Drupal\kits\KitInterface;

/**
 * Interface FormFactoryInterface
 *
 * @package Drupal\formfactory
 */
interface FormFactoryInterface {
  /**
   * @param array $form
   *
   * @return FormFactoryInterface
   */
  public function load(array $form);

  /**
   * @param bool $isTree
   *
   * @return FormFactoryInterface
   */
  public function setTree($isTree = TRUE);

  /**
   * @return array
   */
  public function getForm();

  /**
   * @param KitInterface $kit
   *
   * @return FormFactoryInterface
   */
  public function append(KitInterface $kit);
}
