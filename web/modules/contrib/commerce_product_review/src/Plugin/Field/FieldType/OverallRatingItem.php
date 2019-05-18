<?php

namespace Drupal\commerce_product_review\Plugin\Field\FieldType;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product_review\OverallProductRating;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Implements the 'commerce_product_review_overall_rating' field type plugin.
 *
 * @FieldType(
 *   id = "commerce_product_review_overall_rating",
 *   label = @Translation("Rating summary"),
 *   category = @Translation("Commerce"),
 *   default_widget = "commerce_product_review_overall_rating_default",
 *   default_formatter = "commerce_product_review_overall_rating_default",
 * )
 */
class OverallRatingItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['score'] = DataDefinition::create('string')
      ->setLabel(t('Overall rating score'))
      ->setRequired(FALSE);

    $properties['count'] = DataDefinition::create('integer')
      ->setLabel(t('Rating count'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'score' => [
          'description' => 'Overall rating score.',
          'type' => 'numeric',
          'precision' => 4,
          'scale' => 3,
        ],
        'count' => [
          'description' => 'Rating count.',
          'type' => 'int',
          'unsigned' => TRUE,
          'size' => 'normal',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();
    $constraints[] = $manager->create('ComplexData', [
      'score' => [
        'Regex' => [
          'pattern' => '/^[+-]?((\d+(\.\d*)?)|(\.\d+))$/i',
        ],
      ],
    ]);
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->score === NULL || $this->score === '' || empty($this->count);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if ($values instanceof OverallProductRating) {
      $overall_rating = $values;
      $values = [
        'score' => $overall_rating->getScore(),
        'count' => $overall_rating->getCount(),
      ];
    }
    parent::setValue($values, $notify);
  }

  /**
   * Gets the OverallProductRating value object for the current field item.
   *
   * @return \Drupal\commerce_product_review\OverallProductRating
   *   The OverallProductRating value object.
   */
  public function toOverallProductRating() {
    $parent = $this->getEntity();
    $product = $parent && $parent instanceof ProductInterface ? $parent : NULL;
    return new OverallProductRating($this->score, $this->count, $product);
  }

}
