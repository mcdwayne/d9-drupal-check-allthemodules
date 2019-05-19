<?php

namespace Drupal\simple_global_filter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\simple_global_filter\Form\SelectGlobalFilter;

/**
 * Provides a 'GlobalFilterBlock' block.
 *
 * @Block(
 *  id = "global_filter_block",
 *  admin_label = @Translation("Global filter block"),
 *  category = @Translation("Simple global filter"),
 *  deriver = "Drupal\simple_global_filter\Plugin\Derivative\GlobalFilterBlock"
 * )
 */
class GlobalFilterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $global_filter = \Drupal::entityTypeManager()->getStorage('global_filter')->load($this->getDerivativeId());

    $options_tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($global_filter->getVocabulary(), 0, NULL, TRUE);

    $options = [];
    foreach($options_tree as $term) {
      $options[$term->id()] = $term->label();
    }

    $form = \Drupal::formBuilder()->getForm(new SelectGlobalFilter($this->getDerivativeId()), $options);

    // Ideally we would have a cache context for each global filter. But it is not
    // possible to create cache contexts dynamically, but by editing a *.services.yml file.
    // So we do not allow to cache this block never:
    $form['#cache']['max-age'] = 0;

    return $form;
  }

}
