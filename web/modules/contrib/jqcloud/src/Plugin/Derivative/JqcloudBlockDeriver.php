<?php

namespace Drupal\jqcloud\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Derivative\DeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides dynamic definitions for jQCloud blocks based on Vocabularies.
 *
 * @see \Drupal\jqcloud\Plugin\Block\JqcloudBlock
 * @see plugin_api
 */
class JqcloudBlockDeriver extends DeriverBase implements DeriverInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    /** @var \Drupal\taxonomy\VocabularyInterface $vocabulary */
    foreach (Vocabulary::loadMultiple() as $vocabulary) {
      $this->derivatives[$vocabulary->id()] = $base_plugin_definition;
      $this->derivatives[$vocabulary->id()]['admin_label'] = $this->t(
        'jQCloud with "@voc" vocabulary',
        ['@voc' => $vocabulary->label()]
      );

      $this->derivatives[$vocabulary->id()]['vocabulary'] = $vocabulary;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
