<?php

namespace Drupal\ckeditor_table_tools_toolbar\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Table Tools Toolbar" plugin.
 *
 * @CKEditorPlugin(
 *   id = "tabletoolstoolbar",
 *   label = @Translation("Table Tools Toolbar Plugin")
 * )
 */
class TableToolsToolbarPlugin extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'tableinsert' => [
        'label' => $this->t('Insert a table'),
        'image' => 'libraries/tabletoolstoolbar/icons/tableinsert.png',
      ],
      'tabledelete' => [
        'label' => $this->t('Delete a table'),
        'image' => 'libraries/tabletoolstoolbar/icons/tabledelete.png',
      ],
      'tableproperties' => [
        'label' => $this->t('Table properties'),
        'image' => 'libraries/tabletoolstoolbar/icons/tableproperties.png',
      ],
      'tablerowinsertbefore' => [
        'label' => $this->t('Insert row before'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablerowinsertbefore.png',
      ],
      'tablerowinsertafter' => [
        'label' => $this->t('Insert row after'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablerowinsertafter.png',
      ],
      'tablerowdelete' => [
        'label' => $this->t('Delete row'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablerowdelete.png',
      ],
      'tablecolumninsertbefore' => [
        'label' => $this->t('Insert column before'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablecolumninsertbefore.png',
      ],
      'tablecolumninsertafter' => [
        'label' => $this->t('Insert column after'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablecolumninsertafter.png',
      ],
      'tablecolumndelete' => [
        'label' => $this->t('Delete column'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablecolumndelete.png',
      ],
      'tablecellinsertbefore' => [
        'label' => $this->t('Insert cell before'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablecellinsertbefore.png',
      ],
      'tablecellinsertafter' => [
        'label' => $this->t('Insert cell after'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablecellinsertafter.png',
      ],
      'tablecelldelete' => [
        'label' => $this->t('Delete cell'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablecelldelete.png',
      ],
      'tablecellproperties' => [
        'label' => $this->t('Cell properties'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablecellproperties.png',
      ],
      'tablecellsmerge' => [
        'label' => $this->t('Merge cells'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablecellsmerge.png',
      ],
      'tablecellmergeright' => [
        'label' => $this->t('Merge cells right'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablecellmergeright.png',
      ],
      'tablecellmergedown' => [
        'label' => $this->t('Merge cells down'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablecellmergedown.png',
      ],
      'tablecellsplithorizontal' => [
        'label' => $this->t('Split cells horizontally'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablecellsplithorizontal.png',
      ],
      'tablecellsplitvertical' => [
        'label' => $this->t('Split cells vertically'),
        'image' => 'libraries/tabletoolstoolbar/icons/tablecellsplitvertical.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/tabletoolstoolbar/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}
