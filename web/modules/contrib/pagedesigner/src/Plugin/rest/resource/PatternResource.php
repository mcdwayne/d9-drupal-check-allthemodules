<?php

namespace Drupal\pagedesigner\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\pagedesigner\Service\HandlerPluginManager;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\ui_patterns\Definition\PatternDefinition;
use Drupal\ui_patterns\UiPatternsManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "pagedesigner_pattern",
 *   label = @Translation("Pagedesigner patterns"),
 *   uri_paths = {
 *     "canonical" = "/pagedesigner/pattern"
 *   }
 * )
 */
class PatternResource extends ResourceBase
{

    /**
     * A current user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    /**
     * The pattern manager.
     *
     * @var \Drupal\ui_patterns\UiPatternsManager
     */
    protected $patternManager;

    /**
     * The processor manager.
     *
     * @var Drupal\pagedesigner\Service\HandlerPluginManager
     */
    protected $handlerManager;

    /**
     * Constructs a new PatternResource object.
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
     * @param \Drupal\ui_patterns\UiPatternsManager $pattern_manager
     *  The pattern manager
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger,
        AccountProxyInterface $current_user,
        UiPatternsManager $pattern_manager,
        HandlerPluginManager $handler_manager) {

        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

        $this->currentUser = $current_user;
        $this->patternManager = $pattern_manager;
        $this->handlerManager = $handler_manager;
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
            $container->get('plugin.manager.ui_patterns'),
            $container->get('plugin.manager.pagedesigner_handler')
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
    public function get(EntityInterface $entity = null)
    {
        $patterns = [];
        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if ($this->currentUser->hasPermission('edit pagedesigner element entities')) {
            if ($entity == null) {
                $type = 'element';
                $designerPermission = $this->currentUser->hasPermission('access pagedesigner designer patterns');
                $stylingPermission = $this->currentUser->hasPermission('edit pagedesigner styles');

                $patternDefinitions = $this->patternManager->getDefinitions();

                $handlers = $this->handlerManager->getHandlers();
                foreach ($handlers as $handler) {
                    $handler->collectPatterns($patternDefinitions);
                }
                foreach ($patternDefinitions as $id => $definition) {
                    $additional = $definition->getAdditional();

                    // Check if the pattern is meant for the page designer
                    if (empty($additional['pagedesigner']) || $additional['pagedesigner'] == 0) {
                        continue;
                    }

                    // Check if the pattern is for designer only
                    if (!empty($additional['designer']) && !$designerPermission) {
                        continue;
                    }

                    $category = (!empty($additional['category'])) ? $additional['category'] : 'element';
                    $icon = (!empty($additional['icon'])) ? $additional['icon'] : 'fas fa-square';
                    $type = (!empty($additional['type'])) ? $additional['type'] : 'standard';

                    $styles = [];
                    if ($stylingPermission) {
                        $styles = (!empty($additional['styles'])) ? $additional['styles'] : $styles;
                    }

                    $weight = (!empty($additional['weight'])) ? $additional['weight'] : 1000;
                    $markup = (!empty($additional['markup'])) ? $additional['markup'] : '';

                    $label = $definition->getLabel();
                    $description = $definition->getDescription();
                    $filename = $definition->getBasePath() . '/' . $definition->getTemplate() . '.html.twig';
                    if (empty($markup) && \file_exists($filename)) {
                        $markup = \file_get_contents($filename);
                    }
                    if (empty($markup)) {
                        continue;
                    }
                    $definitionArray = [
                        'label' => $label,
                        'description' => $description,
                        'fields' => $this->getFields($definition),
                        'icon' => $icon,
                        'category' => $category,
                        'markup' => $markup,
                        'type' => $type,
                        'additional' => $additional,
                        'styles' => $styles,
                        'weight' => $weight,
                    ];
                    $patterns[$id] = $definitionArray;
                }
            }
            foreach ($handlers as $handler) {
                $handler->adaptPatterns($patterns);
            }

            uasort(
                $patterns,
                function (array $a, array $b) {
                    return $a['weight'] - $b['weight'];
                }
            );
        }
        $response = new ResourceResponse($patterns, 200);
        $response->addCacheableDependency(['cache' => ['max-age' => 0]]);
        return $response;
    }

    protected function getFields(PatternDefinition $definition)
    {
        $fields = [];
        foreach ($definition->getFields() as $id => $field) {
            $label = $field->getLabel();
            $description = $field->getDescription();
            $description = (empty($description)) ? $label : $description;

            $fields[$id] = [
                'description' => $description,
                'label' => $label,
                'name' => $field->getName(),
                'preview' => $field->getPreview(),
                'type' => $field->getType(),
            ];
            $handlers = $this->handlerManager->getInstance(['type' => $field->getType()]);
            foreach ($handlers as $handler) {
                $handler->prepare($field, $fields[$id]);
            }
        }
        return $fields;
    }
}
