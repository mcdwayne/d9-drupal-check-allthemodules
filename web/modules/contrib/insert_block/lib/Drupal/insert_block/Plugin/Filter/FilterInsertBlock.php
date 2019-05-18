<?php

namespace Drupal\insert_block\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;

/**
 * Class FilterInsertBlock
 *
 * Inserts blocks into the content
 *
 * @package Drupal\insert_block\Plugin\Filter
 *
 * @Filter(
 *   id = "filter_insert_block",
 *   title = @Translation("Insert blocks"),
 *   description = @Translation("Inserts the contents of a block into a node using [block:module=delta] tags."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "check_roles" = TRUE
 *   }
 * )
 */
class FilterInsertBlock extends FilterBase {
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $form['check_roles'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Check roles permissions.'),
      '#default_value' => $this->settings['check_roles'],
      '#description' => $this->t('If user does not have permissions to view block it will be hidden.'),
    );
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode, $cache, $cache_id) {
    return _insert_block($text, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return t('<a name="filter-insert_block"></a>You may use [block:<em>block_entity_id</em>] tags to display the contents of block. To discover block entity id, visit admin/structure/block and hover over a block\'s configure link and look in your browser\'s status bar. The last "word" you see is the block ID.');
    }
    else {
      return t('You may use <a href="@insert_block_help">[block:<em>block_entity_id</em>] tags</a> to display the contents of block.',
        array("@insert_block_help" => url("filter/tips/filter_insert_block", array('fragment' => 'filter-insert_block'))));
    }
  }

}