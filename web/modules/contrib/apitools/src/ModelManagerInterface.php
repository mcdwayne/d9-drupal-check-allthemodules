<?php

namespace Drupal\apitools;

interface ModelManagerInterface {

  /**
   * Create a new ApiTools Model object.
   *
   * @param $model_name
   *   The plugin id for the ApiTools Model.
   * @param array $values
   *   An array of data associated with the ApiTools Model.
   *
   * @return Modelinterface|bool
   */
  public function getModel($model_name, array $values = []);

  /**
   * @param $model_name
   *   The plugin id for the ApiTools Model.
   *
   * @return ModelControllerInterface|bool
   */
  public function getModelController($model_name, $provider_name);

  /**
   * @param $client_method
   *   The machine name used for the client method.
   *
   * @return ModelControllerInterface|bool
   */
  public function getModelControllerByMethod($client_method, $provider_name);

  /**
   * Get a plugin definition by a client method.
   *
   * @return array|bool
   */
  public function getDefinitionByMethod($client_method);
}
