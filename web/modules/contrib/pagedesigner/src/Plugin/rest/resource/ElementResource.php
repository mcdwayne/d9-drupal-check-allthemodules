<?php

namespace Drupal\pagedesigner\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Service\HandlerPluginManager;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\ui_patterns\UiPatternsManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "pagedesigner_element",
 *   label = @Translation("Pagedesigner element resource"),
 *   uri_paths = {
 *     "canonical" = "/pagedesigner/element/{id}",
 *     "create" = "/pagedesigner/element",
 *   }
 * )
 */
class ElementResource extends ResourceBase
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
     * @var \Drupal\pagedesigner\Service\HandlerPluginManager
     */
    protected $handlerManager;

    /**
     * The handler manager.
     *
     * @var \Drupal\ui_patterns\UiPatternsManager
     */
    protected $patternManager;

    /**
     * The language manager
     *
     * @var \Drupal\Core\Language\LanguageManager
     */
    protected $languageManager = null;
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
     * @param \Drupal\pagedesigner\Service\HandlerPluginManager $handler_manager
     *   The processor plugin manager
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger,
        AccountProxyInterface $current_user,
        HandlerPluginManager $handler_manager,
        UiPatternsManager $pattern_manager,
        LanguageManager $language_manager) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

        $this->currentUser = $current_user;
        $this->handlerManager = $handler_manager;
        $this->patternManager = $pattern_manager;
        $this->languageManager = $language_manager;
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
            $container->get('plugin.manager.pagedesigner_handler'),
            $container->get('plugin.manager.ui_patterns'),
            $container->get('language_manager')
        );
    }

    /**
     * Undocumented function
     *
     * @param [type] $id
     * @return void
     */
    public function get($id = null)
    {

        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        // if (!$this->currentUser->hasPermission('access content')) {
        //     throw new AccessDeniedHttpException();
        // }

        $build = [];
        if (!is_numeric($id)) {
            throw new BadRequestHttpException('The entity key must be numeric.');
        }
        $entity = Element::load($id);
        if ($entity == null) {
            throw new NotFoundHttpException('The entity does not exist.');
        }
        $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
        if (!$entity->hasTranslation($language)) {
            throw new UnprocessableEntityHttpException('The entity does not exist in the given language.');
        }
        $entity = $entity->getTranslation($language);
        if ($entity != null) {
            $handlers = $this->handlerManager->getInstance(['type' => $entity->bundle()]);
            foreach ($handlers as $handler) {
                $build = array_merge($build, $handler->serialize($entity));
            }
        }

        $response = new ModifiedResourceResponse($build, 200);
        // $response->addCacheableDependency(['cache' => ['max-age' => 0]]);
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
        if (empty($request['pattern'])) {
            throw new BadRequestHttpException('The pattern key is mandatory for the post requests.');
        }
        if (empty($request['container'])) {
            throw new BadRequestHttpException('The container key is mandatory for the post requests.');
        }
        if (empty($request['parent']) && $request['parent'] != null) {
            throw new BadRequestHttpException('The parent key is mandatory for the post requests.');
        }
        if (!$this->currentUser->hasPermission('edit pagedesigner element entities')) {
            throw new AccessDeniedHttpException('You are not allowed to create pagedesigner content');
        }

        $type = null;
        $patternDefinition = null;
        $patternDefinitions = $this->patternManager->getDefinitions();
        $handlers = $this->handlerManager->getHandlers();
        foreach ($handlers as $handler) {
            $handler->collectPatterns($patternDefinitions);
        }
        foreach ($patternDefinitions as $plugin_id => $definition) {
            if ($plugin_id == $request['pattern']) {
                $patternDefinition = $definition;
            }
        }
        if ($patternDefinition === null) {
            throw new UnprocessableEntityHttpException('The given pattern is not registered.');
        }
        $type = $patternDefinition->getAdditional()['type'];
        if (empty($type)) {
            throw new UnprocessableEntityHttpException('No type given in pattern definition.');
        }
        $isDesignerPattern = !empty($patternDefinition->getAdditional()['designer']) || $patternDefinition->getAdditional()['designer'] == 1;
        $designerPermission = $this->currentUser->hasPermission('access pagedesigner designer patterns');
        // Check if the pattern is for designer only
        if ($isDesignerPattern && !$designerPermission) {
            throw new AccessDeniedHttpException('You are not allowed to create designer elements.');
        }
        $build = ['type' => $type];
        $handlers = $this->handlerManager->getInstance(['type' => $type]);
        foreach ($handlers as $handler) {
            $build = array_merge($build, $handler->generate($patternDefinition, $request));
        }
        return new ModifiedResourceResponse($build, 200);
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
    public function patch($id, $request)
    {
        $build = [];
        if (!is_numeric($id)) {
            throw new BadRequestHttpException('The entity key must be numeric.');
        }

        if (!$this->currentUser->hasPermission('edit pagedesigner element entities')) {
            throw new AccessDeniedHttpException('You are not allowed to edit pagedesigner content');
        }

        $entity = Element::load($id);
        if ($entity == null) {
            throw new NotFoundHttpException('The entity does not exist.');
        }
        $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
        if (!$entity->hasTranslation($language)) {
            throw new UnprocessableEntityHttpException('The entity does not exist in the given language.');
        }
        $entity = $entity->getTranslation($language);
        $handlers = $this->handlerManager->getInstance(['type' => $entity->bundle()]);
        foreach ($handlers as $handler) {
            $build = array_merge($build, $handler->patch($entity, $request));
        }

        return new ModifiedResourceResponse($build, 200);
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
    public function delete($id)
    {

        $build = [];
        if (!is_numeric($id)) {
            throw new BadRequestHttpException('The entity key must be numeric.');
        }
        if (!$this->currentUser->hasPermission('edit pagedesigner element entities')) {
            throw new AccessDeniedHttpException('You are not allowed to delete pagedesigner content');
        }

        $entity = Element::load($id);
        if ($entity == null) {
            throw new NotFoundHttpException('The entity does not exist.');
        }
        $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
        if (!$entity->hasTranslation($language)) {
            throw new UnprocessableEntityHttpException('The entity does not exist in the given language.');
        }
        $entity = $entity->getTranslation($language);
        $handlers = $this->handlerManager->getInstance(['type' => $entity->bundle()]);
        foreach ($handlers as $handler) {
            $build = array_merge($build, $handler->delete($entity));
        }
        return new ModifiedResourceResponse($build, 200);

    }

}
