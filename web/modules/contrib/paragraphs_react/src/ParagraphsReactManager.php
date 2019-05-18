<?php

namespace Drupal\paragraphs_react;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ParagraphsReactManager.
 */
class ParagraphsReactManager implements ParagraphsReactManagerInterface {
  private $paragraphsReactTableName = 'paragraphs_react_mapping';
  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;
  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;
  /**
   * Constructs a new ParagraphsReactManager object.
   */
  public function __construct(Connection $database, EntityTypeManager $entity_type_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  public function saveReactParagraphSetting($reactParagraphSetting) {
    try {
      if ($this->loadParagraphSetting($reactParagraphSetting['entity_id'], $reactParagraphSetting['paragraph_field_name'])) {
        //update settings
        $res = $this->database->update($this->paragraphsReactTableName)
          ->fields($reactParagraphSetting)
          ->execute();
        if($res){
          return TRUE;
        }
        return FALSE;
      }
      else {
        //insert new settings
        $res = $this->database->insert($this->paragraphsReactTableName)
          ->fields(['entity_id','entity_type','paragraph_field_name','page_title','page_url','jsx'],$reactParagraphSetting)
          ->execute();
        if($res){
          return TRUE;
        }
        return FALSE;
      }
    } catch (\Exception $e){
      \Drupal::logger('paragraphs_react')->warning(t('error in SaveReactParagraphSetting --> ').$e->getMessage());
      return FALSE;
    }
  }

  public function loadParagraphSetting($entity_id, $field_name) {
    $query = $this->database->select($this->paragraphsReactTableName,'prt')
      ->fields('prt',['entity_id','entity_type','paragraph_field_name','page_title','page_url','jsx'])
      ->condition('prt.entity_id',$entity_id)
      ->condition('prt.paragraph_field_name',$field_name);
    $result = $query->execute()->fetchAll();
    if(!empty($result)){
      return $result;
    }
    return FALSE;
  }

  public function loadAll(){
    $query = $this->database->select($this->paragraphsReactTableName,'prt')
      ->fields('prt',['entity_id','entity_type','paragraph_field_name','page_title','page_url']);
    $result = $query->execute()->fetchAll();
    if(!empty($result)){
      return $result;
    }
    return [];
  }

  public function manageFormSubmit($form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $values = $form_state->getValues();
    foreach($input as $name => $submitted){
      if(strpos($name,'field_')!==FALSE && isset($submitted[0]['paragraphs_react_url'])){
        $this->updateReactParagraphSettingFromInput($submitted[0],$form,$form_state,$name);
      }
      if(strpos($name,'field_')!==FALSE && $this->loadParagraphSetting($values['nid'],$name)){
        if(!isset($submitted[0]['paragraphs_react_is_enabled']) || !$submitted[0]['paragraphs_react_is_enabled']) {
          $this->deleteParagraphSetting($values['nid'],$name);
        }
      }
    }
  }

  private function deleteParagraphSetting($nid, $name) {
    try {
      $query = $this->database->delete('paragraphs_react_mapping')
        ->condition('entity_id', $nid)
        ->condition('paragraph_field_name', $name);
      $res = $query->execute();
    } catch (\Exception $e){
      $res = FALSE;
    }
    return $res;
  }

  private function updateReactParagraphSettingFromInput($submitted_value,$form,FormStateInterface $form_state,$field_name) {
    $buildInfo = $form_state->getBuildInfo();
    /** @var \Drupal\node\NodeForm $nodeForm */
    $nodeForm = $buildInfo['callback_object'];
    $entity = $nodeForm->getEntity();
    if(!is_null($entity)) {
      $entityId = $entity->id();
    } else {
      $entityId = FALSE;
    }
    if($entityId && $paragraphSetting = $this->loadParagraphSetting($entityId,$field_name)){
      //if already exists we check also if the Setting has been deleted
      $reactParagraphSetting = [
        'entity_id' => $entityId,
        'entity_type' => 'node',
        'paragraph_field_name' => $field_name,
        'page_title' => $submitted_value['paragraphs_react_page_title'],
        'page_url' => $submitted_value['paragraphs_react_url'],
        'jsx' => $submitted_value['paragraphs_react_jsx']
      ];
      $this->saveReactParagraphSetting($reactParagraphSetting);
    } else {
      //checks if the flag is setted up and creates the setting
      if($entityId){
        $reactParagraphSetting = [
          'entity_id' => $entityId,
          'entity_type' => 'node',
          'paragraph_field_name' => $field_name,
          'page_title' => $submitted_value['paragraphs_react_page_title'],
          'page_url' => $submitted_value['paragraphs_react_url'],
          'jsx' => $submitted_value['paragraphs_react_jsx']
        ];
        $this->saveReactParagraphSetting($reactParagraphSetting);
      }
    }
    \Drupal::service('router.builder')->rebuild();
  }

  public function loadReactLayoutMarkup($data){
    $id = $data['entity_id'];
    $entity_type = $data['entity_type'];
    $paragraph_field_name = $data['paragraph_field_name'];
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($id);
    $paragraphs_to_load = $entity->{$paragraph_field_name}->ReferencedEntities();
    $reactLayoutArray = [];
    $viewBuilder = $this->entityTypeManager->getViewBuilder('paragraph');
    foreach($paragraphs_to_load as $paragraph){
      $paragraphRenderArray = $viewBuilder->view($paragraph,'default');
      $paragraphMarkup = \Drupal::service('renderer')->renderRoot($paragraphRenderArray);
      $reactLayoutArray[$paragraph->id()] = [
        'markup' => $paragraphMarkup,
        'paragraph_state' => $paragraph->toArray()
      ];
    }
    if(!empty($reactLayoutArray)) {
      return [
        'appcontainerId' => 'paragraph-react-spa-container',
        'rendered_paragraphs' => $reactLayoutArray
      ];
    } else {
      return [];
    }
  }

  public function loadAllMarkup($return=TRUE,&$form=[]) {
    $all = $this->loadAll();
    $rows = [];
    $header = [
      'title',
      'url',
      'field name',
      'entity id',
      'entity type',
    ];
    foreach($all as $k => $v){
      $rows[] = [
        $v->page_title,
        $v->page_url,
        $v->paragraph_field_name,
        $v->entity_id,
        $v->entity_type
      ];
    }
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#caption' => t('Enabled paragraphs react pages')
    ];
    return;
  }
}
