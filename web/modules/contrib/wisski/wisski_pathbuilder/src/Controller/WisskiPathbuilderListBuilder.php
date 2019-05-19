<?php
/**
 * @file
 *
 * Contains drupal\wisski_pathbuilder\WisskiPathbuilderListBuilder
 */
 
namespace Drupal\wisski_pathbuilder\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

class WisskiPathbuilderListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('name');
    #$header['label'] = $this->t('name');
    
    return $header + parent::buildHeader();
  }
 
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
 
    // id
    $row['name'] = $entity->getName(); 
    #$this->getLabel($entity);
   
    return $row + parent::buildRow($entity);
  }

  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    
#    $url = \Drupal\Core\Url::fromRoute('entity.wisski_pathbuilder.overview', ['wisski_pathbuilder' => $entity->id()]);
                           
#    $operations['view_paths'] = array(
#      'title' => $this->t('View Paths'),
#      'weight' => 10,
#      'url' => $url, 
#    );
                        
    return $operations;
  } 
  
  /**
   * {@inheritdoc}
   */
  /*
  public function render() {
    
    $build = parent::render();
    
    $build['#empty'] = $this->t('There are no Pathbuilders defined.');
    return $build;
  }
  */
  
}
