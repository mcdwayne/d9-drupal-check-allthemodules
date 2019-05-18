<?php

namespace Drupal\registry_proxies\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * @Block(
 *   id = "proxies_block",
 *   admin_label = @Translation("Namespaces block"),
 *   category = @Translation("Registry"),
 * )
 */

class ProxiesBlock extends BlockBase  {
   /**
   * {@inheritdoc}
   */
  
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    
    return $form;
  }
//  public function build() {
//    $node = \Drupal::routeMatch()->getParameter('node');
//    $host = \Drupal::request()->getHost(); 
//    $shortname = $node->get('field_kurzname')->getValue()[0]['value'];
//    $url = $host.'/register/namespace/'.$shortname.'/{OID}/{Version}';
//    
//    
//    $form = array();
//    $form['help'] = [
//      '#type' => '#markup',
//      '#markup' => $this->t('This can be used to test the ID-Resolver respectively the redirect of this namespace.<br><br>'),
//    ];
//    $form['url'] = [
//      '#type' => '#markup',
//      '#markup' => $url,
//    ];
//    $form['shortname'] = [
//      '#type' => 'hidden',
//      '#value' => $shortname,
//      '#required' => TRUE,
//    ];
//    $form['objectid'] = [
//      '#type' => 'textfield',
//      '#title' => $this->t('ObjectID {OID}'),
//      '#default_value' => '',
//      '#size' => 60,
//      '#maxlength' => 128,
//      '#required' => TRUE,
//    ];
//    $form['version'] = [
//      '#type' => 'textfield',
//      '#title' => $this->t('Version {Version}'),
//      '#default_value' => '',
//      '#size' => 60,
//      '#maxlength' => 128,
//      '#required' => FALSE,
//    ];
//    $form['actions'] = array('#type' => 'actions');
//    $form['actions']['submit'] = [
//      '#type' => 'submit',
//      //'#submit' => $this->blockSubmit(),
//      '#value' => $this->t('Test ID-Resolver'),
//    ];
//    return $form;
//    
//  }
//  
//  
//  
//  public function blockForm($form, FormStateInterface $form_state) {
//    $form = parent::blockForm($form, $form_state);
//
//    $config = $this->getConfiguration();
//
//    $form['hello_block_name'] = array(
//      '#type' => 'textfield',
//      '#title' => $this->t('Who'),
//      '#description' => $this->t('Who do you want to say hello to?'),
//      '#default_value' => isset($config['hello_block_name']) ? $config['hello_block_name'] : '',
//    );
//
//    return $form;
//  }
//  
//  
//  public function blockSubmit($form, FormStateInterface $form_state) {
//    parent::blockSubmit($form, $form_state);
//    $values = $form_state->getValues();
//    $this->configuration['hello_block_name'] = $values['hello_block_name'];
//  }
  
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    
    return array(
      '#markup' => $this->t('@org, @loc. Email id : @mail Phn: @phn. Address: @add', array('@add'=> $org_add,'@phn'=> $org_phn,'@mail'=> $org_mail,'@org'=> $org_name,'@loc' => $org_loca)),
    );
  }
  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
   
  }
}
