<?php

namespace Drupal\pagedesigner\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\pagedesigner\Plugin\pagedesigner\AssetPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AssetPluginBase extends PluginBase implements AssetPluginInterface
{

    /**
     * Create a new instance.
     *
     * @param AccountInterface $user The user for which to process. If empty, the current user is used.
     * @param string $langCode The language to process in. If empty, the current language is used.
     */
    public function __construct($configuration, $plugin_id, $plugin_definition)
    {
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

    /**
     * Processes the node.
     *
     * @param ContentEntityBase $entity
     * @param array $children
     * @return void
     */
    public function get($filter = [])
    {

    }

}
