<?php

namespace Drupal\cleverreach\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides block plugin definitions for cleverreach blocks.
 *
 * @see \Drupal\cleverreach\Plugin\Block\CleverreachBlock
 */
class CleverreachBlock extends DeriverBase implements ContainerDeriverInterface {
  
  protected $database;
  
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /*public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }     */
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('database')
    );
  }

  public function getDerivativeDefinitions($base_plugin_definition) {
    $blocks = array();
    $results = $this->database->query('SELECT * FROM {cleverreach_block_forms} bf WHERE bf.active = 1');

    foreach ($results as $result) {
      $blocks['cleverreach_block_' . $result->bid] = t('CleverReach: @grpname', array('@grpname' => cleverreach_get_group_name($result->listid)));
    }  
    
    foreach ($blocks as $block_id => $block_label) {
      $this->derivatives[$block_id] = $base_plugin_definition;
      $this->derivatives[$block_id]['admin_label'] = $block_label;
      $this->derivatives[$block_id]['cache'] = DRUPAL_NO_CACHE;
    }
    return $this->derivatives;
  }
  
}