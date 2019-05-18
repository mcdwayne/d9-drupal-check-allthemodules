<?php

namespace Drupal\bibcite_crossref\Normalizer;

use Drupal\bibcite_entity\Normalizer\ContributorNormalizer;

/**
 * Base normalizer class for bibcite formats.
 */
class CrossrefContributorNormalizer extends ContributorNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = ['Drupal\bibcite_entity\Entity\ContributorInterface'];

  protected $format = 'crossref';

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $value = (isset($data['name'][0]['value'])) ? $data['name'][0]['value'] : $data;

    if (is_array($value)) {
      $_data = [
        'last_name' => [['value' => $value['family']]],
      ];
      if (!empty($value['given'])) {
        $_data['first_name'] = [['value' => $value['given']]];
      }
      if (!empty($value['affiliation'])) {
        $affiliations = [];
        foreach ($value['affiliation'] as $affiliation) {
          $affiliations[] = $affiliation['name'];
        }
        $_data['suffix'] = [['value' => implode(', ', $affiliations)]];
      }
    }
    else {
      $_data = $data;
    }

    return parent::denormalize($_data, $class, $format, $context);
  }

}
