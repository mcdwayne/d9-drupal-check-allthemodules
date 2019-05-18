<?php

namespace Drupal\entity_slug\Plugin\Slugifier;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_slug\Annotation\Slugifier;

/**
 * @Slugifier(
 *   id = "short_circuit",
 *   name = @Translation("Short circuit"),
 *   weight = 20,
 * )
 */
class ShortCircuitSlugifier extends SlugifierBase {

  /**
   * {@inheritdoc}
   */
  public function slugify($input, FieldableEntityInterface $entity) {
    $output = $input;

    while (preg_match('/{(({[^}]*})+)}/', $output, $matches)) {
      list($match, $values) = $matches;

      $replacement = '';

      $values = trim($values, '{}');
      $valuesArray = preg_split('/\s*}\s*{\s*/', $values);

      if ($valuesArray) {
        foreach ($valuesArray as $value) {
          if (!empty($value)) {
            $replacement = $value;
            break;
          }
        }
      }

      $output = str_replace($match, $replacement, $input);
    }

    return $output;
  }

  public function information() {
    $information = [];

    $information[] = $this->t('The Short Circuit slugifier will take a row of {{item}{item}{item}} and print the first one that has a value.');
    $information[] = $this->t('Example: {{[node:field_headline]}{[node:title]} will use the headline token if available, falling back to the title.');

    return array_merge($information, parent::information());
  }
}
