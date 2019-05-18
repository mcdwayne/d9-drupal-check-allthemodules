<?php

/*
 * @file
 * Contains \Drupal\accordion_blocks\Plugin\migrate\source\Accordionblock
 */

namespace Drupal\accordion_blocks\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Accordion block source from database
 * 
 *  @MigrateSource(
 *   id = "accordionblock"
 * )
 */
class Accordionblock extends DrupalSqlBase {
  /**
   * The default theme name.
   *
   * @var string
   */
  protected $defaultTheme;
  
  protected $adminTheme;
  
  protected $systemDefaultTheme;
  
  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('accordion_blocks', 'ab')
                 ->fields('ab', ['id', 'title', 'content'])
                 ->orderBy('id', 'ASC');
    return $query;
  }
  
  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $this->defaultTheme = $this->variableGet('theme_default','bartik');
    $this->adminTheme = $this->variableGet('admin_theme', null);
    $this->systemDefaultTheme = 'bartrik';
    return parent::initializeIterator();
  }
  
  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $content = unserialize($row->getSourceProperty('content'));
    $blocks_content = [];
    if (isset($content)) {
      foreach ($content as $key => $block) {
        if (!empty($block)) {
          list($module, $delta) = explode('_delta_', $block);
          $block_id = $this->_getBlockId($module, $delta);
          //$block_id = $this->defaultTheme. "_".$module."_".$delta;
          $blocks_content[] = $block_id;
        }
      }
    }
    
    $row->setSourceProperty('content',  $blocks_content);
    return parent::prepareRow($row);
  }
  
  /**
   * @param type $module
   * @param type $delta
   */
  protected function _getBlockId($module, $delta) {
    
       $db = \Drupal::database(); 
       $data = $db->select('migrate_map_d7_block', 'mm')
                  ->fields('mm', ['sourceid1', 'sourceid2', 'sourceid3', 'destid1'])
                  ->condition('sourceid1', $module, '=')
                  ->condition('sourceid2', $delta, '=')
                  ->execute();
       foreach($data as $key => $value) {
         print_r($key);
         print_r($value);
         if(!empty($value->destid1)) {
           return $value->destid1;
         }
       }
       
    
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('id of the accordion widget'),
      'title' => $this->t('Title of the accordion widget'),
      'content' => $this->t('serailized content used in the accordion widget')
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'ab'
      ]
    ];
  }
  
}
