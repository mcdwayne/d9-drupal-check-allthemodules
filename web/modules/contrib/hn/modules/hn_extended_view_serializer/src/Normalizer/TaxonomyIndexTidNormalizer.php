<?php

namespace Drupal\hn_extended_view_serializer\Normalizer;

use Drupal\taxonomy\Entity\Term;
use Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer;

/**
 * Normalizes an ImageItem.
 */
class TaxonomyIndexTidNormalizer extends EntityReferenceFieldItemNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    /** @var \Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid $object */
    // Execute adminSummary to add term titles to options array.
    $object->adminSummary();
    $value_options = $object->getValueOptions();

    $options = $object->options;
    $options['value'] = $value_options;

    if (empty($object->value)) {
      // All is selected, show all terms.
      $query = \Drupal::entityQuery('taxonomy_term');
      $query->condition('vid', $options['vid']);
      $tids = $query->execute();
      $options['value'] = Term::loadMultiple($tids);
    }

    return $options;
  }

}
