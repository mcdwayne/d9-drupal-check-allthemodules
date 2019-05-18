<?php

namespace Drupal\pagedesigner_layout\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Service\Renderer;
use Drupal\pagedesigner\Service\StateChanger;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "pagedesigner_layout",
 *   label = @Translation("Pagedesigner layout"),
 *   uri_paths = {
 *     "canonical" = "/pagedesigner/layout/{id}",
 *     "create" = "/pagedesigner/layout",
 *   }
 * )
 */
class LayoutResource extends ResourceBase
{

    /**
     * A current user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    /**
     * The handler manager.
     *
     * @var \Drupal\pagedesigner\Service\StateChanger
     */
    protected $stateChanger;

    /**
     * The handler manager.
     *
     * @var \Drupal\pagedesigner\Service\Renderer
     */
    protected $renderer;

    /**
     * Constructs a new ElementResource object.
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
     * @param \Drupal\pagedesigner\Service\StateChanger $state_changer
     *   The state changer
     * @param \Drupal\pagedesigner\Service\Renderer $renderer
     *   The pagedesigner renderer
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger,
        AccountProxyInterface $current_user,
        StateChanger $state_changer,
        Renderer $renderer) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

        $this->currentUser = $current_user;
        $this->stateChanger = $state_changer;
        $this->renderer = $renderer;
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
            $container->get('current_user'),
            $container->get('pagedesigner.service.statechanger'),
            $container->get('pagedesigner.service.renderer')
        );
    }

    /**
     * Responds to POST requests.
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *   If the request is malformed.
     */
    public function get($id)
    {
        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        // if (!$this->currentUser->hasPermission('edit pagedesigner element entities')) {
        //     throw new AccessDeniedHttpException();
        // }

        $layout = Element::load($id);
        $entity = $layout->children->entity;
        $clone = $this->stateChanger->copy($entity)->getOutput();
        $renderer = $this->renderer->renderForEdit($clone);
        // $response = \Drupal::service('main_content_renderer.ajax')->renderResponse($renderArray, \Drupal::request(), \Drupal::routeMatch());
        // $response->addCommand(new BaseCommand('grapes_styles', $renderer->getStyles()));
        $response = new ModifiedResourceResponse([['command' => 'pd_markup', 'data' => $renderer->getMarkup()], ['command' => 'pd_styles', 'data' => $renderer->getStyles()]], 201);
        return $response;
    }

    /**
     * Responds to POST requests.
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *   If the request is malformed.
     */
    public function post($request)
    {
        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        // if (!$this->currentUser->hasPermission('edit pagedesigner element entities')) {
        //     throw new AccessDeniedHttpException();
        // }
        if (empty($request['original'])) {
            throw new BadRequestHttpException('The original key is mandatory for the post requests.');
        }

        $original = $request['original'];
        $entity = Element::load($original);
        $clone = $this->stateChanger->copy($entity, $entity->container->entity)->getOutput();
        $layout = Element::create(['type' => 'layout', 'name' => $request['name']]);
        $layout->field_content->value = $request['description'];
        $layout->field_include_content->value = $request['include_content'] == 1;
        $layout->children->target_id = $clone->id();
        $layout->save();
        return new ModifiedResourceResponse([$layout], 200);
    }
    /**
     * Responds to POST requests.
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *   If the request is malformed.
     */
    public function delete($id)
    {
        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        // if (!$this->currentUser->hasPermission('edit pagedesigner element entities')) {
        //     throw new AccessDeniedHttpException();
        // }

        $layout = Element::load($id);
        $entity = $layout->children->entity;
        $this->stateChanger->delete($entity);
        $layout->delete();
        return new ModifiedResourceResponse([true], 200);
    }

}
