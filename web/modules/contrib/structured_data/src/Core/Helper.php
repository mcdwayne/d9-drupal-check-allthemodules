<?php

namespace Drupal\structured_data\Core;

use Drupal\Core\Url;

class Helper
{

  const emptyBundle = 'none';

  public static function getCurrentPageMeta($fillEmptyValues = FALSE)
  {
    $route = \Drupal::routeMatch();
    $route_name = $route->getRouteName();

    $url = Url::fromRoute('<current>');
    $urlString = $url->toString();

    $matches = [];
    $result = preg_match("/entity\\.([a-zA-Z0-9_]+)\\.canonical/", $route_name, $matches);
    if ($result == 1)
    {
      $bundle = $matches[1];
      $entity_id = $route->getRawParameter($bundle);
    }
    else
    {
      $bundle = $fillEmptyValues ? self::emptyBundle : '';
      $entity_id = $fillEmptyValues ? '0' : '';
    }

    $meta = [
      'route_name' => $route_name,
      'url' => $urlString,
      'bundle' => $bundle,
      'entity_id' => $entity_id,
    ];

    return ($meta);
  }

  public static function getPageJsonForRoute($route_name, $url = NULL)
  {
    $query = db_select('structured_data_json', 'sdj')
      ->fields('sdj')
      ->condition('route_name', $route_name);

    if (empty($url))
    {
      $query
        ->addExpression("TRIM(IFNULL(url, '')) = ''");
    }
    else
    {
      $query
        ->condition('url', $url);
    }

    $result = $query
      ->execute()
      ->fetchObject();

    return ($result);
  }

  public static function getPageJsonForEntity($bundle, $entity_id)
  {
    $query = db_select('structured_data_json', 'sdj')
      ->fields('sdj')
      ->condition('bundle', $bundle)
      ->condition('entity_id', $entity_id);

    $result = $query
      ->execute()
      ->fetchObject();

    return ($result);
  }

  public static function getPageJson($params)
  {
    $obj = (empty($params['entity_id']) || $params['entity_id'] == '0') ? self::getPageJsonForRoute($params['route_name'], $params['url']) : self::getPageJsonForEntity($params['bundle'], $params['entity_id']);
    return ($obj);
  }

  public static function updatePageJson(&$entity)
  {
    $existing_obj = self::getPageJson($entity);

    if (empty($entity['entity_id']))
    {
      unset($entity['bundle']);
      unset($entity['entity_id']);
    }

    if ($existing_obj == NULL)
    {
      $entity['id'] = db_insert('structured_data_json')
        ->fields($entity)
        ->execute();
    }
    else
    {
      db_update('structured_data_json')
        ->fields($entity)
        ->condition('id', $existing_obj->id)
        ->execute();
    }
  }

}
