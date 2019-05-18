<?php

namespace Drupal\dbpedia_spotlight\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'entity reference taxonomy term RSS' formatter.
 *
 * @FieldFormatter(
 *   id = "dbpedia_spotlight_field_formatter",
 *   label = @Translation("Dbpedia spotlight"),
 *   description = @Translation("Display taxonomy term as DBpedia URI"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */

class DBpediaSpotlightFieldFormatter extends EntityReferenceFormatterBase {


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    // @todo to load in config variable
    $dbpedia_uri = "http://dbpedia.org/resource/";

    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $url = Url::fromUri($dbpedia_uri . $entity->label(), array('attributes' => array('target' => '_blank')));
      $elements[$delta] = ['#markup' => Link::fromTextAndUrl($entity->label(), $url)->toString()];
    }

    return $elements;
  }



}
