<?php

namespace Drupal\smartwaiver;

interface ClientInterface {

  /**
   * Get a waiver object from the API.
   *
   * @param $waiver_id
   *
   * @return bool|object
   */
  public function waiver($waiver_id);

  /**
   * Get a list of waiver objects from the API.
   *
   * @param array $options
   *
   * @return array|mixed
   */
  public function waivers($options = []);

  /**
   * Get a list of templates from the API.
   *
   * @return array|mixed
   */
  public function templates();

}
