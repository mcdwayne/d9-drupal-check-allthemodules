<?php

namespace Drupal\pagedesigner;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\pagedesigner\HandlerPluginInterface;
use Drupal\pagedesigner\Service\HandlerPluginManager;

abstract class PagedesignerService
{
    /**
     * The output of the last operation.
     *
     * @var mixed
     */
    protected $_output = null;

    /**
     * The language manager
     *
     * @var \Drupal\Core\Language\LanguageManager
     */
    protected $languageManager = null;

    /**
     * The handler manager
     *
     * @var \Drupal\pagedesigner\Service\HandlerPluginManager
     */
    protected $handlerManager = null;

    /**
     * Create a new instance.
     *
     * @param AccountInterface $user The user for which to process. If empty, the current user is used.
     * @param string $langCode The language to process in. If empty, the current language is used.
     */
    public function __construct(LanguageManager $language_manager, HandlerPluginManager $handler_manager)
    {
        $this->languageManager = $language_manager;
        $this->handlerManager = $handler_manager;
    }

    // /**
    //  * {@inheritdoc}
    //  */
    // public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    // {
    //     return new static(
    //         $container->get('language_manager'),
    //         $container->get('plugin.manager.pagedesigner_handler')
    //     );
    // }

    /**
     * Returns the output of the last operation.
     *
     * @return mixed
     */
    public function getOutput()
    {
        return $this->_output;
    }

    /**
     * Loads the container for the entity.
     *
     * @param ContentEntityBase $entity The entity being rendered.
     * @return Element The container.
     */
    protected function _getContainer(ContentEntityBase $entity)
    {
        $container = $entity;
        if ($entity->getEntityTypeId() == 'node') {

            $ids = \Drupal::entityQuery('pagedesigner_element')->condition('type', 'container', 'LIKE')->condition('container.target_id', $entity->id())->notExists('parent.target_id')->execute();
            if (count($ids) == 0) {
                $container = $this->_createContainer($entity);
            } else {
                $container = Element::load(reset($ids));
            }
        }
        $language = $entity->language()->getId();
        if (!$container->hasTranslation($language)) {
            $container->addTranslation($language);
            $container->container->target_id = $entity->id();
            $container->save();
        }
        $container = $container->getTranslation($language);
        return $container;
    }

    /**
     * Creates a container for the entity.
     *
     * @param ContentEntityBase $entity The entity being rendered.
     * @return Element The created container.
     */
    protected function _createContainer(ContentEntityBase $entity)
    {
        $container = Element::create(['type' => 'container', 'name' => 'container']);
        $container->container->target_id = $entity->id();
        $container->langcode->value = $entity->langcode->value;
        $container->save();
        return $container;
    }

    /**
     * Get the handler.
     *
     * @param ContentEntityBase $entity
     * @return Drupal\pagedesigner\Plugin\pagedesigner\HandlerPluginInterface
     */
    protected function getHandler(ContentEntityBase $entity)
    {
        return $this->handlerManager->createInstance($entity->bundle());
    }
}
