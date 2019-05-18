<?php

namespace Drupal\druminate\Plugin;

/**
 * Defines an interface for Druminate Endpoint plugins.
 */
interface DruminateEndpointInterface {

  /**
   * Retrieve the @servlet property from the annotation and return it.
   *
   * @return string
   *   Api Servlet.
   */
  public function getServlet();

  /**
   * Retrieve the @method property from the annotation and returns it.
   *
   * @return string
   *   Api method.
   */
  public function getMethod();

  /**
   * Retrieve the @params property from the annotation and returns it.
   *
   * @return array
   *   Api parameters.
   */
  public function getParams();

  /**
   * Retrieve the @authRequired property from the annotation and returns it.
   *
   * @return bool
   *   True or False.
   */
  public function authRequired();

  /**
   * Retrieve the isFrozen property from the configuration and returns it.
   *
   * @return bool
   *   True or False.
   */
  public function isFrozen();

  /**
   * Retrieve the @cacheLifetime property from the annotation and returns it.
   *
   * @return bool
   *   True or False.
   */
  public function cacheLifetime();

  /**
   * Function used to either load data from api or database.
   *
   * @return bool|mixed
   *   Druminate Api Response.
   */
  public function loadData();

  /**
   * Retrieve the @customUrl property from the annotation and returns it.
   *
   * If the url is not present one is generated from the api servlet.
   *
   * @return string
   *   Api Url.
   */
  public function getCustomUrl();

  /**
   * Retrieve the @httpRequestMethod property from the annotation and return it.
   *
   * Must be of type 'POST' or 'GET'. Defaults to 'GET'.
   *
   * @return string
   *   HTTP Request Method.
   */
  public function getHttpRequestMethod();

}
