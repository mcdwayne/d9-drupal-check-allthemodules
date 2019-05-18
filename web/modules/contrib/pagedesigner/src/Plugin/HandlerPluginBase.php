<?php

namespace Drupal\pagedesigner\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\pagedesigner\HandlerPluginInterface;
use Drupal\pagedesigner\Service\HandlerPluginManager;
use Drupal\ui_patterns\Definition\PatternDefinition;
use Drupal\ui_patterns\Definition\PatternDefinitionField;
use Drupal\ui_patterns\UiPatternsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public view renderer.
 *
 * Renders a node and its children in public (not editable) form.
 */
abstract class HandlerPluginBase extends PluginBase implements HandlerPluginInterface
{
    /**
     * The user to render the markup for.
     *
     * @var \Drupal\Core\Session\AccountInterface
     */
    protected $user = null;

    /**
     * The language manager
     *
     * @var \Drupal\Core\Language\LanguageManager
     */
    protected $languageManager = null;

    /**
     * The pattern manager
     *
     * @var \Drupal\ui_patterns\UiPatternsManager
     */
    protected $patternManager = null;

    /**
     * The handler manager
     *
     * @var \Drupal\pagedesigner\Service\HandlerPluginManager
     */
    protected $handlerManager = null;

    /**
     * The pattern definitions
     *
     * @var array
     */
    protected static $patternDefinitions = null;

    /**
     * Create a new instance.
     *
     * @param AccountInterface $user The user for which to process. If empty, the current user is used.
     * @param string $langCode The language to process in. If empty, the current language is used.
     */
    public function __construct($configuration,
        $plugin_id,
        $plugin_definition,
        AccountInterface $user,
        LanguageManager $language_manager,
        UiPatternsManager $pattern_manager,
        HandlerPluginManager $handler_manager) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->user = $user;
        $this->languageManager = $language_manager;
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
            $container->get('current_user'),
            $container->get('language_manager'),
            $container->get('plugin.manager.ui_patterns'),
            $container->get('plugin.manager.pagedesigner_handler')
        );
    }

    public function collectAttachments(&$attachments)
    {

    }

    /**
     * Allows to add dynamic patterns to the list.
     *
     * @param \Drupal\ui_patterns\Definition\PatternDefinition[] $patterns
     * @return void
     */
    public function collectPatterns(&$patterns)
    {

    }

    /**
     * Allows to alter the pattern data sent to the client.
     *
     * @param array $patterns
     * @return void
     */
    public function adaptPatterns(&$patterns)
    {

    }

    /**
     * Processes a field.
     *
     * @param PatternDefinitionField $field
     * @param array $fieldArray
     * @return void
     */
    public function prepare(PatternDefinitionField &$field, &$fieldArray)
    {

    }

    /**
     * Get the field value.
     *
     * @param \Drupal\pagedesigner\Entity\Element $entity
     * @param string $state
     * @return void
     */
    public function get(Element $entity)
    {
        return $entity->field_content->value;
    }

    /**
     * Get the field value.
     *
     * @param \Drupal\pagedesigner\Entity\Element $entity
     * @param string $state
     * @return void
     */
    public function patch(Element $entity, $data)
    {
        if (is_string($data)) {
            $entity->field_content->value = $data;
            $entity->saveEdit();
        }
        return $entity;
    }

    /**
     * Store the field value.
     *
     * @param array $data
     * @return \Drupal\pagedesigner\Entity\Element
     */
    public function generate($definition, $data)
    {
        $type = $definition['type'];
        $name = (!empty($definition['name'])) ? $definition['name'] : $definition['type'];
        $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
        $element = Element::create(['type' => $type, 'name' => $name, 'langcode' => $language]);
        return $element;
    }

    /**
     * Copy the given element.
     *
     * @param array $value
     * @param string $state
     * @return void
     */
    public function copy(Element $entity, ContentEntityBase $container)
    {
        $clone = $entity->createDuplicate();
        $children = [];
        foreach ($entity->children as $item) {
            $handler = $this->handlerManager->getInstance(['type' => $item->entity->bundle()])[0];
            $child = $handler->copy($item->entity, $container);
            $child->parent->entity = $clone;
            $child->container->entity = $container;
            $child->save();
            $children[] = $child->id();
        }
        $clone->children->setValue($children);
        $clone->setPublished(false);
        $clone->container->entity = $container;
        $clone->save();
        return $clone;
    }

    /**
     * Get the field value.
     *
     * @param \Drupal\pagedesigner\Entity\Element $entity
     * @param string $state
     * @return void
     */
    public function delete(Element $entity)
    {
        $entity->deleted = true;
        foreach ($entity->children as $item) {
            if ($item->entity != null) {
                $handler = $this->handlerManager->getInstance(['type' => $item->entity->bundle()])[0];
                $handler->delete($item->entity);
            }
        }
        $entity->saveEdit();
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->pluginDefinition['name'];
    }

    /**
     * {@inheritDoc}
     */
    public function renderForPublic(Element $entity)
    {
        $entity = $entity->loadNewestPublished();
        if ($entity != null) {
            return $this->render($entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function render(Element $entity)
    {
        return [
            '#type' => 'inline_template',
            '#template' => '{{markup}}',
            '#context' => ['markup' => ['#markup' => Markup::create($this->get($entity))]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function renderForEdit(Element $entity)
    {
        return $this->render($entity);
    }

    public function serialize(Element $entity)
    {
        return ['entity' => $entity];
    }

    public function publish(Element $entity)
    {
        foreach ($entity->children as $item) {
            $handler = $this->handlerManager->getInstance(['type' => $item->entity->bundle()])[0];
            $handler->publish($item->entity);
        }
        if (!$entity->isPublished()) {
            $entity->setPublished(true);
            $entity->save();
        }
    }

    public function unpublish(Element $entity)
    {
        foreach ($entity->children as $item) {
            $handler = $this->handlerManager->getInstance(['type' => $item->entity->bundle()])[0];
            $handler->unpublish($item->entity);
        }
        if ($entity->isPublished()) {
            $entity->setPublished(false);
            $entity->save();
        }
    }

    /**
     * Returns the definition of the specified pattern.
     *
     * @param string $id
     * @return PatternDefinition|null
     */
    protected function _getPatternDefinition($id)
    {

        if (self::$patternDefinitions == null) {
            self::$patternDefinitions = $this->patternManager->getDefinitions();
            $handlers = $this->handlerManager->getHandlers();
            foreach ($handlers as $handler) {
                $handler->collectPatterns(self::$patternDefinitions);
            }
        }
        foreach (self::$patternDefinitions as $key => $definition) {
            if ($key == $id) {
                return $definition;
            }
        }
        return null;
    }
}
