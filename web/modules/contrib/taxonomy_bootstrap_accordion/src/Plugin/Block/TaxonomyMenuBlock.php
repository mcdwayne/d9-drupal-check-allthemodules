<?php

namespace Drupal\taxonomy_bootstrap_accordion\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'TaxonomyMenuBlock' block.
 *
 * @Block(
 *  id = "taxonomy_menu_block",
 *  admin_label = @Translation("All Vocabularies Accordion"),
 *  category = @Translation("Menus")
 * )
 */
class TaxonomyMenuBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'vocabs' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['vocabs'] = $form_state->getValue('vocabs');
  }

  /**
   * Implements blockForm().
   */
  public function blockForm($form, FormStateInterface $formState) {
    $vocabs = Vocabulary::loadMultiple();
    $weighted = [];

    foreach ($vocabs as $machine => $vocab) {
      // Place them in an array, in case of a weight collision.
      $weight = $vocab->get('weight');
      $weighted[$weight][] = $vocab->get('vid');
    }

    ksort($weighted, SORT_NUMERIC);

    // Flatten the weighted arrays.
    $flattened = [];
    foreach ($weighted as $weight => $items) {
      foreach ($items as $vid) {
        $flattened[] = $vid;
      }
    }

    $weighted = $flattened;
    $options = [];

    foreach ($weighted as $vid) {
      $vocab = Vocabulary::load($vid);
      $options[$vid] = $vocab->get('name');
    }
    $form['vocabs'] = [
      "#type" => "checkboxes",
      "#default_value" => $this->configuration['vocabs'],
      "#options" => $options,
      "#title" => $this->t("Vocabularies to Include"),
    ];
    return $form;
  }

  /**
   * Expects the machine name of the vocabulary item.
   */
  public function groupVocab($vocabulary) {
    // These 2 vars have nothing to do with config.
    $vocabs = taxonomy_vocabulary_get_names();
    $vocab = $vocabs[$vocabulary];

    // Begin new.
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary);

    $term_links = [];

    $current_path = \Drupal::service('path.current')->getPath();
    $alias_path = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);
    $open = '';
    $active = '';
    $expanded = 'false';
    foreach ($terms as $term) {
      $apply_active = FALSE;
      $tid = $term->tid;
      $name = $term->name;
      $url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $tid], ['absolute' => FALSE]);
      $path = $url->toString();
      if ($path == $alias_path) {
        $apply_active = TRUE;
        $open = 'in';
        $expanded = 'true';
        $active = 'active-trail active';
      }
      $classes = $apply_active ? [$active] : [];
      $class = implode(" ", $classes);
      $term_links[$tid] = [
        'css' => $class,
        'tid' => $tid,
        'text' => $name,
      ];
    }

    $group = [
      'heading' => $active,
      'abutton' => [
        'css' => $active,
        'expanded' => $expanded,
        'href' => $vocabulary,
        'text' => $vocab,
      ],
      'bodyclass' => $open,
      'links' => $term_links,
    ];
    return $group;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $vocabs = $this->configuration['vocabs'];
    $vocabslist = Vocabulary::loadMultiple();
    $build = [];
    if ($vocabs) {
      $groups = [];
      foreach ($vocabs as $vid => $value) {
        $vocabname = $vocabslist[$vid]->get('name');
        if ($value) {
          $groups["$vocabname"] = $this->groupVocab($vid);
        }
      }
    }
    else {
      $groups = [];
    }
    $build['#theme'] = 'accordion-group';
    $build['#taxonomy'] = $groups;
    $build['#cache']['contexts'] = [
      'url',
    ];

    return $build;
  }

}
