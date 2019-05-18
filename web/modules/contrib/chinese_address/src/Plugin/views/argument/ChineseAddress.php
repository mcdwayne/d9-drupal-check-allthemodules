<?php

namespace Drupal\user\Plugin\views\argument;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\Plugin\views\argument\ManyToOne;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allow role ID(s) as argument.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("chinese_address")
 */
class ChineseAddress extends ManyToOne
{

    /**
   * Constructs a \Drupal\user\Plugin\views\argument\RolesRid object.
   *
   * @param array                                      $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string                                     $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed                                      $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) 
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition);

    }

    /**
   * {@inheritdoc}
   */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) 
    {
        return new static($configuration, $plugin_id, $plugin_definition);
    }

}
