<?php

namespace Drupal\sentiment_analysis\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'sentimentanalysis' field type.
 *
 * @FieldType (
 *   id = "sentimentanalysis",
 *   label = @Translation("Sentiment Analysis"),
 *   description = @Translation("This field check sentiment analysis input using API."),
 *   default_widget = "sentimentanalysis",
 *   default_formatter = "sentimentanalysis"
 * )
 */
class SentimentAnalysisItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setConstraints(array(
        'Length' => array('max' => 5000),
      ))
      ->addConstraint('SentimentAnalysisValidationConstraint', []);
    return $properties;
  }
}
