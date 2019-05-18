<?php

namespace Drupal\eloqua_app_cloud\Controller;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\Renderer;
use Drupal\eloqua_app_cloud\Exception\EloquaAppCloudApiException;
use Drupal\eloqua_app_cloud\Exception\EloquaAppCloudInstanceIdNotFoundException;
use Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudInteractiveResponderBase;
use Drupal\eloqua_rest_api\Factory\ClientFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class EndpointControllerBase.
 *
 * @property pluginManagerService
 * @package Drupal\eloqua_app_cloud\Controller
 */
class EloquaAppCloudEndpointController extends ControllerBase {

  /**
   * @var \Eloqua\Client
   */
  protected $eloqua;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * @var LanguageManagerInterface
   */
  protected $langManager;

  /**
   * @var EntityTypeManager
   */
  protected $entityManager;

  /**
   * @var QueueFactory
   */
  protected $queueFactory;

  /**
   * @var array $plugins
   */
  protected $plugins;

  /**
   * @var  LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * EndpointControllerBase constructor.
   *
   * @param \Drupal\eloqua_app_cloud\Controller\ClientFactory|\Drupal\eloqua_rest_api\Factory\ClientFactory $eloquaFactory
   * @param \Drupal\eloqua_app_cloud\Controller\RequestStack|\Symfony\Component\HttpFoundation\RequestStack $requestStack
   * @param \Drupal\Core\Language\LanguageManagerInterface $langManager
   * @param \Drupal\Core\Entity\EntityTypeManager $entityManager
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   * @param array $plugins
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Render\Renderer $renderer
   *
   * @internal param \Drupal\Core\Queue\QueueFactory $queue
   */
  public function __construct(ClientFactory $eloquaFactory, array $plugins, RequestStack $requestStack, LanguageManagerInterface $langManager, EntityTypeManager $entityManager, QueueFactory $queueFactory, LoggerInterface $logger, Renderer $renderer) {
    $this->eloqua = $eloquaFactory->get();
    $this->request = $requestStack->getCurrentRequest();
    $this->langManager = $langManager;
    $this->entityManager = $entityManager;
    $this->queueFactory = $queueFactory;
    $this->plugins = $plugins;
    $this->logger = $logger;
    $this->renderer = $renderer;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    // Get the list of plugin managers in our "namespace" this way we can manage any plugin
    // that is added later.
    $plugins = [];
    foreach ($container->getServiceIds() as $serviceId) {
      if (strpos($serviceId, 'plugin.manager.eloqua_app_cloud') === 0) {
        $type = $container->get($serviceId);
        $plugin_definitions = $type->getDefinitions();
        foreach ($plugin_definitions as $plugin) {
          $plugins[$plugin['id']] = $type;
        }
      }
    }

    return new static(
      $container->get('eloqua.client_factory'),
      $plugins,
      $container->get('request_stack'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('queue'),
      $container->get('logger.channel.eloqua_app_cloud'),
      $container->get('renderer')
    );
  }

  /**
   * "Instantiate" (i.e. Eloqua create call) endpoint.
   * The main purpose is to return a set a field list and other configuration to Eloqua.
   *
   * @param $eloquaAppCloudService
   *
   * @return mixed
   */
  public function instantiate($eloquaAppCloudService) {
    $query = $this->request->query->all();

    // Get the instanceID from the query parameter.
    $instanceId = $this->getInstanceId($query);
    $pluginReferences = $this->getEntityPlugins($eloquaAppCloudService);

    // Punting on the case where there are multiple plugins for a service.
    // This code will always return the instantiate response for the last plugin in the list.
    foreach ($pluginReferences as $pluginReference) {
      $id = $pluginReference->value;
      // Get the plugin manager from the list of plugins (from the container).
      $pluginMgr = $this->plugins[$id];
      // Instantiate the referenced plugin.
      $plugin = $pluginMgr->createInstance($id);
      $instantiate = $plugin->instantiate($instanceId, $query);
    }
    $this->logger->debug('Received instantiate service hook with payload @fieldList', [
      '@fieldList' => print_r($this->getFieldList($pluginReferences), TRUE),
    ]);
    return new JsonResponse($instantiate, 200);
  }

  /**
   * Return an array of plugins as referenced by the service entity.
   *
   * @param $eloquaAppCloudService
   *
   * @return mixed
   */
  private function getEntityPlugins($eloquaAppCloudService) {
    //Get the service entity defined at this route.
    $entity = $this->entityManager->getStorage('eloqua_app_cloud_service')
      ->load($eloquaAppCloudService);
    // Return the list of plugin references.
    return $entity->field_eloqua_app_cloud_responder->getIterator();
  }

  /**
   * Return a list of all the eloqua fields (as found in plugin annotation) required by a list of plugins.
   *
   * @param $pluginReferences
   *
   * @return array
   *
   * @throws \Drupal\eloqua_app_cloud\Exception\EloquaAppCloudApiException
   */
  private function getFieldList($pluginReferences) {
    // Iterate over the ServiceEntity plugins and build a merged field list.
    $fieldLists = [];
    foreach ($pluginReferences as $pluginReference) {
      $id = $pluginReference->value;
      // Get the plugin manager from the list of plugins (from the container).
      $pluginMgr = $this->plugins[$id];
      // Instantiate the referenced plugin.
      $plugin = $pluginMgr->createInstance($id);
      // We can only allow ONE type of API at a time.
      if (!empty($api) && $api !== $plugin->api()) {
        // Throw an exception!
        throw new EloquaAppCloudApiException('Multiple API types found, only one allowed (i.e. contacts or Custom Objects).');
      }
      $api = $plugin->api();
      // Merge the required field lists of all the listed plugins.
      $fieldLists = array_merge($fieldLists, $plugin->fieldList());
    }
    return $fieldLists;
  }

  /**
   * Returns HTML to be displayed in a popup configure window on the Eloqua
   * canvas. If required it should also return an bulk API request to update
   * the field list for this service.
   *
   * @TODO: implement a way to request an updated field list :)
   *   (https://github.com/tableau-mkt/eloqua_app_cloud/issues/3)
   *
   * @param $eloquaAppCloudService
   *
   * @return mixed
   */
  public function update($eloquaAppCloudService) {
    $query = $this->request->query->all();
    // Get the instanceID from the query parameter.
    $instanceId = $this->getInstanceId($query);
    $pluginReferences = $this->getEntityPlugins($eloquaAppCloudService);

    $response = [
      '#theme' => 'eloqua_app_cloud_update_dialog',
    ];
    // Punting on the case where there are multiple plugins for a service.
    // This code will always return all the instantiate responses for the
    // last plugin in the list, but does not try to help if they break each other.
    foreach ($pluginReferences as $pluginReference) {
      $id = $pluginReference->value;
      // Get the plugin manager from the list of plugins (from the container).
      $pluginMgr = $this->plugins[$id];
      // Instantiate the referenced plugin.
      $plugin = $pluginMgr->createInstance($id);
      $update = $plugin->update($instanceId, $query);
      $response['#content'][$plugin->getPluginId()] = $update;
    }
    return $response;
  }

  /**
   * Delete any existing queue entries for this service entity.
   *
   * @return mixed
   */
  public function delete($eloquaAppCloudService) {
    $query = $this->request->query->all();
    // Get the instanceID from the query parameter.
    $instanceId = $this->getInstanceId($query);
    $pluginReferences = $this->getEntityPlugins($eloquaAppCloudService);

    // Iterate over the ServiceEntity plugins and then over the payload items.
    foreach ($pluginReferences as $pluginReference) {
      $id = $pluginReference->value;
      // Get the plugin manager from the list of plugins (from the container).
      $pluginMgr = $this->plugins[$id];
      // Instantiate the referenced plugin.
      $plugin = $pluginMgr->createInstance($id);
      $plugin->delete($instanceId, $query);
      // Get the appropriate queue for this plugin.
      $queue = $this->queueFactory->get($plugin->queueWorker());
      $queueCount = $queue->numberOfItems();

      for ($i = 0; $i <= $queueCount; $i++) {
        $queueItem = $queue->claimItem();
        // Only delete the item if it is part of our instance.
        // If the claim returned false then assume the queue is empty.
        if ($queueItem && $queueItem->instanceId === $instanceId) {
          $queue->deleteItem($queueItem);
        }
      }
    }
    return new HtmlResponse('Deleted.', 200);
  }

  /**
   * Execute (i.e. Eloqua notify) endpoint.
   * Loops over any plugins referenced by the service entity and calls their execute method.
   * Collects the results an either returns them synchronously (for syncronous content)
   * or queues them to be returned via the Eloqua bulk API.
   *
   * Must always return SOMETHING and if the results are queued it must return
   * a status 204 to indicate an async action.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function execute($eloquaAppCloudService) {
    $query = $this->request->query->all();
    $instanceId = $this->getInstanceId($query);
    // Get the execution ID
    $executionId = $this->request->get("executionId");
    if (empty($executionId)) {
      $this->logger->error('No executionId found for @eloqua_app_cloud_service.', ['@eloqua_app_cloud_service' => $eloquaAppCloudService]);
      // It apparently does not matter what we return here, since Eloqua does not have any way of handing external errors?
      return new HtmlResponse('No executionId found.', 500);
    }
    // Now load the JSON payload form Eloqua.
    $content = $this->request->getContent();

    if (empty($content)) {
      $this->logger->error('No content found');
      // It apparently does not matter what we return here, since Eloqua does not have any way of handing external errors?
      return new HtmlResponse('Error no no content found.', 500);
    }
    $payload = json_decode($content);
    $records = $payload->items;
    $this->logger->debug('Received execute service hook with payload @records', [
      '@record' => print_r($records, TRUE),
    ]);
    $pluginReferences = $this->getEntityPlugins($eloquaAppCloudService);

    // Iterate over the ServiceEntity plugins and then over the payload items.
    foreach ($pluginReferences as $pluginReference) {
      $id = $pluginReference->value;
      // Get the plugin manager from the list of plugins (from the container).
      $pluginMgr = $this->plugins[$id];
      // Instantiate the referenced plugin.
      $plugin = $pluginMgr->createInstance($id);
      // Is this a sync or async (bulk) plugin? If the annotation is empty then assume that it is async.
      if (!empty($plugin->respond()) && $plugin->respond() === 'synchronous') {
        $this->logger->debug('The @plugin plugin requested a synchronous response.', ['@plugin' => $plugin->getPluginId()]);
        // Merge all the responses into one array.
        // TODO: Will this even work?
        $response = $this->respondSynchronously($plugin, $records, $instanceId, $executionId, $query);
        $responseHtml = $this->renderer->renderRoot($response);
        $result = new CacheableResponse($responseHtml);
        $result->addCacheableDependency($response);
        return $result;
      }
      else {
        $this->logger->debug('The @plugin plugin requested an asynchronous response.', ['@plugin' => $plugin->getPluginId()]);
        $response = $this->respondAsynchronously($plugin, $records, $instanceId, $executionId, $query);
        $json = new JsonResponse($response);
        $json->setStatusCode(204);
      }
    }
    return $json;
  }

  /**
   * Content "module" plugins that are annotated as synchronous need to be executed and returned immediately.
   * Returns must be in the form of a renderable object.
   *
   * @param $plugin
   * @param $records
   * @param $instanceId
   *
   * @return \stdClass
   */
  protected function respondSynchronously($plugin, $records, $instanceId, $executionId, $query) {
    $response = new \stdClass();
    // The response will be the same for all contacts, but we need one "record".
    $response = $plugin->execute($instanceId, new \stdClass(), $query);
    return $response;
  }

  /**
   * Any plugin annotated as responding asynchronously
   * @param $plugin
   * @param $records
   * @param $instanceId
   * @param $executionId
   *
   * @return \stdClass
   */
  protected function respondAsynchronously($plugin, $records, $instanceId, $executionId, $query) {
    // Get the appropriate queue for this plugin.
    $queue = $this->queueFactory->get($plugin->queueWorker());
    // Put the records directly onto on the queue.
    foreach ($records as $record) {
      // Let the plugin manipulate the record as needed.
      $plugin->execute($instanceId, $record, $query);
    }
    // @TODO Define a queueItem class?
    $queueItem = new \stdClass();
    // Pass the queue type to the worker to make it easy to requeue if there are more then 5000 records.
    $queueItem->queueWorker = $plugin->queueWorker();
    // Pass the instance ID and Execution ID so the worker can communicate with Eloqua.
    $queueItem->instanceId = $instanceId;
    $queueItem->executionId = $executionId;

    $queueItem->api = $plugin->api();
    $queueItem->fieldList = $plugin->fieldList();
    $queueItem->records = $records;
    $queue->createItem($queueItem);
    // If we have gotten through all that we just return a 204 to indicate an asynchronous response.
    $response = new \stdClass();
    return $response;
  }

  /**
   * Get the label of the plugin to use as a page title.
   *
   * @param \Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudInteractiveResponderBase $plugin
   *
   * @return string
   */
  public function getTitle($eloquaAppCloudService = NULL) {
    $pluginReferences = $this->getEntityPlugins($eloquaAppCloudService);
    $title = "";
    foreach ($pluginReferences as $pluginReference) {
      $id = $pluginReference->value;
      // Get the plugin manager from the list of plugins (from the container).
      $pluginMgr = $this->plugins[$id];
      // Instantiate the referenced plugin.
      /** @var EloquaAppCloudInteractiveResponderBase $plugin */
      $plugin = $pluginMgr->createInstance($id);
      // Just concatenate the titles if there are multiple plugins.
      $title .= ' ' . $plugin->label();
    }
    return $title;
  }

  /**
   * @param $query
   *
   * @return $instanceId
   */
  private function getInstanceId($query) {
    $instanceId = $query["instance"];
    if (empty($instanceId)) {
      $this->logger->error('No instanceID found');
      // It apparently does not matter what we return here, since Eloqua does not have any way of handing external errors?
      return new HtmlResponse('No instanceID found.', 500);
    }
    return $instanceId;
  }
}
