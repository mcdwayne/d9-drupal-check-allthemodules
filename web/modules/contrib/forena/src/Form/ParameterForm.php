<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 2/13/16
 * Time: 10:10 AM
 */

namespace Drupal\forena\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\forena\DataManager;
use Drupal\forena\FrxAPI;

class ParameterForm extends FormBase {
  use FrxAPI;

  public function getFormID() {
    return 'forena_parameter_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $parameters = NULL) {
    $parms = $_GET;
    //$storage = $form_state->getStorage();
    // Set menu parms
    $menu_parms = $this->getDataContext('menu-parms');
    if ($menu_parms) $storage['menu-parms'] = $menu_parms;
    // Set Descriptors
    $values = $form_state->getValues(); 
    $collapse = isset($attributes['collapsed']) ? $attributes['collapsed'] : FALSE;
    if ($values) {
      $collapse=FALSE;
      $parms = array_merge($parms, $values['params']);
      // In the case of ahah, we need to restore menu parameters from the form state.
      if (isset($storage['menu-parms'])) {
        $menu_parms = $storage['menu-parms'];
        $parms = array_merge($menu_parms,$parms);

      }
      $this->app()->alter('forena_parameters', $report_name,  $parms);
      $this->pushData($parms, 'parm');
    }
    $template = @$attributes['template'];
    $collapsible = isset($attributes['collapsible']) ? $attributes['collapsible'] : TRUE;
    $title = isset($attributes['title']) ? $attributes['title'] : $this->t('Parameters');
    $submit_value = isset($attributes['submit']) ? $attributes['submit'] : $this->t('Submit');

    unset($parms['q']);
    $form = array();


    if ($parameters) {

      $this->app()->alter('forena_parameters', $report_name,  $parms);
      if ($parameters) {

        $form['parms'] = array(
          '#tree' => TRUE,
          '#title' => $title,
          '#type' => 'details',
          '#collapsible' => $collapsible,
          '#collapsed' => $collapse,
          '#prefix' => '<div id="parameters-wrapper">',
          '#suffix' => '</div>',
        );

        foreach ($parameters as $node) {
          $add_null = FALSE;
          $list=array();
          $disabled = FALSE;
          $label = @(string)$node['label'];
          $id = @(string)$node['id'];
          $data_source = @(string)$node['data_source'];
          $data_field = @(string)$node['data_field'];
          $class = @(string)$node['class'];
          $type = @(string)$node['type'];
          $option_str = @(string)$node['options'];
          $options = array();
          if ($option_str) {
            parse_str($option_str, $options);
          }

          if (isset($parms[$id])) {
            $value = $parms[$id];
            $multi_value=(array)$parms[$id];
          }
          else {
            $value = @(string)$node['default'];
            if (strpos($value, '|')!==FALSE) {
              $multi_value = explode('|', $value);
            }
            elseif ($value) {
              $multi_value = (array) $value;
            }
            else {
              $multi_value = array();
            }
          }
          $desc =  @(string)$node['desc'];
          $label_field = @(string)$node['label_field'];

          @(strcmp((string)$node['require'], "1") == 0) ? $required = TRUE : $required = FALSE;
          $ctl_attrs = array();

          //returned values filtered against data_field attr.
          if ($data_source) {
            $list = DataManager::instance()->dataBlockParams($data_source, $data_field, $label_field);
            if (!$required && $add_null) $list = array('' => '') + $list;
          }

          //Determine the form element type to be displayed
          //If select or radios is chosen then begin a $list array for display values.
          $multiselect = FALSE;
          $ajax = FALSE;
          $add_null = FALSE;
          switch ($type) {
            case 'multiselect':
              $type = 'select';
              $multiselect = TRUE;
              $value = $multi_value;
              break;
            case 'multiselectajax':
              $type = 'select';
              $multiselect = TRUE;
              $value = $multi_value;
              $ajax = TRUE;
              break;
            case 'checkboxes':
              $value = $multi_value;
              break;
            case 'selectajax':
              $ajax = TRUE;
              $type = 'select';
              $add_null = TRUE;
              break;
            case 'select':
              $add_null = TRUE;
              break;
            case 'date_text':
            case 'date_select':
            case 'date_popup':
              $options['date_format'] = @$options['date_format'] ? $options['date_format'] : 'Y-m-d';
              $ctl_attrs['#date_format'] = $options['date_format'];
              if ($value){
                $datetime = @strtotime($value);
                if ($datetime) {
                  $value = date('Y-m-d h:i', $datetime);
                }

              }
              $ctl_attrs['#forena_date_format'] = @$options['date_parm_format'] ? $options['date_parm_format'] : 'Y-m-d';

              if (@$options['date_year_range']) {
                $ctl_attrs['#date_year_range'] = $options['date_year_range'];
              }
              if (@$options['date_label_position']) {
                $ctl_attrs['#date_label_position'] = $options['date_label_position'];
              }

              $list=array();
              break;
            case 'checkbox':
              if (@$option_str['return_value']) {
                $ctl_attrs['#return_value'] = $options['return_value'];
              }
              $list=array();
              break;
            case 'radios':
              break;
            case 'hidden':
              $list=array();
              break;
            default:
              $type = 'textfield';
              $list = array();
          }

          if (isset($menu_parms[$id]) && $type!='hidden') {
            $disabled = TRUE;
          }

          //If a data_source attr was found then create an array of
          $form['parms'][$id] = array(
            '#type' => $type,
            '#title' => ($label) ? $this->t($label) : $this->t($id),
            '#default_value' => $value,
            '#disabled' => $disabled,
            '#required' => $required,
            '#description' => $this->t($desc),
          );

          $form['parms'][$id] = array_merge($form['parms'][$id], $ctl_attrs);

          if ($type == 'item') {
            $form['parms'][$id]['#markup'] = $value;

          }

          if ($type == 'hidden') {
            $form['parms'][$id]['#value'] = $value;
          }

          // Add class to parmeter form.
          if ($class) {
            $form['parms'][$id]['#attributes'] = array(
              'class' => @explode(' ', $class),
            );
          }

          //if $list is not empty then push options
          //onto the array. options will cause an error for
          //textfield elements.
          if ($list || $type == 'select' || $type =='radios') {
            if ($add_null) {
              $prompt = @$options['prompt'];
              if (!$prompt) $prompt = $required ? '-' . $this->t('select') .  '-' : '-' .t('none') . '-';
              $form['parms'][$id]['#empty_option'] = $prompt ;
            }
            $form['parms'][$id]['#options'] = $list;
            $form['parms'][$id]['#multiple'] = $multiselect;
          }

          if ($ajax) {
            $form['parms'][$id]['#ajax'] = array('callback' => 'forena_parameters_callback',
              'wrapper' => 'parameters-wrapper');
          }

        }

        if ($template) {
          $form['parms']['#forena-template'] = $template;
          $form['parms']['#theme'] = 'forena_fieldset_template';
          _forena_set_inline_theme($form['parms']);
        }

        $form['parms']['submit'] = array(
          '#type' => 'submit',
          '#value' => $submit_value,
        );

      }
      //$form_state->setStorage($storage);
      return $form;
    }
    else {
      return NULL; 
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    unset($values['parms']['submit']);

    if (isset($values['parms'])) foreach ($values['parms'] as $key => $value) {
      $ctl = $form['parms'][$key];
      switch($ctl['#type']) {
        case 'date_popup':
        case 'date_select':
        case 'date_text':
          $datetime = @strtotime($value);
          if ($datetime) {
            $value = $values['parms'][$key] = date($ctl['#forena_date_format'], $datetime);
          }
          break;

      }

      if (is_array($value)) {
        $values['parms'][$key] = array();
        foreach ($value as $k => $val) {
          if ($val) {
            $values['parms'][$key][] = $val;
          }
        }
      }
      else {
        if (strpos($value, '|')!==FALSE) {
          $values['parms'][$key] = explode('|', $value);
        }
        elseif ($value==='' || $value===NULL) {
          unset($values['parms'][$key]);
        }
      }
    }

    $path = \Drupal::service('path.current')->getPath();
    $url = Url::fromUserInput($path, ['query' => $values['parms'] ]);
    $form_state->setRedirectUrl($url);
  }

}