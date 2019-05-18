<?php

namespace Drupal\presshub;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Presshub\Client;

class PresshubHelper {

  use StringTranslationTrait;

  protected $api;
  protected $cache;
  protected $plugin_manager;

  /**
   * Initialize required data.
   */
  public function __construct() {
    $config = \Drupal::config('presshub.settings');
    $this->cache = \Drupal::cache();
    $this->plugin_manager = \Drupal::service('plugin.manager.presshub');
    $this->api = new Client(
      $config->get('api_key'),
      $config->get('timeout'),
      $config->get('connect_timeout'),
      $config->get('api_endpoint')
    );
  }

  /**
   * Publish Presshub article.
   */
  public function preview($entity, $template) {
    // Generate previewable files, more services can be added.
    // Please follow example: 'FacebookIA' => []
    $result = $this->api->setTemplate($template)
      ->setServices($this->getServiceParams($entity))
      ->preview()
      ->execute();
  }

  /**
   * Publish Presshub article.
   */
  public function publish($entity, $template) {
    // Publish article to AppleNews and Twitter.
    // More can be added. See Get Services callback.
    $result = $this->api->setTemplate($template)
      ->setServices($this->getServiceParams($entity))
      ->publish()
      ->execute();
  }

  /**
   * Update Presshub article.
   */
  public function update($article, $entity, $template) {
    // Update article in AppleNews and Twitter
    // Please note not all services support update operation via API.
    $result = $this->api->setTemplate($template)
      ->setServices($this->getServiceParams($entity))
      // Presshub publication ID.
      ->update($article['presshub_id'])
      ->execute();
  }

  /**
   * Delete Presshub article.
   */
  public function delete($article, $entity) {
    // Delete article from AppleNews and Twitter
    // Please note not all services support delete operation via API.
    $result = $this->api->setServices($this->getServiceParams($entity))
      ->delete($article['presshub_id'])
      ->execute();
  }

  /**
   * Check if article published to Presshub.
   */
  public function isPublished($entity) {
    $article = db_select('presshub', 'p')
      ->fields('p')
      ->condition('p.entity_id', $entity->id())
      ->execute()
      ->fetchAssoc();
    if (!empty($article['presshub_id'])) {
      return $article;
    }
    return FALSE;
  }

  /**
   * Get Apple News sections.
   */
  public function getAppleNewsSections() {
    $sections = [];
    if ($cached = $this->cache->get('presshub_apple_sections')) {
      $sections = $cached->data;
    }
    else {
      $result = $this->api->getAppleNewsSections()
        ->execute();
      if ($result->data) {
        foreach ($result->data as $info) {
          $sections[$info->id] = $info->name;
        }
      }
      $this->cache->set('presshub_apple_sections', $sections);
    }
    return $sections;
  }

  /**
   * Get Presshub services.
   */
  public function getServices() {
    $services = [];
    if ($cached = $this->cache->get('presshub_services')) {
      $services = $cached->data;
    }
    else {
      $result = $this->api->getServices()
        ->execute();
      if ($result->data) {
        foreach ($result->data as $service => $info) {
          $info = $this->api->getService($service)
            ->execute();
          if (!empty($info->info->connected_to)) {
            $services[$service] = $service . ' (' . $info->info->connected_to . ')';
          }
        }
      }
      $this->cache->set('presshub_services', $services);
    }
    return $services;
  }

  /**
   * Get Presshub templates (plugins).
   */
  public function getTemplates() {
    $templates = [];
    $manager = $this->plugin_manager;
    foreach ($manager->getDefinitions() as $id => $template) {
      $object = $manager->createInstance($template['id']);
      $templates[$id] = [
        'name'         => (string) $object->getName(),
        'entity_types' => $object->getEntityTypes(),
      ];
    }
    return $templates;
  }

  /**
   * Generate Presshub template.
   */
  public function generateTemplate($entity) {
    $entity_template = db_select('presshub_templates', 't')
      ->fields('t', ['template'])
      ->condition('t.entity_type', $entity->bundle())
      ->execute()
      ->fetchField();
    $manager = $this->plugin_manager;
    foreach ($manager->getDefinitions() as $id => $template) {
      if ($object = $manager->createInstance($template['id'])) {
        // Check if content is publishable.
        if ($object->isPublishable($entity) && $entity_template == $template['id']) {
          return $object->template($entity);
        }
      }
    }
  }

  /**
   * Get services.
   */
  public function getServiceParams($entity) {
    $entity_template = db_select('presshub_templates', 't')
      ->fields('t', ['template'])
      ->condition('t.entity_type', $entity->bundle())
      ->execute()
      ->fetchField();
    $manager = $this->plugin_manager;
    foreach ($manager->getDefinitions() as $id => $template) {
      if ($object = $manager->createInstance($template['id'])) {
        // Check if content is publishable.
        if ($entity_template == $template['id']) {
          return $object->setServiceParams($entity);
        }
      }
    }
  }

  /**
   * Get AMP version of Drupal node.
   */
  public function getAmpVersion($entity_id) {
    $content = db_select('presshub_amp', 'a')
      ->fields('a', ['content'])
      ->condition('a.entity_id', $entity_id)
      ->execute()
      ->fetchField();
    return $content;
  }

}
