<?php

namespace Drupal\zendesk_tickets\Zendesk\Resources\Core;

use Zendesk\API\Resources\Core\Tickets as ZendeskApiTickets;

/**
 * Customized Zendesk HttpClient.
 */
class Tickets extends ZendeskApiTickets {

  /**
   * {@inheritdoc}
   *
   * Overide to restrict the valid endpoints.
   */
  public static function getValidSubResources() {
    return parent::getValidSubResources();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpRoutes() {
    $this->routes = [];
  }

  /**
   * {@inheritdoc}
   */
  public function find($id = NULL, array $queryParams = [], $routeKey = __FUNCTION__) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function findAll(array $params = [], $routeKey = __FUNCTION__) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function findMany(array $ids = [], $extraParams = [], $key = 'ids') {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function update($id = NULL, array $updateResourceFields = [], $routeKey = __FUNCTION__) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function updateMany(array $params, $key = 'ids') {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($id = NULL, $routeKey = __FUNCTION__) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMany(array $ids = [], $key = 'ids') {
    return NULL;
  }

}
