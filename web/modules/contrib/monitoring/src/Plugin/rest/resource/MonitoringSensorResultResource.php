<?php

/**
 * @file
 * Definition of Drupal\monitoring\Plugin\rest\resource\MonitoringSensorInfoResource.
 */

namespace Drupal\monitoring\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\monitoring\Sensor\DisabledSensorException;
use Drupal\monitoring\Sensor\NonExistingSensorException;
use Drupal\monitoring\Sensor\SensorManager;
use Drupal\monitoring\SensorRunner;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource for monitoring sensors results.
 *
 * @RestResource(
 *   id = "monitoring-sensor-result",
 *   label = @Translation("Monitoring sensor result")
 * )
 */
class MonitoringSensorResultResource extends ResourceBase {

  /**
   * The sensor manager.
   *
   * @var \Drupal\monitoring\Sensor\SensorManager
   */
  protected $sensorManager;

  /**
   * The sensor runner.
   *
   * @var \Drupal\monitoring\SensorRunner
   */
  protected $sensorRunner;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  public function __construct(array $configuration, $plugin_id, array $plugin_definition, array $serializer_formats, SensorManager $sensor_manager, SensorRunner $sensor_runner, LoggerInterface $logger, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->sensorManager = $sensor_manager;
    $this->sensorRunner = $sensor_runner;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('monitoring.sensor_manager'),
      $container->get('monitoring.sensor_runner'),
      $container->get('logger.factory')->get('rest'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $path_prefix = strtr($this->pluginId, ':', '/');
    $route_name = strtr($this->pluginId, ':', '.');

    $collection = parent::routes();
    $route = new Route("/$path_prefix", array(
      '_controller' => 'Drupal\rest\RequestHandler::handle',
      // Pass the resource plugin ID along as default property.
      '_plugin' => $this->pluginId,
    ), array(
      '_permission' => "restful get $this->pluginId",
    ));
    $route->setMethods(['GET']);
    foreach ($this->serializerFormats as $format_name) {
      // Expose one route per available format.
      $format_route = clone $route;
      $format_route->addRequirements(array('_format' => $format_name));
      $collection->add("$route_name.list.$format_name", $format_route);
    }
    return $collection;
  }

  /**
   * Responds to sensor INFO GET requests.
   *
   * @param string $id
   *   (optional) The sensor name, returns a list of all sensors when empty.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the sensor config.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get($id = NULL) {
    $request = \Drupal::request();

    $format = $request->getRequestFormat('ĵson');

    if ($id) {
      try {
        $sensor_config[$id] = $this->sensorManager->getSensorConfigByName($id);

        // Some sensors might render or do things that we can not properly
        // collect cacheability metadata for. So, run it in our own render
        // context. For example, one is the run cron link of the system.module
        // requirements hook.
        $context = new RenderContext();
        $sensor_runner = $this->sensorRunner;
        $result = $this->renderer->executeInRenderContext($context, function() use ($sensor_runner, $sensor_config) {
          return $sensor_runner->runSensors($sensor_config);
        });
        $response = $result[$id]->toArray();
        $url = Url::fromRoute('rest.monitoring-sensor-result.GET.' . $format, ['id' => $id, '_format' => $format])->setAbsolute()->toString(TRUE);
        $response['uri'] = $url->getGeneratedUrl();
        if ($request->get('expand') == 'sensor') {
          $response['sensor'] = $result[$id]->getSensorConfig()->toArray();
        }
        $response = new ResourceResponse($response);
        $response->addCacheableDependency($result[$id]->getSensorConfig());
        $response->addCacheableDependency($url);
        $response->addCacheableDependency(CacheableMetadata::createFromRenderArray([
          '#cache' => [
            'contexts' => [0 => 'url.query_args'],
            'max-age' => 0,
          ],
        ]));
        if (!$context->isEmpty()) {
          $response->addCacheableDependency($context->pop());
        }

        return $response;
      }
      catch (NonExistingSensorException $e) {
        throw new NotFoundHttpException($e->getMessage(), $e);
      }
      catch (DisabledSensorException $e) {
        throw new NotFoundHttpException($e->getMessage(), $e);
      }
    }
    else {
      $list = array();
      $cacheable_metadata = new CacheableMetadata();

      // Some sensors might render or do things that we can not properly
      // collect cacheability metadata for. So, run it in our own render
      // context. For example, one is the run cron link of the system.module
      // requirements hook.
      $context = new RenderContext();
      $sensor_runner = $this->sensorRunner;
      $results = \Drupal::service('renderer')->executeInRenderContext($context, function() use ($sensor_runner) {
        return $sensor_runner->runSensors();
      });

      foreach ($results as $id => $result) {
        $list[$id] = $result->toArray();
        $url = Url::fromRoute('rest.monitoring-sensor-result.GET.' . $format, ['id' => $id, '_format' => $format])->setAbsolute()->toString(TRUE);
        $list[$id]['uri'] = $url->getGeneratedUrl();
        if ($request->get('expand') == 'sensor') {
          $list[$id]['sensor'] = $result->getSensorConfig()->toArray();
        }
        $cacheable_metadata = $cacheable_metadata->merge($url);
        $cacheable_metadata = $cacheable_metadata->merge(CacheableMetadata::createFromObject($result->getSensorConfig()));
        $cacheable_metadata = $cacheable_metadata->merge(CacheableMetadata::createFromRenderArray([
          '#cache' => [
            'max-age' => 0,
          ],
        ]));
      }
      $response = new ResourceResponse($list);
      $response->addCacheableDependency($cacheable_metadata);

      if (!$context->isEmpty()) {
        $response->addCacheableDependency($context->pop());
      }

      return $response;
    }

  }

}
