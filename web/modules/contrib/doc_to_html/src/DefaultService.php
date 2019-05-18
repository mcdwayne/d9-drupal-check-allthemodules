<?php

namespace Drupal\doc_to_html;

use Drupal\Core\State\StateInterface;
use Drupal\Core\Form\FormStateInterface;



/**
 * Class DefaultService.
 *
 * @package Drupal\doc_to_html
 */
class DefaultService {

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var Drupal\Core\State\StateInterface.
   */
  protected $defaultservice;

  /**
   * Constructor.
   */
  public function __construct($state) {
    $this->defaultservice = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * @return array whit admitted OS
   */
  public function admittedOS(){
    return array(
      'MAC OS' => 'Darwin',
      'LINUX' => 'Linux',
      'Windows' => 'WINNT'
    );
  }

  /**
   * @param $os
   * @return array
   *   return basic settings for specific OS
   */
  public function settingsOS($os){

    switch($os){
      case 'Darwin':
          return  array(
            'base_path_application' => '/Applications/LibreOffice.app/Contents/MacOS/',
            'command' => './soffice',);
        break;

      case 'Linux':
          return array(
            'base_path_application' => '',
            'command' => 'soffice',
          );
        break;

      case 'WINNT':
          return array(
            'base_path_application' => '',
            'command' => '',
          );
        break;
      default:
        return array(
          'base_path_application' => '',
          'command' => '',
        );
    }
  }

  public function GetEntityBundleFieldBy($field_type){
    $result = array();
    $fieldType = \Drupal::service('entity_field.manager')->getFieldMapByFieldType($field_type);

    // Extract all entity_type and fields
    foreach($fieldType as $entityType => $fields){
      // Extract Field_name and Field_info
      foreach ($fields as $field_name => $field_info){

        // Extract all Bundles
        $bundles = $field_info['bundles'];
        foreach ($bundles as $bundle) {
          // Get Field Definition.
          $field = \Drupal::service('entity_field.manager')->getFieldDefinitions($entityType, $bundle);

          // Get Field Label.
          $field_label= $field[$field_name]->get('label');

          // Get bundle info and label
          $bundles_info = \Drupal::service("entity_type.bundle.info")->getAllBundleInfo();
          $bundle_title = $bundles_info[$entityType][$bundle]['label'];

          $result[$entityType.'-'.$bundle.'-'.$field_name] = array(
            'bundle_title' => $bundle_title,
            'bundle' => $bundle,
            'field_label' => $field_label,
            'field_name' => $field_name
          );
        }
      }
    }
    return $result;
  }

  /**
   * @return array
   */
  public function getSupporttedFile(){
    // Get supportted file.
    $doc = \Drupal::config('doc_to_html.basicsettings')->get('doc');
    $docx = \Drupal::config('doc_to_html.basicsettings')->get('docx');

    // Define empty string.
    $string ='';

    // Support for doc.
    if($doc === 1){
      $string = 'doc';
    }

    // Support for docx.
    if($doc != '' && $docx === 1){
      $string .= ' docx';
    }
    else {
      $string .= 'docx';
    }

    // return empty array for support if not settings saved
    if($string != ''){
      return array($string);
    }
    else{
      return array('');
    }
  }
}
