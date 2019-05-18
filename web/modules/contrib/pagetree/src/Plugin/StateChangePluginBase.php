<?php

namespace Drupal\pagetree\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\pagetree\Plugin\pagetree\StateChangePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public view renderer.
 *
 * Renders a node and its children in public (not editable) form.
 */
abstract class StateChangePluginBase extends PluginBase implements StateChangePluginInterface
{
    /**
     * Create a new instance.
     *
     * @param AccountInterface $user The user for which to process. If empty, the current user is used.
     * @param string $langCode The language to process in. If empty, the current language is used.
     */
    public function __construct($configuration,
        $plugin_id,
        $plugin_definition) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition
        );
    }

    public function publish(ContentEntityBase &$entity, $message)
    {

    }

    public function unpublish(ContentEntityBase &$entity, $message)
    {

    }

    public function generate(ContentEntityBase &$entity)
    {

    }

    public function delete(ContentEntityBase &$entity)
    {

    }
    public function copy(ContentEntityBase &$entity, ContentEntityBase &$clone = null)
    {

    }
}
