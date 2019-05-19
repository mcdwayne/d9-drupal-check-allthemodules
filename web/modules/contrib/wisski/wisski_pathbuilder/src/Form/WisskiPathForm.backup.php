<?php
/**
 * @file
 * Contains \Drupal\wisski_pathbuilder\Form\WisskiPathForm
 */
 
namespace Drupal\wisski_pathbuilder\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface; 
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Url;
use Drupal\wisski_salz\EngineInterface;
use Drupal\wisski_pathbuilder\PathbuilderEngineInterface;

/**
 * Class WisskiPathForm
 * 
 * Fom class for adding/editing WisskiPath config entities.
 */
 
class WisskiPathForm extends EntityForm {
      

  protected $pb = NULL;

  #public function getFormId() {
  #  return 'wisski_path_form';
  #}  
  /**
   * @{inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $wisski_pathbuilder = NULL) { 

    // the form() function will not accept additional args,
    // but this function does
    // so we have to override this one to get hold of the pb id
    $this->pb = $wisski_pathbuilder;
#    dpm($this->pb, 'pb');
#    drupal_set_message('BUILD: ' . serialize($form_state));
    return parent::buildForm($form, $form_state, $wisski_pathbuilder);
    
  }
  
   /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {    

    $now = time();
    \Drupal::logger('wisski_path_form')->debug('Call '.$now);
#    drupal_set_message('FORM: ' . serialize($form_state)); 
/*
    $form = parent::form($form, $form_state);
    $twig = \Drupal::service('twig');
dpm($twig);
    $twig->enableDebug();
    $twig->enableAutoReload();
*/
//dpm($form,'Input Form');    
    // get the entity    
    $path = $this->entity;

    // do we have an engine for queries?
    $got_engine = FALSE;
    
#    dpm($this->pb, "pb in form: ");
    
    // load the pb entity this path currently is attached to 
    // we found this out by the url we're coming from!
    $pb = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::load($this->pb);

    // load the adapter of the pb
    $adapter = \Drupal\wisski_salz\Entity\Adapter::load($pb->getAdapterId());

    // if there was an adapter
    if ($adapter) {
      // then we can get the engine
      $engine = $adapter->getEngine();    

      if ($engine) $got_engine = TRUE;
    } // else we should fail here I think.

    // Change page title for the edit operation
    if($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit Path: @id', array('@id' => $path->getID()));
    }
                                                                                                            
    // the name for this path
    $form['name'] = array(
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#title' => $this->t('Name'),
      '#default_value' => empty($path->getName()) ? NULL : $path->getName(),
      '#attributes' => array('placeholder' => $this->t('Name for the path')),
      //'#description' => $this->t("Name of the path."),
      '#required' => true,
    );
    
    // automatically calculate a machine name based on the name field
    $form['id'] = array(
      '#type' => 'machine_name',
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#default_value' => $path->getID(),
      '#disabled' => !$path->isNew(),
      '#machine_name' => array(
        'source' => array('name'),
        'exists' => 'wisski_path_load',
      ),
      '#required' => TRUE,
    );
    
    // the name for this path
    $form['type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Path Type'),
      '#options' => array("Path" => "Path", "Group" => "Group", "SmartGroup" => "SmartGroup"),
      '#default_value' => $path->getType(),
      '#description' => $this->t("Is this Path a group?"),
    );
    
    // only ask for alternatives if there is an engine.
    if (!$got_engine) {
      drupal_set_message("There is no engine - please create one.", "error");
      return;
    }

    $storage = $form_state->getStorage();  

    if ($engine->providesSimpleAlternatives()) {
      $simple_mode = isset($storage['simple_mode']) ? $storage['simple_mode'] : TRUE;
      if ($trigger = $form_state->getTriggeringElement()) {
        if ($trigger['#parents'][0] === 'mode_button') $simple_mode = !$simple_mode;
      }
      $form['mode_selection'] = array(
        '#type' => 'details',
        '#title' => $this->t('Step Detection Mode: %mode',array('%mode' => ($simple_mode ? $this->t('simple') : $this->t('complex')))),
        '#prefix' => '<div id=wisski-mode-selection>',
        '#suffix' => '</div>',
      );
      $complex_description = $this->t('Changing to complex mode will provide the complete list of step alternatives in every select box below. This might lead to highly increased detection time.');
      $simple_description = $this->t('Changing to simple mode will increase the speed of alternative detection. This might lead to incomplete option lists in the select boxes');
      $form['mode_selection']['mode_description'] = array(
        '#type' => 'item',
        '#markup' => $simple_mode ? $complex_description : $simple_description,
      );
      $form['mode_selection']['mode_button'] = array(
        '#type' => 'button',
        '#value' => $simple_mode ? $this->t('Change to complex mode') : $this->t('Change to simple mode'),
        '#ajax' => array(
          'wrapper' => 'wisski-mode-selection',
          'callback' => array($this,'simpleModeAjax'),
        ),
      );
    } else $simple_mode = FALSE;

    
    $existing_paths = array();
    #drupal_set_message('val ' . serialize($form_state->getValues()));
    #drupal_set_message('path_data ' . serialize($form_state->getValue('path_data')));

    // if there was something in form_state - use that because it is likely more accurate
    #if(empty($form_state->getValue('path_data'))) {
    
    //BEGIN find the correct values for path_array and datatype_property
    //get the user input to see if there was a change
    $input = $form_state->getUserInput();
    dpm($input,'Input '.$now);
    #dpm($form_state->getStorage(), 'storage');
    
    
    if(empty($input)) {
      //no input means the form is fresh and we take the info form the path entity
      if(!empty( $path->getPathArray() ))
        $existing_paths = $path->getPathArray();
      $datatype_property = $path->getDatatypeProperty()?:'empty';
      $disamb = $path->getDisamb()?:'empty';
#      drupal_set_message('getPathArray: ' . serialize($existing_paths));
       
    } else {
      
      //we had a click so the user changed something
      //first gather the chced values
      $paout = isset($storage['existing_paths']) ? $storage['existing_paths'] : array();
      //dpm($storage,'Click');
      // in case of there is something in storage...
      if(!empty($paout)) {
      
        $datatype_property = isset($storage['datataype_property']) ? $storage['datatype_property'] : 'empty';
        //now, let's se whats new
        $trigger = $form_state->getTriggeringElement();
        #dpm($trigger,'Trigger');
        $matches = array();
        //the ajax triggers have wisski-data attibutes set to
        // selectNN, btnNN, delNN, or data0
        // see below
        // so we can find out what was intended to be changed
        if (preg_match('/^([a-z]+)(\d*)$/',$trigger['#attributes']['data-wisski'],$matches)) {
          //$trigger type is select, btn, del, or data, respectively
          //$row num represents the number of the table row where the cahnge was done (empty for datatype_property)
          list(,$trigger_type,$row_num) = $matches;
        }
        
#        dpm($input,'before');
        if ($trigger_type === 'select') {
          //triggered a standard step selection, so we change the selected row to the chosen value
          $paout[$row_num] = $input['wisskipathselect'.$row_num];#$input['path_array']['step:'.$row_num]['select'];
        }      
        if ($trigger_type === 'btn' && $paout[$row_num] !== 'empty') {
          //triggered path enhancement, so we add two empty steps right BEFORE the selected row
          $paout = \Drupal\wisski_core\WisskiHelper::array_insert($paout,array('empty','empty'),$row_num);
        }
        if ($trigger_type === 'del' && count($paout) > $row_num + 1) {
          //triggered row deletion, removes two steps beginning with the selected row
          $paout = \Drupal\wisski_core\WisskiHelper::array_remove_part($paout,$row_num,2);
        }
        if ($trigger_type === 'data' && $row_num == 0) {
          //triggered datatype selection, change to chosen value
          $datatype_property = $input['path_array']['datatype_property']['datatypeselect'];
        }
        if ($trigger_type === 'data' && $row_num == 1) {
          //triggered datatype selection, change to chosen value
          $disamb = $input['path_array']['disamb']['disambselect'];
        }
        //dpm($paout,'after');
        $existing_paths = $paout;

#      drupal_set_message('pa: ' . serialize ($pa));     
      } else { // case else - primary if we are editing
        // everything is in input - don't ask me why!
        //dpm($input,'Input else');
        
        // @Dorian: Do you testing. This one killed all paths when clicking
        // readded old code, needs good explanation to not do so.
        foreach($input as $key => $something) {
        #if (isset($input['_triggering_element_name'])) {
        #  $key = $input['_triggering_element_name'];
          $did_match = preg_match('/^([a-z]+)(\d*)$/', $key, $matches);
          if (!$did_match) {
            continue;
#            drupal_set_message($this->t('The trigger name didn\'t match'),'error');
          } else {
            list(,$trigger_type,$row_num) = $matches;
          }
          
          if($trigger_type == 'wisskipathselect')
            $paout[$row_num] = $input[$key];
          #dpm($paout, "pa out in step $key");
        }
        
        $datatype_property = $input['path_array']['datatype_property']['datatypeselect'];
        $disamb = $input['path_array']['disamb']['disambselect'];
        $existing_paths = $paout;
      }
    }
    #drupal_set_message("HI");
    #drupal_set_message('isRebuilding? ' . serialize($form_state->isRebuilding()));  
    #$form_state->setRebuild();
    
    if (end($existing_paths) !== 'empty') $existing_paths[] = 'empty';

    $storage['simple_mode'] = $simple_mode;
    $storage['existing_paths'] = $existing_paths;
    $storage['datatype_property'] = $datatype_property;
    $storage['disamb'] = $disamb;
    #dpm($storage,'Storage to write');
    $form_state->setStorage($storage);

    //END value detection, now $existing_paths and $datatype_property are set correctly, according to entity info and/or user input
#    drupal_set_message(serialize($existing_paths));

    
    $curvalues = $existing_paths;
    //dpm($curvalues, 'curvalues');

    $form['#pathcount'] = count($curvalues);
    
    // count the steps as the last one doesn't need a button
    $i = 0; 
    
    $form['path_array'] = array(
      '#type' => 'table',
      '#prefix' => '<div id="wisski-path-table">',
      '#suffix' => '</div>',
      '#header' => array('step' => $this->t('Step'),'edit' => '','opis' => ''),
      //'#tree' => TRUE,
    );
    #dpm($curvalues, "curval");
    // go through all values and create fields for them
    foreach($curvalues as $key => $element) {
      $form['path_array']['step:'.$key] = array(
        '#type' => 'container',
        '#tree' => TRUE,
        '#attributes' => array('class' => 'wisski-row', 'id' => 'wisski-row-'.$key),
      );
#      drupal_set_message("key " . $key . ": element " . $element);
      if ($key > 0) {
        $pre = $curvalues[($key-1)] !== 'empty' ? array($curvalues[($key-1)]) : array();
        // this does not result in correct uris - perhaps reasoning missing?
        #$succ = (isset($curvalues[($key+1)]) && $curvalues[($key+1)] !== 'empty') ? array($curvalues[($key+1)]) : array();
        #$path_options = $engine->getPathAlternatives($pre,$succ);
        $path_options = $simple_mode ? $engine->getSimplePathAlternatives($pre) : $engine->getPathAlternatives($pre);
      } else {
        $path_options = $engine->getPathAlternatives();
        $pre = "";
      }
      if (!in_array($element,$path_options)) {
        $path_options[$element] = $element;
        uksort($path_options,'strnatcasecmp');
      }
      $form['path_array']['step:'.$key]['select'.$key] = array(
         //'#default_value' => 'empty',
        '#value' => $element,
        '#type' => 'select',
        '#name' => 'wisskipathselect' . $key,
        '#empty_value' => 'empty',
        '#empty_option' => $this->t('Select next step'),
        '#options' => $path_options,
        //'#title' => $this->t('Step ' . $key . ': Select the next step of the path'),
        //'#title_display' => 'invisible',
        '#attributes' => array('data-wisski' => 'select'.$key),
        '#description' => $pre,
        '#ajax' => array(
          'callback' => '\Drupal\wisski_pathbuilder\Form\WisskiPathForm::ajaxPathData',
          'wrapper' => 'wisski-path-table',
          'event' => 'change', 
        ),
        '#limit_validation_errors' => array(),
        '#prefix' => '<div id=wisski-path-select-'.$key.'>',
        '#suffix' => '</div>',
      );
      $form['path_array']['step:'.$key]['edit_btn'.$key] = array(
        '#type' => 'link',
        '#title' => $this->t('edit'),
        '#url' => Url::fromRoute('<current>'),
        '#ajax' => array(
          'wrapper' => 'wisski-path-select-'.$key,
          'callback' => array($this,'editButtonCallback'),
        ),
      );
/*    
      if($i < count($curvalues) - 1 && !($i % 2)) {
        $form['path_array']['step:'.$key]['opis']['#type'] = 'actions';
        $form['path_array']['step:'.$key]['opis']['btn'. $key] = array(
          //'#type' => 'submit',
          '#type' => 'button',
          '#value' => '+'.$key,
          '#attributes' => array('data-wisski' => 'btn'.$key),
          '#ajax' => array(
            'callback' => '\Drupal\wisski_pathbuilder\Form\WisskiPathForm::ajaxPathData',
            'wrapper' => 'wisski-path-table',
            'event' => 'click', 
          ),
          '#name' => 'btn'.$key,
          '#limit_validation_errors' => array(),
        );
        $form['path_array']['step:'.$key]['opis']['del'.$key] = array(
          '#type' => 'button',
          '#value' => '-'.$key,
          '#attributes' => array('data-wisski' => 'del'.$key),
          '#ajax' => array(
            'callback' => '\Drupal\wisski_pathbuilder\Form\WisskiPathForm::ajaxPathData',
            'wrapper' => 'wisski-path-table',
            'event' => 'click', 
          ),
          '#name' => 'del'.$key,
          '#limit_validation_errors' => array(),
        );
      } else {
        $form['path_array']['step:'.$key]['opis'] = array(
          '#type' => 'hidden',
          '#title' => 'nop:'.$key
        );
      }  
*/
      
      $i++;
    }                         
        
    $primitive = array();

    $form['path_array']['datatype_property'] = array(
      '#type' => 'container',
      '#tree' => TRUE,
    );
    #dpm($form);    
    // only act if there is more than the dummy entry
    // and if it is not a property -> path length odd +1 for dummy -> even
    if(count($curvalues) > 1 && count($curvalues) % 2 == 0 && $primitive = $engine->getPrimitiveMapping($curvalues[(count($curvalues)-2)])) {  
      if (count($primitive) == 1) {
        $default = current($primitive);
#        dpm($default,'Default');
        $form_state->setValue(array('path_array','datatype_property','datatypeselect'),$default);
        $form['path_array']['datatype_property']['datatypeselect'] = array(
          '#default_value' => $default,
          '#value' => $default,
          '#type' => 'select',
          '#options' => $primitive,
          //'#title' => t('Please select the datatype property for the Path.'),
          '#ajax' => array(
            'callback' => '\Drupal\wisski_pathbuilder\Form\WisskiPathForm::ajaxPathData',
            'wrapper' => 'wisski-path-table',
            'event' => 'change', 
          ),
          '#attributes' => array('data-wisski' => 'data0'),
          '#disabled' => TRUE,
        );
      } else {
#        dpm($datatype_property, "dataprop");
        $form['path_array']['datatype_property']['datatypeselect'] = array(
          '#value' => $datatype_property,
          '#type' => 'select',
          '#empty_value' => 'empty',
          '#empty_option' => $this->t('Select datatype property'),
          '#options' => $primitive,
          //'#title' => t('Please select the datatype property for the Path.'),
          '#ajax' => array(
            'callback' => '\Drupal\wisski_pathbuilder\Form\WisskiPathForm::ajaxPathData',
            'wrapper' => 'wisski-path-table',
            'event' => 'change', 
          ),
          '#attributes' => array('data-wisski' => 'data0'),
          '#required' => TRUE,
        );
      }    
    } else $form['path_array']['datatype_property']['datatypeselect'] = array(
      '#type' => 'hidden',
      '#value' => 'empty',
    );
    $form['path_array']['datatype_property']['opis'] = array(
      '#type' => 'hidden',
      '#value' => 'opis',
    );
    
    if(count($curvalues) > 1 && count($curvalues) % 2 == 0 ) {  
      foreach($curvalues as $key => $curvalue) {
        if($key%2 == 0)
          $disamboptions[($key/2)+1] = $curvalue; 
      }
      $form['path_array']['disamb']['disambselect'] = array(
        '#value' => $disamb,
        '#type' => 'select',
        '#empty_value' => 'empty',
        '#empty_option' => $this->t('Select disambiguation concept'),
        '#options' => $disamboptions,
        //'#title' => t('Please select the datatype property for the Path.'),
#        '#ajax' => array(
#          'callback' => '\Drupal\wisski_pathbuilder\Form\WisskiPathForm::ajaxPathData',
#          'wrapper' => 'wisski-path-table',
#          'event' => 'change', 
#        ),
        '#attributes' => array('data-wisski' => 'data1'),
        '#required' => TRUE,
      );
    } else $form['path_array']['disamb']['disambselect'] = array(
      '#type' => 'hidden',
      '#value' => 'empty',
    );
    $form['path_array']['disamb']['opis'] = array(
      '#type' => 'hidden',
      '#value' => 'opis',
    );
    
    //dpm($form['path_array'], 'formixxx000');
    //dpm(\Drupal::service('form_builder')->prepareForm('wisski_path_form',$form['path_array'],$form_state));
    return $form;
  }
  
  public static function ajaxPathData(array $form, FormStateInterface $form_state) {
    \Drupal::logger('wisski_path_form')->debug('ajax call');
    return $form['path_array'];
  }
  
  public function simpleModeAjax(array $form,FormStateInterface $form_state) {
    
    return $form['mode_selection'];
  }

  public function editButtonCallback(array $form,FormStateInterface $form_state) {
    
    $trigger_button = $form_state->getTriggeringElement();
    dpm($trigger_button);
    $xpl = explode(':',$trigger_button['#array_parents'][1]);
    if ($xpl[0] === 'step') $key = $xpl[1];
    return $form['path_array']['step:0']['select0'];
  }
  
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    //parent::save($form,$form_state);
    //$pb = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilder::load($this->pb);
#    dpm(array($this->entity,$this->pb),__METHOD__);

#    drupal_set_message("I saved!");
#    return;

    $path = $this->entity;
    
    $status = $path->save();
#dpm($path,'Saved path');    
    if($status) {
      // Setting the success message.
      drupal_set_message($this->t('Saved the path: @id.', array(
        '@id' => $path->getID(),
      )));
    } else {
      drupal_set_message($this->t('The path @id could not be saved.', array(
        '@id' => $path->getID(),
      )));
    }
        
    if(empty($this->pb))
      $pbid = $form_state->getBuildInfo()['args'][0];
    else
      $pbid = $this->pb;
          
    // load the pb
    $pb = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::load($pbid);
     
       
    // add the path to its tree if it was not there already
    if(is_null($pb->getPbPath($path->id())))
      $pb->addPathToPathTree($path->id(), 0, $path->isGroup());
      
    // save the pb
    $status = $pb->save();

    $form_state->setRedirect('entity.wisski_pathbuilder.edit_form',array('wisski_pathbuilder' => $pbid));
  }
 
  /**
   * {@inheritdoc}
   * overridden to ensure the correct mapping of form values to entity properties
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    
    $values = $form_state->getValues();

    //From parent, not sure what this is necessary for
    if ($this->entity instanceof EntityWithPluginCollectionInterface) {
      // Do not manually update values represented by plugin collections.
      $values = array_diff_key($values, $this->entity->getPluginCollections());
    }

    //$values represent form values as hidden in the render elements i.e. the path steps can be found in
    // $values['path_array']['step:'.$row_number]['select']
    $values = $form_state->getValues();
#    dpm($values,__METHOD__.'::values');
    $path_array = array();
    foreach ($values['path_array'] as $key => $value) {
      //gather step values while ignoring empty lines
      if (strpos($key,'step') === 0) {
        $row = explode(':',$key)[1];
        if($value['select'.$row] !== 'empty') {
          $path_array[$row] = $value['select'.$row];
        }
      } elseif ($key === 'datatype_property') {
        $datatype_property = $value['datatypeselect'];
      } elseif ($key === 'disamb') {
        $disamb = $value['disambselect'];
      }
    }
    ksort($path_array);
    
#    dpm($path_array);
    $entity->setPathArray($path_array);
    //the $values do not accept the datatype_property value being named correctly, thus select is our desired goal
    $entity->setDatatypeProperty($datatype_property);
    $entity->setID($values['id']);
    $entity->setName($values['name']);
    $entity->setType($values['type']);
    $entity->setDisamb($disamb);    
    //dpm($entity,__FUNCTION__.'::path');
  }

}


