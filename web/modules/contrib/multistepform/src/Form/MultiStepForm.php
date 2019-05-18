<?php

namespace Drupal\multistepform\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeForm;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;



/**
 * Class MultiStepForm.
 */
class MultiStepForm extends NodeForm {

  protected $step = 1;
  protected $steps = [];

  protected $tempStore;

  public function __construct(EntityManagerInterface $entity_manager, PrivateTempStoreFactory $temp_store_factory, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, AccountInterface $current_user, $tempstore) {
    parent::__construct($entity_manager,$temp_store_factory, $entity_type_bundle_info, $time, $current_user);
    $this->tempStore = $tempstore;
  }


  public static function create(ContainerInterface $container){
    return new static(
      $container->get('entity.manager'),
      $container->get('tempstore.private'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('user.private_tempstore')->get('multi_step_form')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multi_step_form';
  }


  protected function getSteps($comps){
    $steps = [0=>-1];
    foreach($comps as $f=>$comp){
      if(!empty($comp['third_party_settings']['multistepform']['step'])){
        $steps[$comp['third_party_settings']['multistepform']['step']][] = $f;
      }
    }

    $this->steps = array_keys($steps);
    sort($this->steps);
    unset($steps[0]);
    unset($this->steps[0]);
    // sort($steps);
    // pr(array_values($steps));exit;
    return $steps;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $tempStore = \Drupal::service('user.private_tempstore')->get('multistep_data');
    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, 'multistep');
    $form_state->set('form_display',$form_display);
    $form = parent::form($form, $form_state);
    // $fields = $this->entity->getFieldDefinitions();
    // pr($form_display->getComponents());exit;
    $comps = $form_display->getComponents();
    $steps = $this->getSteps($comps);
    $allStepFields = [];
    foreach($steps as $item){
      foreach($item as $it){
        $allStepFields[] = $it;
      }
    }
    foreach($form_display->getComponents() as $field => $v){      
      if($this->step <= count($this->steps)){
        if(!in_array($field, $steps[$this->steps[$this->step]])){
            unset($form[$field]);
        }
      }else{
        if(in_array($field, $allStepFields)){
          unset($form[$field]);
        }
      }
    }
    if($this->step <= count($this->steps)) {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#weight' => 1000,
        '#submit' => ['::nextAction'],
        '#validate' => []
      ];
    }
    else {
      $form['actions'] = parent::actions($form, $form_state);
      $form['actions']['#weight'] = 1000;
    }
    return $form;
  }

  protected function actions(array $form, FormStateInterface $form_state) {
    return parent::actions($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // if($this->step = 9) {
      // pr($this->step);
      // parent::validateForm($form, $form_state);
      // pr($form_state->getValues());exit;
    // }
  }


  public function nextAction(array &$form, FormStateInterface $form_state){
    foreach($form_state->getValues() as $key => $val){
      $this->tempStore->set($key, $val);
    }
    $form_state->setRebuild();
    $this->step++;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach($form_state->getValues() as $key => $val){
      $this->tempStore->set($key, $val);
    }
    $form_display = $form_state->get('form_display');
    foreach($form_display->getComponents() as $field => $v){
      $form_state->setValue($field,$this->tempStore->get($field));
      $this->tempStore->delete($field);
    }
    $this->entity->validate();
    // pr($form_state->getValues());exit;
    return parent::submitForm($form, $form_state);
  }

}
