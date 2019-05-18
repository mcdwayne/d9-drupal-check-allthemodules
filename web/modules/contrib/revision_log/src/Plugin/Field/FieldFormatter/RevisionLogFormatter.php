<?php

namespace Drupal\revision_log\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\DiffArray;

/**
 * Plugin implementation of the 'revision_log_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "revision_log_formatter",
 *   label = @Translation("Revision log formatter"),
 *   field_types = {
 *     "content_revision_log"
 *   }
 * )
 */
class RevisionLogFormatter extends FormatterBase {


  private $logViewMode = 'log';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'order'=>'asc',
      'limit'=> 0,
      'date_format' => 'medium',
      'header_template' => '@action by @user at @datetime',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['order'] = [
      '#type' => 'select',
      '#title'=> $this->t('Display Order'),
      '#options' => $this->getOptions(),
      '#default_value'=>$this->getSetting('order'),
    ];
    $form['date_format'] = [
      '#type' => 'select',
      '#title'=> $this->t('Date Format'),
      '#options' => $this->getDateFormats(),
      '#default_value'=>$this->getSetting('date_format'),
    ];
    $form['header_template'] = [
      '#type' => 'textfield',
      '#title'=> $this->t('Header template'),
      '#default_value'=>$this->getSetting('header_template'),
      '#description' => $this->t('Available tokens: @user => Revision author, @action => Created/Updated, @datetime => Revision created time'),
    ];
    $form['limit'] = [
      '#type' => 'number',
      '#title'=> $this->t('History limit'),
      '#description' => $this->t('Number of items to show, set 0 to show all'),
      '#min' => 0,
      '#max' => 1000,
      '#default_value'=>$this->getSetting('limit'),
    ];
    return $form + parent::settingsForm($form, $form_state);
  }
  
  protected function getDateFormats(){
    $dateFormats = \Drupal::entityTypeManager()
      ->getStorage('date_format')
      ->loadByProperties(['locked'=>false]);
    return array_map(function($format){
      return $format->label()."(".$format->getPattern().")";
    },$dateFormats);
  }
  
  protected function getOptions(){
    return ['asc'=>$this->t('Chronological'),'desc'=>$this->t('Reverse Chronological')];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary['order'] = "Order : " .$this->getOptions()[$this->getSetting('order')];
    $summary['limit'] = "Limit : " . $this->getSetting('limit');
    $summary['date_format'] = "Date Format : " . $this->getSetting('date_format');
    $summary['header_template'] = "Header : " . $this->getSetting('header_template');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $i = 0;
    $entity = $items->getEntity();
    $vids = \Drupal::entityManager()->getStorage($entity->getEntityTypeId())->revisionIds($entity);
    $versions = \Drupal::entityTypeManager()
      ->getStorage($entity->getEntityTypeId())
      ->loadMultipleRevisions($vids);

    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load( $entity->getEntityTypeId() . '.'. $entity->bundle() .'.' . $this->logViewMode);
    //Fallback to default view mode when "log" view mode is not detected.
    if(!$display){
      $display = \Drupal::entityTypeManager()
        ->getStorage('entity_view_display')
        ->load( $entity->getEntityTypeId() . '.'. $entity->bundle() .'.default');
      //Dettach the current component from display to avoid comparisions.
      $display->removeComponent('content_revision_log');
    }
    //@todo depending on https://www.drupal.org/project/drupal/issues/2923701
    // As title is also nodes hard property changes to titles should also be tracked fro now.
    //Can be removed once the above issue is fixed
    $display->setComponent('title',['label'=>'inline','weight'=>0]);
    
    if(count($versions) > 0){
      $dateSer = \Drupal::service('date.formatter');
      $header = $this->getSetting('header_template');
      $cnt = 1;
      $current = reset($versions);
      $next = null;
      $log = [];
      foreach($display->getComponents() as $field=>$c) {
        if(!$entity->hasField($field)){
          continue;
        }
        if(!$entity->getFieldDefinition($field)->isDisplayConfigurable('view') && $field != 'title'){
          continue;
        }
        if (!$current->get($field)->isEmpty()) {
          $log[$display->getComponent($field)['weight']] = $current->get($field)->view($display->getComponent($field));
        }
      }
      ksort($log);
      $date = $dateSer->format($current->getChangedTime(),$this->getSetting('date_format'));
      $elements[++$i] = ['#theme'=>'item_list','#items'=>$log, '#title'=>$this->t($header,['@user'=>$current->getRevisionUser()->getDisplayName(),'@datetime'=>$date,'@action'=>$this->t('Created')]),'#attributes'=>['class'=>['content-revision-log']]];
      while($cnt <= count($versions)){
        $cnt++;
        if($next){
          $current = $next;
        }
        $next = next($versions);
        if(!$next){
          break;
        }
        $log = [];
        foreach($display->getComponents() as $field=>$c) {
          if (empty($entity->hasField($field))) {
            continue;
          }
          if (DiffArray::diffAssocRecursive($next->get($field)->getValue(), $current->get($field)->getValue())) {
            $log[$display->getComponent($field)['weight']] = $next->get($field)->view($display->getComponent($field));
            //@todo We can even show the old and comparision with new
            // $log[$i][$field]['old'] = $nx->get($field)->view();
          }
        }
        $date = $dateSer->format($next->getChangedTime(),$this->getSetting('date_format'));
        if(!empty($log)){
          ksort($log);
          $elements[++$i] = ['#theme'=>'item_list','#items'=>$log, '#title'=>$this->t($header,['@action'=>$this->t('Updated'),'@user'=>$next->getRevisionUser()->getDisplayName(),'@datetime'=>$date]),'#attributes'=>['class'=>['content-revision-log']]];
        }
      }
    }
    $order = $this->getSetting('order');
    if($order == 'desc'){
      krsort($elements);
    }
    $limit = $this->getSetting('limit');
    if($limit){
      return array_slice($elements, 0, $limit);
    }
    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
