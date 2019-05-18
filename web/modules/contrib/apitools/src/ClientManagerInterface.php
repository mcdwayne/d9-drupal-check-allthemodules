<?php
 
namespace Drupal\apitools;

interface ClientManagerInterface {

  /**
   * @return ModelManagerInterface
   */
  public function getModelManager();

  /**
   * @param $id
   * @param array $options
   *
   * @return mixed
   */
  public function load($id, array $options = []);
}
