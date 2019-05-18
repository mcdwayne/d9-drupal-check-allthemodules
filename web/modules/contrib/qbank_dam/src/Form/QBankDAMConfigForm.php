<?php

namespace Drupal\qbank_dam\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\qbank_dam\QBankDAMService;
// use Drupal\Core\Database\Database;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class QBankDAMConfigForm.
 *
 * @package Drupal\qbank_dam\Form
 */
class QBankDAMConfigForm extends ConfigFormBase {

  protected $QAPI;

  public function __construct(QBankDAMService $qbank_api) {
    $this->QAPI = $qbank_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('qbank_dam.service'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'qbank_dam.qbankdamconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qbank_dam_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('qbank_dam.qbankdamconfig');

    if ($this->QAPI->checkStoredConfiguration()){
      $deploymentSites = $this->QAPI->getDeploymentSites();
    }
    else {
      $deploymentSites = [ 'No site' => $this->t('No site'), ];
    }


    $form['group1'] = array(
      '#type' => 'fieldset',
      '#title' => t('Main Config'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,  
    );

    $form['group1']['protocol'] = [
      '#type' => 'select',
      '#title' => $this->t('Protocol'),
      '#options' => ['HTTP' => $this->t('HTTP'), 'HTTPS' => $this->t('HTTPS')],
      '#size' => 2,
      '#default_value' => $config->get('protocol'),
    ];
    $form['group1']['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Url'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('api_url'),
    ];
    $form['group1']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID *'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('client_id'),
    ];
    $form['group1']['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('user'),
    ];
    $form['group1']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('password'),
    ];
    $form['group1']['session_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Session ID'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('session_id'),
    ];
    $form['group1']['deployment_site'] = [
      '#type' => 'select',
      '#title' => $this->t('Deployment site'),
      '#options' => $deploymentSites,
      '#size' => 0,
      '#default_value' => $config->get('deployment_site'),
    ];

    $form['metadata_config'] = [
      '#type' => 'hidden',
    ];
   
    $form['group2'] = array(
      '#type' => 'fieldset',
      '#title' => t('Image Property Mapping'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,  
    );
  

    $form['group2']['metadata_mapping_table'] = array(
      '#type' => 'table',
      '#caption' => $this
        ->t('Map Qbank image properties with Drupal fields'),
      '#header' => array(
        $this
          ->t('Drupal field'),
        $this
          ->t('Qbank Image property name'),
        $this
          ->t('Action')
      ),
    );

    $form['group2']['btn_add_mapping'] = [
      '#type' => 'button',
      '#value' => t('+ ADD NEW'),
    ];


    $x = 1;
    if($config->get('map') != NULL){
      $mapJson = json_decode($config->get('map'), true);
      if(count($mapJson) > 0){
        foreach($mapJson as $key => $value){
          $form['group2']['metadata_mapping_table'][$x]['#attributes'] = array(
            'class' => array(
              'metadata_mapping_table_row'
            ),
          );

          if($value == "" && $key == ""){
              $form['group2']['metadata_mapping_table'][$x]['drupal'] = array(
            '#type' => 'textfield',
            '#title' => $this
              ->t('Qbank'),
            '#title_display' => 'invisible',
            );
            $form['group2']['metadata_mapping_table'][$x]['qbank'] = array(
              '#type' => 'textfield',
              '#title' => $this
                ->t('Drupal'),
              '#title_display' => 'invisible',
            );
            $form['group2']['metadata_mapping_table'][$x]['remove'] = array(
              '#type' => 'button',
              '#value' => t('X'),
              '#title_display' => 'invisible',
             
            );
          }else{
            $form['group2']['metadata_mapping_table'][$x][$value] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Meta Data '.$value.' map'),
            '#maxlength' => 64,
            '#size' => 64,
            '#default_value' => $value,
            '#title_display' => 'invisible',
            );

            $form['group2']['metadata_mapping_table'][$x][$key] = array(
              '#type' => 'textfield',
              '#title' => $this->t('Meta Data '.$key.' map'),
              '#maxlength' => 64,
              '#default_value' => $key,
              '#title_display' => 'invisible',
            );

            
            
            $form['group2']['metadata_mapping_table'][$x]['remove'] = array(
              '#type' => 'button',
              '#value' => t('X'),
              '#title_display' => 'invisible',         
            );
  
          }

          $x++;
        }
      }else{
        $form['group2']['metadata_mapping_table'][1]['#attributes'] = array(
          'class' => array(
            'metadata_mapping_table_row'
          ),
        );
        $form['group2']['metadata_mapping_table'][1]['drupal'] = array(
          '#type' => 'textfield',
          '#title' => $this
            ->t('Qbank'),
          '#title_display' => 'invisible',
        );
        $form['group2']['metadata_mapping_table'][1]['qbank'] = array(
          '#type' => 'textfield',
          '#title' => $this
            ->t('Drupal'),
          '#title_display' => 'invisible',
        );
        $form['group2']['metadata_mapping_table'][1]['remove'] = array(
          '#type' => 'button',
          '#value' => t('X'),
          '#title_display' => 'invisible',
         
        );
      }
    }else{      
        $form['group2']['metadata_mapping_table'][1]['#attributes'] = array(
          'class' => array(
            'metadata_mapping_table_row'
          ),
        );
        $form['group2']['metadata_mapping_table'][1]['drupal'] = array(
          '#type' => 'textfield',
          '#title' => $this
            ->t('Qbank'),
          '#title_display' => 'invisible',
        );
        $form['group2']['metadata_mapping_table'][1]['qbank'] = array(
          '#type' => 'textfield',
          '#title' => $this
            ->t('Drupal'),
          '#title_display' => 'invisible',
        );
        $form['group2']['metadata_mapping_table'][1]['remove'] = array(
          '#type' => 'button',
          '#value' => t('X'),
          '#title_display' => 'invisible',
         
        );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($this->QAPI->checkConfiguration(
        $form_state->getValue('api_url'),
        $form_state->getValue('client_id'),
        $form_state->getValue('user'),
        $form_state->getValue('password')
      ) == NULL) {
      $form_state->setErrorByName('missing qbank_url', $this->t('Unable to connect to QBank DAM API, please check your configuration'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('qbank_dam.qbankdamconfig')
      ->set('protocol', $form_state->getValue('protocol'))
      ->set('api_url', $form_state->getValue('api_url'))
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('user', $form_state->getValue('user'))
      ->set('password', $form_state->getValue('password'))
      ->set('session_id', $form_state->getValue('session_id'))
      ->set('deployment_site', $form_state->getValue('deployment_site'))
      ->set('map', $form_state->getValue('metadata_config'))
      ->save();

    }

}
