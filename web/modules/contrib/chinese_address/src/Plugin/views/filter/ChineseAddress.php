<?php

namespace Drupal\chinese_address\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\chinese_address\chineseAddressHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter handler for user roles.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("chinese_address")
 */
class ChineseAddress extends ManyToOne
{

    /**
   *
   */
    public function __construct(array $configuration, $plugin_id, $plugin_definition) 
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
   *
   */
    public function getValueOptions() 
    {
        $this->valueOptions = chineseAddressHelper::chinese_address_get_location(chineseAddressHelper::CHINESE_ADDRESS_ROOT_INDEX, true);
        if (isset($this->valueOptions)) {
            return $this->valueOptions;
        }

    }

    /**
   * Override empty and not empty operator labels to be clearer for user roles.
   */
    public function operators() 
    {
        $operators = parent::operators();
        return $operators;
    }


}
