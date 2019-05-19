<?php
/**
 * @file
 * Contains \Drupal\wisski_pathbuilder\Form\WisskiPathbuilderConfigureFieldForm
 */
 
namespace Drupal\wisski_pathbuilder\Form;

use Drupal\Core\Form\FormStateInterface; 
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

use Drupal\wisski_core\WisskiHelper;
use Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity as Pathbuilder;

/**
 * Class WisskiPathbuilderForm
 * 
 * Fom class for adding/editing WisskiPathbuilder config entities.
 */
 
class WisskiPathbuilderConfigureFieldForm extends EntityForm {

  
  protected $pathbuilder = NULL;
  protected $path = NULL;
  
  /**
   * @{inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $wisski_pathbuilder = NULL, $wisski_path = NULL) { 
    // the form() function will not accept additional args,
    // but this function does
    // so we have to override this one to get hold of the pb id
    $this->pathbuilder = $wisski_pathbuilder;
#    drupal_set_message(serialize($wisski_path));
    $this->path = $wisski_path;
    return parent::buildForm($form, $form_state, $wisski_pathbuilder, $wisski_path);
  }

   /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
  
    //dpm($this->pathbuilder,'before edit');  
    $form = parent::form($form, $form_state);
    
    $form['pathbuilder'] = array(
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#title' => $this->t('Pathbuilder'),
      '#default_value' => empty($this->pathbuilder->getName()) ? $this->t('Name for the pathbuilder') : $this->pathbuilder->getName(),
      '#disabled' => true,
      '#description' => $this->t("Name of the pathbuilder."),
      '#required' => true,
    );
        
    $form['path'] = array(
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#title' => $this->t('Path'),
      '#default_value' => empty($this->path) ? $this->t('Name for the pathbuilder') : $this->path,
      '#disabled' => true,
      '#description' => $this->t("Name of the path."),
      '#required' => true,
    );

    
#    drupal_set_message(serialize($this->pathbuilder->getPathTree()));
    
#    $tree = $this->pathbuilder->getPathTree();

#    $element = $this->recursive_find_element($tree, $this->path);
    $pbpath = $this->pathbuilder->getPbPath($this->path);
    $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($this->path);
#    dpm($pbpath,'Path');
#    return $form;
    if($path->getType() != "Path") {
      $bundle_options = array();
      
      // typically for wisski bundle and fieldid have to be the same. Everything else is strange!
      // we warn the user then.
      if($pbpath['bundle'] !== Pathbuilder::CONNECT_NO_FIELD && $pbpath['bundle'] !== Pathbuilder::GENERATE_NEW_FIELD && !empty($pbpath['bundle']) && $pbpath['bundle'] != $pbpath['field']) {
        drupal_set_message('For path ' . $pbpath['id'] . " bundle is '" . $pbpath['bundle'] . "' but field is '" . $pbpath['field'] . "' and it typically should be the same. We change that for you.", "warning");
        
#        $pbpath['field'] = $pbpath['bundle'];
        
        $pbpaths_for_write = $this->pathbuilder->getPbPaths();
        $pbpaths_for_write[$this->path]['field'] = $pbpath['bundle'];
        $this->pathbuilder->setPbPaths($pbpaths_for_write);
        $this->pathbuilder->save();
        
      }

      // this was a buggy approach
#      foreach (WisskiHelper::getAllBundleIds(TRUE) as $bundle_id => $bundle_info) {
#        $bundle_options[$bundle_id] = $bundle_info['label'].' ('.$bundle_info['path_id'].' in '.$bundle_info['pathbuilder'].')';
#      }

      $all_bundles = \Drupal\wisski_core\Entity\WisskiBundle::loadMultiple();
      foreach($all_bundles as $bundle_id => $bundle_info) {
        $bundle_options[$bundle_id] = $bundle_info->label;
      }
      
      $bundle_options += array(
        Pathbuilder::CONNECT_NO_FIELD => $this->t('Do not connect a bundle'),
        Pathbuilder::GENERATE_NEW_FIELD => $this->t('Create a new bundle for this group'),
      );
      
#      dpm($pbpath);
      $default_value = empty($pbpath['bundle']) ? '' : $pbpath['bundle'];
      //dpm($bundle_options,'Bundle Options');
      $form['choose_bundle'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Bundle'),
      );
#      dpm($default_value);
#      return $form;
      $form['choose_bundle']['select_bundle'] = array(
        '#type' => 'select',
        '#description' => $this->t('Choose from the list of existing bundles'),
        '#options' => $bundle_options,
        '#default_value' => $default_value,
        '#empty_option' => ' - '.$this->t('select').' - ',
        '#empty_value' => '0',
        '#ajax' => array(
          'wrapper' => 'wisski-bundle-field',
          'callback' => array($this,'bundleCallback'),
        ),
      );
#      return $form;
      $value = $default_value;
      $trigger = $form_state->getTriggeringElement();
      if ($trigger['#name'] == 'select_bundle') {
        $value = $form_state->getValue('select_bundle') ? : '';
      }
      $form['choose_bundle']['bundle'] = array(
        '#type' => 'textfield',
        '#maxlength' => 255,
        '#default_value' => $default_value,
        '#value' => $value,
#      '#disabled' => true,
        '#description' => $this->t('Insert bundle ID'),
#        '#required' => true,
        '#prefix' => '<div id="wisski-bundle-field">',
        '#suffix' => '</div>',
      );
      
      $unlimited = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
      //dpm($pbpath);  
      $form['cardinality'] = array(
        '#type' => 'select',
        '#title' => $this->t('Cardinality'),
        '#default_value' => (empty($pbpath['cardinality']) ? $unlimited : $pbpath['cardinality']),
        '#options' => self::cardinalityOptions(),
      );
#      if (isset($selected_field_values['cardinality'])) $display['cardinality']['#value'] = $selected_field_values['cardinality'];
    }
    
    if($path->getType() == "Path") {
      $bundle_id = $pbpath['bundle'];

      // if there is no bundle, search for one
      if (empty($bundle_id)) {
        $current = $pbpath;
        
        while (empty($bundle_id) && !empty($current['parent']) && $current['id'] !== $current['parent']) {
          $current = $this->pathbuilder->getPbPath($current['parent']);
          $bundle_id = $current['bundle'];
        }
      }
      
      $field_options = array();
      
      if(!empty($bundle_id)) {
        if ($bundle = \Drupal\wisski_core\Entity\WisskiBundle::load($bundle_id)) {
          $bundle_label = $bundle->label();
        } else {
          if($bundle_id !== Pathbuilder::CONNECT_NO_FIELD && $bundle_id !== Pathbuilder::GENERATE_NEW_FIELD) 
            drupal_set_message($this->t('There is no group/bundle specified for this path'),'warning');
          $bundle_label = '';
        }
        //@TODO fill the field options array with existing fields in the given bundle
        $bundle_fields = \Drupal::entityManager()->getStorage('field_config')->loadByProperties(array('bundle' => $bundle_id));
        foreach ($bundle_fields as $bundle_field) {
          $field_name = $bundle_field->getName();
          $field_options[$field_name] = $bundle_field->getLabel().' ('.$field_name.')';
          $bundle_fields[$field_name] = $bundle_field;
        }
      }
      
      $field_options += array(
        Pathbuilder::CONNECT_NO_FIELD => $this->t('Do not connect a field'),
        Pathbuilder::GENERATE_NEW_FIELD => $this->t('Create a new field for this path'),
      );
      $default_value = empty($pbpath['field']) ? '' : $pbpath['field'];
      $form['field_form'] = array(
        '#type' => 'container',
        '#title' => $this->t('Field'),
        '#prefix' => '<div id="wisski-field-values">',
        '#suffix' => '</div>',
      );
      $form['field_form']['choose_field'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Choose field'),
      );
      
      
      $form['field_form']['choose_field']['select_field'] = array(
        '#type' => 'select',
        '#description' => (!empty($bundle_id) ? $this->t('Select an existing field from bundle %bundle_label',array('%bundle_label' => $bundle_label.' ('.$bundle_id.')')) : $this->t('Should a field be created?') ),
        '#options' => $field_options,
        '#default_value' => $default_value,
        '#empty_option' => ' - '.$this->t('select').' - ',
        '#empty_value' => '0',
        '#ajax' => array(
          'wrapper' => 'wisski-field-values',
          'callback' => array($this,'fieldCallback'),
        ),
      );
      
      
      $selected_field_name = $default_value;
      $trigger = $form_state->getTriggeringElement();
      //dpm($trigger,'Trigger');
      if ($trigger['#name'] == 'select_field' || $trigger['#name'] == 'field') {
        $selected_field_name = $form_state->getValue($trigger['#name']) ? : '';
      }
      $form['field_form']['choose_field']['field'] = array(
        '#type' => 'textfield',
        '#maxlength' => 255,
        '#description' => $this->t('Insert field ID'),
        '#default_value' => $default_value,
        '#value' => $selected_field_name,
#      '#disabled' => true,
        '#description' => $this->t("ID of the mapped Field."),
#        '#required' => true,
        '#ajax' => array(
          'wrapper' => 'wisski-field-values',
          'callback' => array($this,'fieldCallback'),
        ),        
      );
      
      if ($selected_field_name != $default_value) {
        $form['field_form']['disclaimer'] = array(
          '#type' => 'fieldset',
          '#attributes' => array('class' => array('messages','messages--warning')),
          'item' => array(
            '#markup' => $this->t('Changing the field properties below will result in changes EVERYWHERE the field is used. This will also affect all other pathbuilders using the same field and bundle'),
          ),
        );
        if (isset($bundle_fields[$selected_field_name])) {
          $selected_field = $bundle_fields[$selected_field_name];
          $selected_field_values = array(
            'field_type' => $selected_field->getType(),
            'formatter' => \Drupal::entityManager()
              ->getStorage('entity_view_display')
              ->load('wisski_individual' . '.'.$bundle_id.'.default')
              ->getComponent($selected_field_name)
              ['type'],
            'widget' => \Drupal::entityManager()
              ->getStorage('entity_form_display')
              ->load('wisski_individual' . '.'.$bundle_id.'.default')
              ->getComponent($selected_field_name)
              ['type'],
            'cardinality' => $selected_field->getFieldStorageDefinition()->getCardinality(),
          );
          //dpm($selected_field_values,'SFV');
        }
      
      }
            
      $formatter_types = \Drupal::service('plugin.manager.field.formatter')->getDefinitions();
      $widget_types = \Drupal::service('plugin.manager.field.widget')->getDefinitions();
      $field_types = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
      
      #drupal_set_message(serialize($widget_types));
      #drupal_set_message(serialize($formatter_types));
      
      #drupal_set_message(serialize($field_types));
      
      $listft = array();
      
      foreach($field_types as $key => $ft) {
        $listft[$key] = $ft['label'];
      }    

      // --- what is the current (default) value for the display of this field ---
      $ftvalue = NULL;
      // check if we are in ajax-mode, then there is something in form-state
      if ($trigger['#name'] === 'fieldtype')
        $ftvalue = $form_state->getValue('fieldtype');
      
      //if the FT itself was not triggered, we should look up in the field selection
      if (empty($ftvalue) && isset($selected_field_values))
        $ftvalue = $selected_field_values['field_type'];//$form_state->getValue('fieldtype');

      //from the database if there is nothing in form_state?
      if(empty($ftvalue))
        $ftvalue = empty($pbpath['fieldtype']) ? 'string' : $pbpath['fieldtype'];     

      // --- by now we should have found a value for the field type

      // generate the displays depending on the selected fieldtype
      $listdisplay = array();
      foreach($widget_types as $wt) {
        if(in_array($ftvalue, $wt['field_types']))
          $listdisplay[$wt['id']] = $wt['label'];
      }
      
      // generate the formatters depending on the selected fieldtype
      $listform = array();
      foreach($formatter_types as $wt) {
        if(in_array($ftvalue, $wt['field_types'])) 
          $listform[$wt['id']] = $wt['label'];
      }
      
      // do something for ajax      
      $display = array(
        '#type' => 'container',
        '#tree' => FALSE,
      );
      
      $display['fieldtype'] = array(
        '#type' => 'select',
        '#maxlength' => 255,
        '#title' => $this->t('Type of the field that should be generated.'),
        '#default_value' => $ftvalue,
        //'#value' => $ftvalue,
#      '#disabled' => true,
        '#options' => $listft,
        '#description' => $this->t("Type for the Field (Textfield, Image, ...)"),
        '#required' => true,
        '#ajax' => array(
          'callback' => 'Drupal\wisski_pathbuilder\Form\WisskiPathbuilderConfigureFieldForm::ajaxPathData',
          'wrapper' => 'wisski_display',
          'event' => 'change',
        ),
      );
      
      $display['field_display'] = array(
        '#type' => 'container',
        '#prefix' => '<div id="wisski_display">',
        '#suffix' => '</div>',
        '#tree' => FALSE,
      );
      
      $display['field_display']['displaywidget'] = array(
        '#type' => 'select',
        '#maxlength' => 255,
        '#title' => $this->t('Type of form display for field'),
        '#default_value' => empty($pbpath['displaywidget']) ? '' : $pbpath['displaywidget'],
#      '#disabled' => true,
        '#options' => $listdisplay,
        '#description' => $this->t("Widget for the Field - If there is any."),
#        '#required' => true,
      );
      if (isset($selected_field_values['widget'])) $display['displaywidget']['#value'] = $selected_field_values['widget'];
       
      $display['field_display']['formatterwidget'] = array(
        '#type' => 'select',
        '#maxlength' => 255,
        '#title' => $this->t('Type of formatter for field'),
        '#default_value' => empty($pbpath['formatterwidget']) ? '' : $pbpath['formatterwidget'],
#      '#disabled' => true,
        '#options' => $listform,
        '#description' => $this->t("Formatter for the field - If there is any."),
#        '#required' => true,
      );
      if (isset($selected_field_values['formatter'])) $display['formatterwidget']['#value'] = $selected_field_values['formatter'];
      
      $unlimited = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
      //dpm($pbpath);  
      $display['cardinality'] = array(
        '#type' => 'select',
        '#title' => $this->t('Cardinality'),
        '#default_value' => (empty($pbpath['cardinality']) ? $unlimited : $pbpath['cardinality']),
        '#options' => self::cardinalityOptions(),
      );
      if (isset($selected_field_values['cardinality'])) $display['cardinality']['#value'] = $selected_field_values['cardinality'];
      
      if (isset($selected_field_name) && $selected_field_name === Pathbuilder::CONNECT_NO_FIELD) {
        $display['#type'] = 'hidden';
      }
      
      $form['field_form']['display'] = $display;

    }
    
#    drupal_set_message("ft: " . serialize($ftvalue) . " dis " . serialize($listdisplay) . " for " . serialize($listform));
#dpm($form);    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function ajaxPathData(array $form, FormStateInterface $form_state) {
    return $form['field_form']['display']['field_display'];
  }
  
  /**
   *
   */
  public function bundleCallback(array $form, FormStateInterface $form_state) {
    
    return $form['choose_bundle']['bundle'];
  }
  
  public function fieldCallback(array $form, FormStateInterface $form_state) {
    
    return $form['field_form'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

#    drupal_set_message("save: " . serialize($form_state->getValues()));

    // get the input of the field
#    $field_name = $form_state->getValue('field');
    // get the input for the path
    $pathid = $form_state->getValue('path');
    
    #$bundle = $this->pathbuilder->getBundle($pathid); #$form_state->getValue('bundle');

    // load the path
    $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pathid);
    #dpm(array($path,$form_state->getValues()),'before edit');
    // get the pbpaths
    $pbpaths = $this->pathbuilder->getPbPaths();
    // set the path and the bundle - beware: one is empty!
    $pbpaths[$pathid]['fieldtype'] = $form_state->getValue('fieldtype');
    $pbpaths[$pathid]['displaywidget'] = $form_state->getValue('displaywidget');
    $pbpaths[$pathid]['formatterwidget'] = $form_state->getValue('formatterwidget');
    $pbpaths[$pathid]['field'] = $form_state->getValue('select_field');
    $pbpaths[$pathid]['bundle'] = $form_state->getValue('bundle');
    $pbpaths[$pathid]['cardinality'] = $form_state->getValue('cardinality');

    // reset that in case something has changed.
    $pbpaths[$pathid]['relativepath'] = NULL;
    
    // save it
    $this->pathbuilder->setPbPaths($pbpaths);
    $this->pathbuilder->save();
    #dpm($this->pathbuilder,'after edit');
#    drupal_set_message(serialize($pbpaths[$pathid]));
    
#    drupal_set_message(serialize($this->pathbuilder->getPbPaths()));

    $form_state->setRedirect('entity.wisski_pathbuilder.edit_form',array('wisski_pathbuilder'=>$this->pathbuilder->id()));
    
    return;    
  }


  public static function cardinalityOptions() {
    $unlimited = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
    return array(
      $unlimited => t('Unlimited'), // TODO: use the t method somehow
      '1' => '1',
      '2' => '2',
      '3' => '3',
      '4' => '4',
      '5' => '5',
    );
  }

}
