<?php

namespace Drupal\multisite_solr_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'MultiSiteSolrSearchBlock' block.
 *
 * @Block(
 *  id = "multi_site_solr_search_block",
 *  admin_label = @Translation("Multi site solr search block"),
 * )
 */
class MultiSiteSolrSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\multisite_solr_search\Form\SearchForm');
    return $form;
  }

}
