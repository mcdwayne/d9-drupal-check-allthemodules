<?php

/**
 * @file
 * Contains \Drupal\wisski_adapter_yaml\XmlAdapterBase.
 */

namespace Drupal\wisski_adapter_xml;

use Drupal\wisski_adapter_xml\Query\Query;

use Drupal\Core\Form\FormStateInterface;
use Drupal\wisski_salz\EngineBase;

use Drupal\Core\Entity\EntityTypeInterface;


/**
 * Configuration Base for the Wisski XML Adapter Engine
 */
abstract class XmlAdapterBase extends EngineBase {

  protected $entity_string;
  
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + array(
      'entity_string' => '<?xml version="1.0" encoding="UTF-8"?>
        <bookstore>
          <book category="COOKING">
            <title lang="en">Knurgonian Pizza</title>
            <author>Knurg</author>
            <year>2009</year>
            <price>31.00</price>
          </book>
          <book category="FURNISHING">
            <title lang="en">Knurgonian Tables</title>
            <author>Knurg</author>
            <year>2013</year>
            <price>39.00</price>
          </book>
        </bookstore>    
      ',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
//  dpm(func_get_args(),__METHOD__);
    parent::setConfiguration($configuration);
    $this->entity_string = $this->configuration['entity_string'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'entity_string' => $this->entity_string,
    ] + parent::getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    
    $form['entity_string'] = array(
      '#type' => 'textarea', 
      '#title' => 'Entity Info', 
      '#default_value' => $this->entity_string, 
      '#description' => 'The entity information in XML-like syntax',
    );
    
    return parent::buildConfigurationForm($form, $form_state) + $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    parent::buildConfigurationForm($form, $form_state);
    $this->entity_string = $form_state->getValue('entity_string');
    $this->id = $form_state->getValue('id');
  }
  
  public function getQueryObject(EntityTypeInterface $entity_type,$condition,array $namespaces) {
//    dpm(func_get_args(),__METHOD__);
    return new Query($entity_type,$condition,$namespaces,$this);
  }  
}
