<?php

/**
 * @file
 * Contains \Drupal\redirect_deleted_entities\Plugin\redirect_deleted_entities\RedirectType\TaxonomyTermRedirectType.
 */

namespace Drupal\redirect_deleted_entities\Plugin\redirect_deleted_entities\RedirectType;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * A redirect type plugin for taxonomy term entities.
 *
 * @RedirectType(
 *   id = "taxonomy_term",
 *   label = @Translation("Taxonomy term paths"),
 *   types = {"term"},
 *   provider = "taxonomy",
 * )
 */
class TaxonomyTermRedirectType extends EntityRedirectTypeBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getPatternDescription() {
    return $this->t('Default redirect pattern (applies to all vocabularies with blank patterns below)');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('default' => array('/[term:vocabulary]')) + parent::defaultConfiguration();
  }

}
