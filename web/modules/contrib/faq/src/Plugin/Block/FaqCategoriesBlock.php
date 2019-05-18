<?php

namespace Drupal\faq\Plugin\Block;

use Drupal\Core\Url;
use Drupal\faq\FaqHelper;
use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Provides a simple block.
 *
 * @Block(
 *   id = "faq_categories",
 *   admin_label = @Translation("FAQ Categories")
 * )
 */
class FaqCategoriesBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    static $vocabularies, $terms;
    $items = [];

    $faq_settings = \Drupal::configFactory()->get('faq.settings');
    if (!$faq_settings->get('use_categories')) {
      return [];
    }
    $moduleHandler = \Drupal::moduleHandler();

    if ($moduleHandler->moduleExists('taxonomy')) {
      if (!isset($terms)) {
        $terms = [];
        $vocabularies = Vocabulary::loadMultiple();
        $vocab_omit = array_flip($faq_settings->get('omit_vocabulary'));
        $vocabularies = array_diff_key($vocabularies, $vocab_omit);
        foreach ($vocabularies as $vocab) {
          foreach (\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocab->id()) as $term) {
            if (FaqHelper::taxonomyTermCountNodes($term->tid)) {
              $terms[$term->name] = $term->tid;
            }
          }
        }
      }
      if (count($terms) > 0) {
        foreach ($terms as $name => $tid) {
          $items[] = \Drupal::linkGenerator()->generate($name, URL::fromUserInput('/faq-page/' . $tid));
        }
      }
    }
    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#list_type' => $faq_settings->get('category_listing'),
    ];
  }

}
