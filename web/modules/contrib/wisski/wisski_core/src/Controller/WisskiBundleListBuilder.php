<?php

namespace Drupal\wisski_core\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\wisski_core\Entity\WisskiEntity;
use Drupal\wisski_core\Entity\WisskiBundle;

class WisskiBundleListBuilder extends ConfigEntityListBuilder implements EntityHandlerInterface {

  const NAVIGATE = 1;
  const CONFIG = 2;
  const CREATE = 3;
  
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

//    $header['id'] = t('ID');
    $header['label'] = $this->t('Name');
    if ($this->type === self::CONFIG) {
      $header['parent'] = $this->t('Parent');
      $header += parent::buildHeader();
    }
    return $header;
  }
  
  /**
   * {@inheritdoc}
   */
   
  public function load() {
  
    if($this->type == self::NAVIGATE || $this->type == self::CREATE) {
      $entities = parent::load();

      $outentities = array();
            
      $sortlist = array();
      
      $menu_tree_data = array();
      
      foreach($entities as $key => $entity) {
        $menus = $entity->getWissKIMenus();

        if($this->type == self::NAVIGATE) {
          $p = new \Drupal\Core\Menu\MenuTreeParameters();
          $p->addCondition('title', $entity->label(), '=');

          $menu_tree_data = \Drupal::service('menu.link_tree')->load('navigate', $p);
          $menu_items = array();
          
          foreach($menu_tree_data as $data) {
            $menu_items[] = $data->link;
          }
#          $menu_items = \Drupal::entityTypeManager()->getStorage('menu_tree')->loadByProperties(['menu_name' => 'navigate', 'title' => $entity->label() ])
        } else
          $menu_items = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['menu_name' => 'create', 'title' => $entity->label(), 'link__uri' => 'route:' . $menus['create'] . ';' . 'wisski_bundle=' . $entity->id() ]);
        
        $menu_items = array_filter($menu_items, function($m) { return $m->isEnabled(); });
        #dpm($menu_items);
        // there should not be more than one.
        $menu_items = current($menu_items);
        
        if(!empty($menu_items))
          $sortlist[$key] = $menu_items->getWeight();
      }
      
      asort($sortlist);
      
      foreach($sortlist as $key => $value) {
        $outentities[$key] = $entities[$key];
      }
      
      return $outentities;
      
    } else {
      $entities = parent::load();
    }
    
    return $entities;
  
  }
  
  /**
   * {@inheritdoc}
   */
  
  public function getEntityIds() {

    // only get topids
    
    $topIds = \Drupal\wisski_core\WisskiHelper::getTopBundleIds();
    
    $query = $this->getStorage()->getQuery()->sort($this->entityType->getKey('id'));

    if($this->type == self::NAVIGATE || $this->type == self::CREATE) {
      // add a condition for the topids    
      $query->condition('id', array_values($topIds), 'IN');
    }
    
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    
    #dpm($query->execute());
    
    return $query->execute();
    
  }
  

  /**
   * {@inheritdoc}
   */
  public function buildRow(\Drupal\Core\Entity\EntityInterface $entity) {
    
    // old: in case of navigate and create - exclude all non-top-groups
    // we don't need to do this here anymore, because we do this in
    // the entity query above.
    /*
    if($this->type == self::NAVIGATE || $this->type == self::CREATE) {
      // get all top groups from pbs
      $parents = \Drupal\wisski_core\WisskiHelper::getTopBundleIds();    

      // only show top groups
      if(!in_array($entity->id, $parents)) {
        drupal_set_message('Bundle '.$entity->id().' is not shown here since it is not a top bundle');
        return false;
        return array();
      }
    }
    */
    
    $menus = WisskiBundle::getWissKIMenus();
    
    switch ($this->type) {
      case self::NAVIGATE: return $this->buildMenuNavigateRow($entity, $menus['navigate']); //$this->buildNavigateRow($entity, $menus['navigate']);
      case self::CREATE: return $this->buildCreateRow($entity, $menus['create']);
      case self::CONFIG: return $this->buildConfigRow($entity);
    }
    drupal_set_message($this->t('Invalid list type'),'error');
    return array();
  }
  
  private function buildMenuNavigateRow($entity, $menu_param = "entity.wisski_bundle.entity_list") {

    $p = new \Drupal\Core\Menu\MenuTreeParameters();
    $p->addCondition('title', $entity->label(), '=');
#    $p->addCondition('id', "%" . $entity->id() . "%", 'LIKE');

    $menu_tree_data = \Drupal::service('menu.link_tree')->load('navigate', $p);
 
# dpm($menu_tree_data, "yay!");
 
    foreach($menu_tree_data as $key => $one_ob) {
      #dpm($one_ob->link->getRouteParameters(), "yasa");
      
      $link = $one_ob->link;
      
      if(empty($link))
        return;
      
      $rpm = $link->getRouteParameters();
      
      // either for bundles
      // there are route parameters and there is a route parameter wisski_bundle
      if($rpm && $rpm['wisski_bundle'] && $rpm['wisski_bundle'] != $entity->id()) {
        unset($menu_tree_data[$key]);
        continue;
      }

      // or the above did not hold
      // so we are in views-mode
      if($link && !$rpm && strpos($link->getPluginId(), $entity->id()) === FALSE) {
        unset($menu_tree_data[$key]);  
        continue;
      }    
    }       

    if(empty($menu_tree_data))
      return;

    $menu_tree_data = current($menu_tree_data);
    
    $entities = $menu_tree_data->link;

#    dpm($menu_tree_data, "yay!");

    if(empty($entities))
      return;
        
    $row['label'] = array(
      'data' => array(
        '#type' => 'link',
        '#url' => $entities->getUrlObject(),
        '#title' => $entities->getTitle(),
#        '#url' => Url::fromRoute('entity.wisski_bundle.entity_list')
#          ->setRouteParameters(array('wisski_bundle' => $entity->id())),
#        '#title' => $entity->label(),
      ),
    );

    return $row;
  }
  
  private function buildNavigateRow($entity, $menu_param = "entity.wisski_bundle.entity_list") {

    // see if there is a row in the navigation menu.
    $entities = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['menu_name' => 'navigate', 'title' => $entity->label() ]);
    
    // there should not be more than one.
    $entities = current($entities);

    if(empty($entities) || !$entities->isEnabled())
      return array();
    
#    dpm($entities);

#    dpm($entity);    
    $row['label'] = array(
      'data' => array(
        '#type' => 'link',
        '#url' => $entities->getUrlObject(),
        '#title' => $entities->getTitle(),
#        '#url' => Url::fromRoute('entity.wisski_bundle.entity_list')
#          ->setRouteParameters(array('wisski_bundle' => $entity->id())),
#        '#title' => $entity->label(),
      ),
    );
    return $row;
  }
  
  private function buildConfigRow($entity) {
    //    $row['id'] = 
    $id = $entity->get('id');
    //@TODO use EntityFieldQuery or whatsolike
    //$ents = WisskiEntity::load(array('bundle'=>$id));
    $parents = $entity->getParentBundleIds();
    $row['label'] = array(
      'data' => $this->getLabel($entity),
      'class' => array('menu-label'),
    );

    // this is deprecated
    //if (list($key,$value) = each($parents)) {
    
    if(!empty($parents)) {
      foreach($parents as $key => $value) { 
        $row['parent'] = array(
          'data' => array(
            '#type' => 'link',
            '#url' => new Url('entity.entity_view_display.wisski_individual.default',array('wisski_bundle' => $key)),
            '#title' => $value,
          ),
        );
      }
    } else $row['parent'] = '';
    $row += parent::buildRow($entity);
    $row['operations']['data']['#links']['add'] = array(
      'title' => $this->t('Add an Entity'),
      'url' => new Url('entity.wisski_individual.add',array('wisski_bundle' => $id)),
      'weight' => 5,
    );
    $row['operations']['data']['#links']['list'] = array(
      'title' => $this->t('List Entities'),
      'weight' => 10,
      'url' => $entity->urlInfo('entity-list'),
    );
    $row['operations']['data']['#links']['regenerate_titles'] = array(
      'title' => $this->t('Update Entity Titles'),
      'weight' => 11,
      'url' => Url::fromRoute('wisski.titles.bulk_update', array('bundle' => $id)),
    );  
//    dpm($row['operations']['data']['#links'],__METHOD__);
    return $row;
  }

  private function buildCreateRow($entity, $menu_param = 'entity.wisski_individual_create.list') {

    // see if there is a row in the navigation menu.
    $entities = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['menu_name' => 'create', 'title' => $entity->label(), 'link__uri' => 'route:' . $menu_param .  ';' . 'wisski_bundle=' . $entity->id() ]);

    // there should not be more than one.
    $entities = current($entities);

    if(empty($entities) || !$entities->isEnabled())
      return array();

 #   dpm($entities);    
    $row['label'] = array(
      'data' => array(
        '#type' => 'link',
        '#url' => $entities->getUrlObject(),
        '#title' => $entities->getTitle(),

#        '#url' => Url::fromRoute('entity.wisski_individual.add')
#        '#url' => Url::fromRoute('entity.wisski_bundle.entity_list')
#          ->setRouteParameters(array('wisski_bundle' => $entity->id())),
#        '#title' => $entity->label(),
      ),
    );
    
    return $row;
  }
  

  /**
   * {@inheritdoc}
   */
  public function render($type = self::CONFIG) {
    $this->type = $type;
    $build = parent::render();
    $build['#empty'] = t('No WissKI bundle available. <a href="@link">Add media bundle</a>.', array(
      '@link' => Url::fromRoute('entity.wisski_bundle.add')->toString(),
    ));
    return $build;
  }

}
