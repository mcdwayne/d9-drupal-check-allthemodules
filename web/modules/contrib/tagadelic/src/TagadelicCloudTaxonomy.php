<?php

namespace Drupal\tagadelic;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Link;
use Drupal\tagadelic\TagadelicCloudBase;
use Drupal\tagadelic\TagadelicTag;

/**
 * Class TagadelicCloud.
 *
 * @package Drupal\tagadelic
 */
class TagadelicCloudTaxonomy extends TagadelicCloudBase {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $config_factory;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->config_factory = $config_factory;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public function createTags(Array $options = array()) {
    $config = \Drupal::config('tagadelic.settings');
    $vocabularies = $config->get('tagadelic_vocabularies');
    $max_amount = 50;
  
    $query = db_select('taxonomy_index', 'i');
    $alias = $query->leftjoin('taxonomy_term_field_data', 't', '%alias.tid = i.tid');
    $query->addExpression('COUNT(i.nid)', 'count');
    $query->addField($alias, 'tid');
    $query->addField($alias, 'name');
    $query->addField($alias, 'description__value');
    $query->orderBy('count', 'DESC');

    // If no vocabularies have been configured use them all
    if (count($vocabularies)) {
      foreach($vocabularies as $key => $value) {
        if ($key !== $value) { 
          $query->condition('t.vid', $key, '<>');
        }
      }
    }

    $query->range(0, $max_amount)
      ->groupBy("t.tid")
      ->groupBy("t.name")
      ->groupBy("t.description__value");

    foreach ($query->execute() as $item) {
      $tag = new TagadelicTag($item->tid, $item->name, $item->count);
      $this->addTag($tag);
    }
  }
}
