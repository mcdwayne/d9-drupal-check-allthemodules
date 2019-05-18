<?php

namespace Drupal\eloqua_app_cloud\Entity;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EloquaAppCloudServiceViewBuilder
 * @package Drupal\eloqua_app_cloud\Entity
 */
class EloquaAppCloudServiceViewBuilder extends EntityViewBuilder {

  /**
   * @var RequestStack
   */
  protected $requestStack;

  /**
   * @var PluginManagerInterface
   */
  protected $menuManager;

  /**
   * @var PluginManagerInterface
   */
  protected $firehoseManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityManagerInterface $entity_manager,
    LanguageManagerInterface $language_manager,
    RequestStack $request_context,
    PluginManagerInterface $menuManager,
    PluginManagerInterface $fireHoseManager
  ) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->requestStack = $request_context;
    $this->menuManager = $menuManager;
    $this->firehoseManager = $fireHoseManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('request_stack'),
      $container->get('plugin.manager.eloqua_app_cloud.menu_responder.processor'),
      $container->get('plugin.manager.eloqua_app_cloud.firehose_responder.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    // Go through standard entity rendering pipeline.
    $render = parent::view($entity, $view_mode, $langcode);

    // Parse the request.
    $request = $this->requestStack->getCurrentRequest();
    $query = $request->getQueryString();
    parse_str($query, $params);
    $isFromEloqua = isset($params['oauth_consumer_key']);

    // Parse out query strings or request body.
    if ($request->getMethod() === 'POST') {
      $postData = $request->getContent();
      $params = json_decode($postData, TRUE);
    }
    else {
      // Remove oauth details. Responders do not need this information.
      unset(
        $params['oauth_consumer_key'],
        $params['oauth_nonce'],
        $params['oauth_signature_method'],
        $params['oauth_timestamp'],
        $params['oauth_version'],
        $params['oauth_signature']
      );
    }

    // Only execute plugins if we're receiving a request from Eloqua.
    if (!$isFromEloqua) {
      return $render;
    }

    // Set this to uncacheable; can be modified by plugins below.
    $render['#cache']['max-age'] = 0;

    // Loop through all responders associated with this service hook.
    foreach ($entity->field_eloqua_app_cloud_responder as $responder) {
      $pluginId = $responder->get('value')->getValue();
      $instance = $this->getPluginInstance($entity->bundle(), $pluginId);
      $instance->execute($render, $params ?: array());
    }

    return $render;
  }

  /**
   * @param string $entityType
   * @param string $pluginName
   * @return object
   * @throws \Exception
   */
  protected function getPluginInstance($entityType, $pluginId) {
    switch ($entityType) {
      case 'menu':
        return $this->menuManager->createInstance($pluginId);
        break;

      case 'firehose':
        return $this->firehoseManager->createInstance($pluginId);
        break;
    }

    throw new \Exception('Plugin manager not found for service type: ' . $entityType);
  }

}
