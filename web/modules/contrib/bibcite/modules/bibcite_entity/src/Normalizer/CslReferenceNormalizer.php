<?php

namespace Drupal\bibcite_entity\Normalizer;

use Drupal\bibcite_entity\Entity\ReferenceInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Normalizes/denormalizes reference entity to CSL format.
 */
class CslReferenceNormalizer extends ReferenceNormalizerBase {

  /**
   * List of date fields.
   *
   * @var array
   */
  protected $dateFields = [
    'bibcite_year',
    'bibcite_access_date',
  ];

  /**
   * {@inheritdoc}
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    // @todo Should throw \Symfony\Component\Serializer\Exception\LogicException
    throw new \Exception("Can't denormalize from csl format.");
  }

  /**
   * {@inheritdoc}
   */
  protected function extractFields(ReferenceInterface $reference, $format = NULL) {
    $attributes = [];

    $attributes['title'] = $this->extractScalar($reference->get('title'));
    foreach ($this->fieldsMapping[$this->format] as $csl_field => $entity_field) {
      if ($entity_field && $reference->hasField($entity_field) && ($field = $reference->get($entity_field)) && !$field->isEmpty()) {
        if (in_array($entity_field, $this->dateFields)) {
          $attributes[$csl_field] = $this->extractDate($field);
        }
        else {
          $attributes[$csl_field] = $this->extractScalar($field);
        }
      }
    }

    return $attributes;
  }

  /**
   * Extract authors values from field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field_item_list
   *   List of field items.
   *
   * @return array
   *   Authors in CSL format.
   */
  protected function extractAuthors(FieldItemListInterface $field_item_list) {
    $authors = [];

    foreach ($field_item_list as $field) {
      /** @var \Drupal\bibcite_entity\Entity\ContributorInterface $contributor */
      if ($contributor = $field->entity) {
        $authors[] = [
          'category' => $field->category,
          'role' => $field->role,
          'family' => $contributor->getLastName(),
          'given' => $contributor->getFirstName() . ' ' . $contributor->getMiddleName(),
          'suffix' => $contributor->getSuffix(),
          'literal' => $contributor->getName(),
          // @todo Implement other fields.
        ];
      }
    }

    return $authors;
  }

  /**
   * Extract date value to CSL format.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $date_field
   *   Date item list.
   *
   * @return array
   *   Date in CSL format.
   */
  protected function extractDate(FieldItemListInterface $date_field) {
    $value = $this->extractScalar($date_field);

    return [
      'date-parts' => [
        [$value],
      ],
      'literal' => $value,
    ];
  }

}
