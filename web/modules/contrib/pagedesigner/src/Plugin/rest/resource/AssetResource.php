<?php

namespace Drupal\pagedesigner\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "pagedesigner_asset",
 *   label = @Translation("Asset resource"),
 *   uri_paths = {
 *     "canonical" = "/pagedesigner/asset/{asset_type}"
 *   }
 * )
 */
class AssetResource extends ResourceBase
{

    /**
     * A current user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    /**
     * Constructs a new AssetResource object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param array $serializer_formats
     *   The available serialization formats.
     * @param \Psr\Log\LoggerInterface $logger
     *   A logger instance.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   A current user instance.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger,
        AccountProxyInterface $current_user) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

        $this->currentUser = $current_user;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('pagedesigner'),
            $container->get('current_user')
        );
    }

    /**
     * Responds to GET requests.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   The entity object.
     *
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function get($asset_type, EntityInterface $entity = null)
    {

        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

// You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        // if (!$this->currentUser->hasPermission('access content')) {
        //     throw new AccessDeniedHttpException();
        // }
        // $build = [];
        // $entity = Element::load($id);
        // $handlers = $this->handlerManager->getInstance(['type' => $entity->bundle()]);
        // foreach ($handlers as $handler) {
        //     $build = array_merge($build, $handler->serialize($entity));
        // }
        $list = [
            [
                'src' => 'http://placehold.it/350x250/0000ff/fff/image1.jpg',
                'name' => 'Image 1',
                'type' => 'image',
                'id' => 10000000000000
            ],
            [
                'src' => 'http://placehold.it/350x250/ff0000/fff/image2.jpg',
                'name' => 'Image 2',
                'type' => 'image',
                'id' => 200000000000000
            ],
            [
                'src' => 'http://placehold.it/350x250/00ff00/fff/image3.jpg',
                'name' => 'Image 3',
                'type' => 'image',
                'id' => 30000000000000
            ],
        ];
        $response = new ResourceResponse($list, 200);
        $response->addCacheableDependency(['cache' => ['max-age' => 0]]);
        return $response;
    }

    /**
     * Responds to POST requests.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   The entity object.
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function post(EntityInterface $entity)
    {

        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        return new ModifiedResourceResponse($entity, 200);
    }

    /**
     * Responds to PATCH requests.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   The entity object.
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function patch(EntityInterface $entity)
    {

        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        return new ModifiedResourceResponse($entity, 204);
    }

    /**
     * Responds to DELETE requests.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   The entity object.
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function delete(EntityInterface $entity)
    {

        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        return new ModifiedResourceResponse(null, 204);
    }

}
