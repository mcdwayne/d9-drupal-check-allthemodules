<?php

namespace Drupal\pagetree\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Drupal\pagetree\Service\StateChange;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get unpublish a page.
 *
 * @RestResource(
 *   id = "pagetree_unpublish",
 *   label = @Translation("Unpublish"),
 *   uri_paths = {
 *     "canonical" = "/pagetree/unpublish"
 *   }
 * )
 */

class Unpublish extends ResourceBase
{

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    protected $stateChange = null;

    /**
     * Constructs a new UnpublishResource object.
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
        AccountProxyInterface $current_user,
        StateChange $state_change) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
        $this->stateChange = $state_change;
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
            $container->get('logger.factory')->get('pagetree'),
            $container->get('current_user'),
            $container->get('pagetree.state_change')
        );
    }

    /*
     * Unpublish a page.
     *
     * Returns a tree array of all unpublished node and revision ids.
     *
     * @return \Drupal\rest\ModifiedResourceResponse The unpublished node and revision ids.
     */
    public function patch($request)
    {
        $id = $request['id'];
        $language = $request['language'];
        $message = $request['message'];

        $entity = Node::load($id);
        if ($entity == null) {
            throw new UnprocessableEntityHttpException('Entity not found.');
        }

        if (!$entity->access('update', $this->currentUser)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this node.');
        }

        if ($entity->hasTranslation($language)) {
            $entity = $entity->getTranslation($language);
            $results = $this->stateChange->unpublish($entity, $message);
        } else {
            throw new UnprocessableEntityHttpException('The given language is not available on the entity.');
        }
        return new ModifiedResourceResponse($results);
    }

}
