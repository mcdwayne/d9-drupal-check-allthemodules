<?php
namespace Drupal\jasm\Controller;

// use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of JASM services.
 */
class JasmListBuilder extends DraggableListBuilder {
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jasm_config_entity_service_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['preset']           = $this->t('Preset');
    $header['status']           = $this->t('Status');
    $header['label']            = $this->t('JASM service');
    $header['id']               = $this->t('Machine name');
    $header['service_page_url'] = $this->t('URL');
    $header['weight']           = $this->t('Weight');
    
    return $header + parent::buildHeader();
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['preset']           = $entity->preset;
    $row['status']           = $entity->status;
    $row['label']            = $entity->label;
    $row['id']               = $entity->id();
    $row['service_page_url'] = $entity->service_page_url;
    $row['weight']           = $entity->weight;
    
    return $row + parent::buildRow($entity);
  }
  
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    
    drupal_set_message(t('The JASM settings have been updated.'));
  }
}