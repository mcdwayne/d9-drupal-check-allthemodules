<?php

namespace Drupal\wisski_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Ajax\AjaxResponse;

use Drupal\Core\Form\FormBase;

use Drupal\wisski_core\WisskiHelper;
use Drupal\wisski_core\Entity\WisskiBundle;

class WisskiTitlePatternForm extends EntityForm {

  private $path_options;
  
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    
    $form = parent::form($form, $form_state);
    
    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    $form['#entity'] = $bundle = $this->entity;
    //dpm($bundle,__METHOD__);
    
    $form['#title'] = $this->t('Edit title pattern for bundle %label', array('%label' => $bundle->label()));

    $options = $bundle->getPathOptions();
    //dpm($options,'Path Options');
    $form_storage = $form_state->getStorage();
    if (isset($form_storage['cached_pattern']) && !empty($form_storage['cached_pattern'])) {
      $pattern = $form_storage['cached_pattern'];
    } else {
      $pattern = $bundle->getTitlePattern();
    }
#    dpm($pattern);

    // if is not array, skip it
    if(!is_array($pattern))
      $pattern = array();

    $max_id = -1;
    if (isset($pattern['max_id'])) {
      $max_id = $pattern['max_id'];
      unset($pattern['max_id']);
    }# else {
#      $max_id = 0;
#    }
    
    $count = count($pattern)-1;

    //if user added or removed a new title element, find out the type and add a template with standard values
    $trigger = $form_state->getTriggeringElement();
    if (!is_null($trigger)) {
      $trigger = $trigger['#name'];
      if ($trigger === 'new-text-button') {
        $id = 't'.++$max_id;
        $pattern[$id] = array(
          'weight' => $count,
          'label' => '',
          'type' => 'text',
          'id' => $id,
          'parents' => '',
          'name' => 'text'.$id,
        );
      } elseif ($trigger === 'path_select_box') {
        $selection = $form_state->getValue('path_select_box');
        if (!empty($selection) && $selection !== 'empty') {
          if (in_array($selection,array_keys(WisskiBundle::defaultPathOptions()))) $label = $options[$selection];
          else {
            //dpm($options,$selection);
            list($pb_id) = explode('.',$selection);
            $label = $options[$pb_id][$selection];
          }
          $id = 'p'.++$max_id;
          $pattern[$id] = array(
            'type' => 'path',
            'name' => $selection,
            'label' => $label,
            'weight' => $count,
            'optional' => TRUE,
            'cardinality' => 1,
            'delimiter' => ', ',
            'id' => $id,
            'parents' => '',
          );
        } else {
          //this may not happen
          drupal_set_message($this->t('Please choose a path to add'),'error');
        }
      } elseif ($trigger === 'on_empty_selection') {
        $on_empty_selection = $form_state->getUserInput()['on_empty_selection'];
        //dpm($on_empty_selection,'sel');
      } else {
        $xpl = explode(':',$trigger);
        if ($xpl[0] === 'remove' && isset($xpl[1])) {
          if (isset($pattern[$xpl[1]])) {
            $max_id--;
            unset($pattern[$xpl[1]]);
          }
        }
      }
    }

    $header = array(
      $this->t('ID'),
      $this->t('Content'),
      $this->t('Options'),
      $this->t('Show #'),
      $this->t('Delimiter'),
      $this->t('Dependencies'),
      $this->t('Weight'),
      '',
      '',
      '',
    );

    $form['pattern'] = array(
      '#type' => 'table',
      //'#theme' => 'table__menu_overview',
      '#caption' => $this->t('Title Pattern'),
      '#header' => $header,
      '#empty' => $this->t('This bundle has no title pattern, yet'),
      '#prefix' => '<div id=\'wisski-title-table\'>',
      '#suffix' => '</div>',
      '#tabledrag' => array(
        // @TODO ! WATCH OUT we use the group name 'row-weight'
        // hard-coded in the buildRow function again
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'row-weight',
        ),
      ),
    );
    if (!empty($pattern)) {
      foreach ($pattern as $key => $attributes) {
        $form['pattern'][$key] = $this->renderRow($key,$attributes);
      }
    }
    
    $form['add_element'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Add a title element'),
    );
    $form['add_element']['path_select_box'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Add a path'),
      '#empty_value' => 'empty',
      '#empty_option' => ' - '.$this->t('select').' - ',
      '#ajax' => array(
        'callback' => 'Drupal\wisski_core\Form\WisskiTitlePatternForm::ajaxResponse',
        'wrapper' => 'wisski-title-table'
      ),
      //'#limit_validation_errors' => array(),
    );
    $form['add_element']['new_text'] = array(
      '#type' => 'button',
      '#value' => $this->t('Add a text block'),
      '#ajax' => array(
        'callback' => 'Drupal\wisski_core\Form\WisskiTitlePatternForm::ajaxResponse',
        'wrapper' => 'wisski-title-table'
      ),
      '#name' => 'new-text-button',
      '#limit_validation_errors' => array(),
    );

    $on_empty_options = array(
      WisskiBundle::DEFAULT_PATTERN => $this->t('Use the global default pattern see %link',array('%link'=>\Drupal\Core\Link::createFromRoute('here','wisski.config_menu')->toString())),
      WisskiBundle::DONT_SHOW => $this->t('Do not show the entity in the navigate list'),
      WisskiBundle::FALLBACK_TITLE => $this->t('Show a generic title'),
    );
    
    if (!isset($on_empty_selection)) $on_empty_selection = WisskiBundle::DEFAULT_PATTERN;
    
    $form['on_empty'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Reaction on Empty title'),
      '#prefix' => '<div id=\'wisski-fallback-title\'>',
      '#suffix' => '</div>',
      'notice' => array(
        '#type' => 'item',
        '#markup' => $this->t('What to do if an entity\'s title is resolved to an empty string')
      ),
      'on_empty_selection' => array(
        '#type' => 'radios',
        '#options' => $on_empty_options,
        '#default_value' => $bundle->onEmpty(),
        //'#value' => $on_empty_selection,
        '#ajax' => array(
          'wrapper' => 'wisski-fallback-title',
          'callback' => array($this,'onEmptyCallback'),
          //'callback' => '\Drupal\wisski_core\Form\WisskiTitlePatternForm::onEmptyCallback',
          'event' => 'change',
        ),
        '#limit_validation_errors' => array(),
        //'#name' => 'on_empty_selection',
      ),
    );
    
    $form['on_empty']['textfield'] = array(
      '#name' => 'on_empty_fallback_title',
      
    );
    
    if ($on_empty_selection == WisskiBundle::FALLBACK_TITLE) {
	    $form['on_empty']['on_empty_textfield'] = array(
        '#type' => 'textfield',
        '#default_value' => $bundle->getFallbackTitle(),
        '#title' => $this->t('Fallback Title'),    
      );
    } else {    
  	  $form['on_empty']['on_empty_textfield'] = array(
        '#type' => 'hidden',
        //'#markup' => 'empty',
      );
    }
    
    
    $form['help'] = array(
      '#type' => 'details',
      '#weight' => 10000,
      '#title' => $this->t('Help'),
      'text' => array('#markup' => $this->t(
          'Build the pattern that creates titles for entities from this bundle.<br>
          Click <em>&lt;Add a text block&gt;</em> to insert a fixed portion of text 
          or select a path from the <em>&lt;Add a path&gt;</em> drop down list to insert an entity-dependent title part. These latter path based parts will be evaluated against the entity and if that yields one ore more results those will be used for the title portion for this row.
          Having added several rows you can sort them by drag-and-drop on the crosshairs in the front section of each row.<br>
          <table>
          <th colspan=2>There are multiple options in each row to influence the pattern creation:</th>
          <tr><td>ID</td><td>the \'name\' for this title portion. Can be used in the dependencies list of other rows. The crosshair in this section can be used to drag-and-drop the row to another position in the pattern</td></tr>
          <tr><td>Content</td><td>If you added a textblock this section can be used to insert the text you want to see at this position in the title. If the row reflects a path, then that path\' name will be shown here.</td></tr>
          <tr><td>Optional</td><td>if the title part is NOT optional, being evaluated to an empty string will result in an invalid title</td></tr>
          <tr><td>Show #</td><td>select the MAXIMUM number of instances of this path to be shown, \'all\' can be chosen here and means all we can find in the knowledge base (probaly restricted by server or software settings, e.g. timeouts)</td></tr>
          <tr><td>Delimiter</td><td>Choose a string that will delimit the various instances of results in this path. Only useful if \'Show #\' does not equal 1</td></tr>
          <tr><td rowspan=2>Dependencies</td><td>Insert a comma seperated list of row IDs as shown in the row head. An ID here means that this row will only show if the row with the given ID does contain something. Adding an exclamation mark "!" right before the row ID means the current row only shows if the referenced row does NOT contain anything</td></tr>
          <tr><td><b>Example:</b> let \'t1\' have dependencies \'p0,!p2\' then the text from the \'content\' section of row \'t2\' will only show up if the path in row \'p0\' contains something for the entity in question AND path \'p2\' does not contain anything</td></tr>
          <tr><td>Remove</td><td>removes the row from the pattern</td></tr>
          </table>
          The order of the rows will be reflected in the order of text portions in the resulting title string: the higher (closer to the top) a row in the pattern, the earlier its result will show up in the created title.'
        ),
      ),
    );

    if(!empty($pattern)) {
#      dpm($pattern);
      $pattern['max_id'] = $max_id;    
      $form_storage['cached_pattern'] = $pattern;
      $form_state->setStorage($form_storage);
    }
//dpm(drupal_render($form['path_select_box']));
    return $form;
  }
  
  /**
   *
   */
  private function renderRow($key,array $attributes) {
    //dpm($attributes,__METHOD__.' '.$key);  
    $rendered = array();
  
    $rendered['#attributes']['class'][] = 'draggable';
    
    $rendered['id'] = array(
      '#type' => 'item',
      '#value' => $attributes['id'],
      '#markup' => $attributes['id'],
      '#attributes' => array('class' => array('row-id')),
    );
    
    if ($attributes['type'] === 'path') {
      $rendered['label'] = array(
        '#type' => 'item',
        '#markup' => $attributes['label'],
        '#value' => $attributes['label'],
      );
      $rendered['optional'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('optional'),
        '#title_display' => 'after',
        '#default_value' => $attributes['optional'],
      );
      static $cardinalities = array(1=>1,2=>2,3=>3,-1=>'all');
      $rendered['cardinality'] = array(
        '#type' => 'select',
        '#title' => $this->t('cardinality'),
        '#title_display' => 'invisible',
        '#options' => $cardinalities,
        '#default_value' => $attributes['cardinality'],
      );
      $rendered['delimiter'] = array(
        '#type' => 'textfield',
        '#size' => 4,
        '#title' => $this->t('delimiter'),
        '#title_display' => 'invisible',
        '#default_value' => isset($attributes['delimiter'])? $attributes['delimiter']: ', ',
      );
    }
    if ($attributes['type'] === 'text') {
      //put a text field here, so that fixed strings can be added to the title
      $rendered['label'] = array(
        '#type' => 'textfield',
        '#default_value' => $attributes['label'],
        '#title' => $this->t('Text'),
        '#title_display' => 'invisible',
      );
      //make sure we have all cells filled
      foreach(array('optional','cardinality','delimiter') as $placeholder) {
        $rendered[$placeholder] = array('#type' => 'hidden');
      }
    }
    
    $parent_string = '';
    
    if (!empty($attributes['parents'])) {
      foreach ($attributes['parents'] as $row_id => $positive) {
        if (!empty($parent_string)) $parent_string .= ', ';
        if (!$positive) $parent_string .= '!';
        $parent_string .= $row_id;
      }
    }
    
    $rendered['parents'] = array(
      '#type' => 'textfield',
      '#default_value' => $parent_string,
      '#size' => 8,
    );
        
    $rendered['weight'] = array(
      '#type' => 'weight',
      '#delta' => 51,
      '#attributes' => array('class' => array('row-weight')),
      '#default_value' => 0,
    );
    
    $rendered['#weight'] = $attributes['weight'];
    
    $rendered['type'] = array(
      '#type' => 'hidden',
      '#value' => $attributes['type'],
      //'#markup' => $attributes['type'],
    );
    
    $rendered['name'] = array(
      '#type' => 'hidden',
      '#value' => $attributes['name'],
    );
    
    $rendered['remove_op'] = array(
      '#type' => 'button',
      '#name' => 'remove:'.$key,
      '#value' => $this->t('remove'),
      '#ajax' => array(
        'callback' => 'Drupal\wisski_core\Form\WisskiTitlePatternForm::ajaxResponse',
        'wrapper' => 'wisski-title-table'
      ),
      '#limit_validation_errors' => array(),
    );
//    dpm(array('attributes'=>$attributes,'result'=>$rendered),__METHOD__);    
    return $rendered;
  }

  /**
   * AJAX response for Field Selection
   */
  public static function ajaxResponse(array &$form, FormStateInterface $form_state) {

	  //dpm($form_state->getStorage()['cached_pattern'],'Cached Pattern');
  	return $form['pattern'];
  }
  
  public function onEmptyCallback(array &$form, FormStateInterface $form_state) {
    
    \Drupal::logger('Wisski AJAX '.__FUNCTION__)->debug($form_state->getTriggeringElement()['#name']);
  	return $form['on_empty'];
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save pattern'),
      '#submit' => array("::submitForm","::save"),
    );
    $actions['delete'] = array(
      '#value' => t('Delete pattern'),
      '#type' => 'submit',
      '#limit_validation_errors' => array(),
      '#submit' => array("::deletePattern"),
    );
    return $actions;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    $pattern = $form_state->getValue('pattern');
    $max_id = 0;

    $errors = array();
    if (isset($pattern['max_id'])) unset($pattern['max_id']);

    $children = array();
    
    foreach ($pattern as $row_id => &$attributes) {
      if (!isset($attributes['type'])) 
        $errors[] = array($row_id,'not set','type');
      elseif ($attributes['type'] === 'path') {
        if (empty($attributes['name'])) 
          $errors[] = array($row_id,'empty','name');
        elseif (!in_array($attributes['name'],array_keys(WisskiBundle::defaultPathOptions())) && !preg_match('/^([a-z0-9_]+\.[a-z0-9_]+)$/',$attributes['name'])) 
          $errors[] = array($row_id,'invalid','name');
        if (!in_array($attributes['cardinality'],array(-1,1,2,3))) 
          $errors[] = array($row_id.'][cardinality','invalid');
        if (empty($attributes['delimiter']))
          $errors[] = array($row_id.'][delimiter','empty');
      } elseif ($attributes['type'] === 'text') {
        if (empty($attributes['label'])) 
          $errors[] = array($row_id.'][label','empty');
      } else $errors[] = array($row_id,'invalid','type');
      
      if (isset($attributes['parents']) && $attributes['parents'] !== '') {
        $parents = explode(',',$attributes['parents']);
        unset($attributes['parents']);
        foreach ($parents as $parent) {
          $t_parent = trim($parent);
          $positive = strpos($t_parent,'!') !== 0;
          if (!$positive) {
            $t_parent = ltrim($t_parent,'!');
          }
          if (array_key_exists($t_parent,$pattern)) {
            $children[$t_parent][$row_id] = $positive;
            $pattern[$row_id]['parents'][$t_parent] = $positive;
          }
          else $errors[] = array($row_id.'][parents','invalid');
        }
      }
      $num_id = intval(substr($attributes['id'],1));
      if ($num_id > $max_id) $max_id = $num_id;
    }
    $pattern['max_id'] = $max_id;

    $cycle = array();
    if ($this->containsCycle($children,$cycle)) {
      foreach ($cycle as $elem) {
        $errors[] = array($elem.'][parents','cyclic');
      }
    } else {
      foreach ($children as $row_id => $row_children) {
        $pattern[$row_id]['children'] = $row_children;
      }
    }

    if (empty($errors)) {
      $form_state->setValue('pattern',$pattern);
    } else {
      foreach ($errors as $error_array) {
        //dpm($error_array,'Errors');
        $element = $error_array[0];
        $error_type = isset($error_array[1]) ? $error_array[1] : '';
        $category = isset($error_array[2]) ? $error_array[2] : '';
        $t_error_type = $this->tError($error_type);
        $form_state->setErrorByName('pattern]['.$element,$t_error_type.' '.$category);
      }
    }
  }

  protected function tError($error_type) {
    
    switch ($error_type) {
      case 'invalid': return $this->t('Invalid');
      case 'not set': return $this->t('Not Set');
      case 'empty': return $this->t('Empty');
      case 'cyclic': return $this->t('Cyclic Dependency');
      default: return $this->t('Wrong input');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    
    /** @var  \Drupal\wisski_core\WisskiBundleInterface $bundle */
    $bundle = $this->entity;
    
    //dpm(array($bundle,$form_state->getValues()),__METHOD__);
    
    $pattern = $form_state->getValue('pattern');
    
    $bundle->setTitlePattern($pattern);
    
    $on_empty = $form_state->getValue('on_empty_selection');
    $bundle->setOnEmpty($on_empty);
    
    $fallback = $form_state->getValue('on_empty_textfield');
    $bundle->setFallbackTitle($fallback);
    
    $bundle->save();
    
    drupal_set_message(t('The title pattern for bundle %name has been updated.', array('%name' => $bundle->label())));

    $form_state->setRedirectUrl($bundle->urlInfo('edit-form'));
  }
  
  public function deletePattern(array $form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->entity->urlInfo('delete-title-form'));
  }  
  
  public function containsCycle($array,&$cycle) {
    $out = self::cycle_detection($array);
    if ($out === FALSE) return FALSE;
    $cycle = $out;
    return TRUE;
  }
  
  /**
   * takes associative array having node names as keys and arrays of node names connect to them as values
   * returns FALSE if the represented graph contains no cycle, or an array containing the cycle
   */
  public static function cycle_detection($array) {
    
    $checked = array();
    foreach ($array as $key => $partners) {
      if (!in_array($key,$checked)) {
        $cycle = self::recursive_cycle_detection($array,$key,array(),$checked);
        if ($cycle !== FALSE) return self::extract_cycle($cycle);
      }
    }
    return FALSE;
  }
  
  private static function extract_cycle($array) {
    $key = end($array);
    reset($array);
    while($elem = array_shift($array)) {
      if ($elem === $key) {
        break;
      }
    }
    return $array;
  }
  
  private static function recursive_cycle_detection($array,$key,$history,&$checked) {
    
    if (empty($array[$key])) return FALSE;
    $history[] = $key;
    foreach($array[$key] as $child) {
      if (in_array($child,$history)) {
        $history[] = $child;
        return $history;
      }
      if (in_array($child,$checked)) return FALSE;
      $result = self::recursive_cycle_detection($array,$child,$history,$checked);
      if ($result !== FALSE) return $result;
    }
    $checked[] = $key;
    return FALSE;
  }
  
}
