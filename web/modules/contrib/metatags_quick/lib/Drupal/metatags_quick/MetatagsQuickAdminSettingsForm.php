<?php
/**
 * @file
 * Contains Drupal\metatags_quick\MetatagsQuickAdminController.
 * 
 * Originally grabbed from book.module's BookSettingsForm
 * 
 * field_info_bundles() removed in 8.x
 */

namespace Drupal\metatags_quick;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Annotation\FieldType;
use Drupal\field\Plugin\Core\Entity\FieldInstance;
use Drupal\Core\Entity\Field\Field;
use Drupal\Core\Form\ConfigFormBase;

/**
 * metatags_quick settings form
 * parts borrowed from field_ui/FieldOverview.php
 */
class MetatagsQuickAdminSettingsForm extends ConfigFormBase {
  protected $entityManager;
  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'metatags_quick_admin_settings';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    // $config = \Drupal::config('metatags_quick');

    // @todo: change variable-based settings to config()
    $current_settings = variable_get('metatags_quick_settings', _metatags_quick_settings_default());
    $module_path = drupal_get_path('module', 'metatags_quick');
    $fields = field_info_fields();
    $metatags_found = FALSE;

    include_once $module_path . '/known_tags.inc';
    $known_tags = _metatags_quick_known_fields();

    $form['global'] = array(
      '#type' => 'fieldset',
      '#title' => t('Global settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    foreach ($fields as $key => $field) {
      if ($field->module != 'metatags_quick') {
        continue;
      }
      $metatags_found = TRUE;
    }
    if (!$metatags_found) {
      $form['global']['basic_init'] = array(
        '#markup' => t('No meta tags found in your installation') . '<br/>',
      );
    }

    $form['global']['use_path_based'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use path-based meta tags'),
      '#default_value' => variable_get('metatags_quick_use_path_based', 1),
      '#return_value' => 1,
    );
    $form['global']['remove_tab'] = array(
      '#type' => 'checkbox', 
      '#title' => t('Hide Path-based Metatags tab'), 
      '#default_value' => variable_get('metatags_quick_remove_tab', 0),
    );
    $form['global']['default_field_length'] = array(
      '#type' => 'textfield',
      '#title' => t('Default maximum length'),
      '#description' => t('Default maximum length for the meta tag fields'),
      '#default_value' => variable_get('metatags_quick_default_field_length', 255),
    );
    $form['global']['show_admin_hint'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show entities/bundles hint in admin'),
      '#default_value' => variable_get('metatags_quick_show_admin_hint', 1),
    );

    $form['standard_tags'] = array(
      '#type' => 'fieldset',
      '#title' => t('Create and attach'),
      '#description' => t('The following meta tags are known to the module and can be created automatically. However, you are not limited to this list and can define tags of your own using the Fields UI.'),
      '#collapsible' => TRUE,
      '#attached' => array(
        'js' => array($module_path . '/js/admin.js'),
        'css' => array($module_path . '/css/admin.css'),
      ),
    );
    if (variable_get('metatags_quick_show_admin_hint', 1)) {
      $form['standard_tags']['hint'] = array(
        '#prefix' => '<div class="messages messages--status">',
        '#markup' => t('<strong>Hint</strong>: press on entity type name to edit individual bundles settings (you can hide this hint in global settings).'),
        '#suffix' => '</div>',
      );
    }
  
    $field_instances = field_info_instances();

    // Build the sortable table header.
    $header = array(
      'title' => array('data' => t('Bundle/entity')),
    );
    foreach ($known_tags as $name => $tag) {
      $header[$name] = $tag['title'];
    }
    //$header['_select_all'] = t('Select all');
  
  //foreach ( field_info_bundles() as $entity_type => $bundles) {
  foreach (entity_get_bundles() as $entity_type => $bundles) {
    $entity_info = entity_get_info($entity_type);
    if (!$entity_info['fieldable']) {
      continue;
    }
    $options[$entity_type]['data'] = array(
      'title' => array(
        'class' => array('entity_type_collapsible', 'entity_type_collapsed', "entity_name_$entity_type"),
        'data' => check_plain($entity_info['label']),
      )
    );
    foreach ($known_tags as $name => $tag) {
      $bundle_workable[$name] = FALSE;
      $options[$entity_type]['data'][$name] = array(
        'data' => array(
          '#type' => 'checkbox',
          '#attributes' => array('class' => array('cb_bundle_parent', "cb_bundle_name_{$entity_type}_{$name}")),      
          '#return_value' => 1,
          '#checked' => FALSE,
        ),
      );
    }

    // How do we mark that specific meta is already attached to bundle
    $checked_markup = array(
      '#markup' => theme('image', 
        array(
          'uri' => 'core/misc/watchdog-ok.png',
          'width' => 18,
          'height' => 18,
          'alt' => 'ok',
          'title' => 'ok',
        )),
    );
        
    foreach ($bundles as $key => $bundle) {
      // Which meta tags are set for this bundle?
      $meta_set = array();
      foreach ($field_instances as $entity_type => $list) {
        if (!array_key_exists($key, $list)) {
          continue;
        }
        foreach ($list[$key] as $bundle_instance) {
          $field_info = \Drupal\field\Field::fieldInfo()->getFieldById($bundle_instance->id);
          if ($field_info->module == 'metatags_quick') {
            $meta_set[$bundle_instance->settings['meta_name']] = 1;
          }
        }

        $options[$entity_type . '_' . $key] = array(
          'class' => array('entity_type_children', "entity_child_$entity_type"),
          'style' => 'display: none',
          'data' => array(
            'title' => array(
              'class' => array('entity_type_child_title'),
              'data' => $bundle['label'],
            ),
          ),
        );
        foreach ($known_tags as $name => $tag) {
          $tag_name = isset($tag['meta_name']) ? $tag['meta_name'] : $name;
          if (empty($meta_set[$tag_name])) {
            // Mark parent bundle as workable - display checkbox.
            $bundle_workable[$name] = TRUE;
            $options[$entity_type . '_' . $key]['data'][$name] = array(
              'data' => array(
                '#name' => $entity_type . '[' . $key . '][' . $name . ']',
                '#type' => 'checkbox',
                '#attributes' => array('class' => array('cb_bundle_child', "cb_child_{$entity_type}_{$name}")),
                '#return_value' => 1,
                '#checked' => FALSE,
              )
            );
          }
          else {
            $options[$entity_type . '_' . $key]['data'][$name]['data'] = $checked_markup; 
          }
        }
      }
    }

    // Now check if we have completely set bundles
    foreach ($known_tags as $name => $tag) {
      if (!$bundle_workable[$name]) {
        $options[$entity_type]['data'][$name]['data'] = $checked_markup; 
      }
    }
  }
  
    $form['standard_tags']['existing'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $options,
      '#empty' => t('No content available.'),
    );
  
    $form['standard_tags']['basic_init_op'] = array(
      '#type' => 'submit',
      '#value' => t('Attach'),
    );
    $form['op'] = array(
      '#value' => t('Submit'),
      '#type' => 'submit',
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    if (empty($this->entityManager)) {
      $this->entityManager = \Drupal::entityManager();
    }
    variable_set('metatags_quick_use_path_based', $form_state['values']['use_path_based']);
    variable_set('metatags_quick_remove_tab', $form_state['values']['remove_tab']);
    variable_set('metatags_quick_default_field_length', $form_state['values']['default_field_length']);
    variable_set('metatags_quick_show_admin_hint', $form_state['values']['show_admin_hint']);
    if ($form_state['triggering_element']['#value'] == t('Attach')) {
      $this->fieldsCreateAttach($form_state['input']);
    }
    else {
    }
    drupal_set_message(t('Meta tags (quick) settings saved'), 'status');
  }
  
  protected function fieldsCreateAttach($input) {
    foreach (entity_get_bundles() as $entity_type => $bundles) {
      $entity_info = entity_get_info($entity_type);
      if (!$entity_info['fieldable']) {
        continue;
      }
    
      foreach ($bundles as $key => $bundle) {
        if (isset($input[$entity_type][$key])) {
          foreach ($input[$entity_type][$key] as $meta_name => $meta_value) {
            $this->fieldAttach($entity_type, $key, $meta_name);
          }
        }
      }
    }
  }
  
  protected function fieldAttach($entity_type, $bundle, $meta_name) {
    // @todo: convert static vars to class members?
    static $meta_fields = FALSE;
    static $field_id = array();
    static $known_tags = FALSE;
    
    // Get metatags_quick fields info
    if (!$meta_fields) {
      include_once drupal_get_path('module', 'metatags_quick') . '/known_tags.inc';
      $known_tags = _metatags_quick_known_fields();
    
      foreach(field_info_fields() as $name => $field_info) {
        if ($field_info['module'] == 'metatags_quick') {
          $meta_fields[$field_info->id()] = $field_info;
          $field_id[$field_info->id()] = $field_info['id'];
        }
      }
    }
    
    // Ignore unknown tags.
    if (!isset($known_tags[$meta_name])) {
      return;
    }
    // Check if meta field exists, create if necessary.
    $meta_name_real = empty($known_tags[$meta_name]['meta_name']) ? $meta_name : $known_tags[$meta_name]['meta_name'];
    if (empty($field_id[$meta_name_real])) {
      $field = array(
        'name' => "meta_$meta_name",
        'type' => 'metatags_quick',
        'entity_type' => $entity_type,
        // 'translatable' => $values['translatable'],
      );
      
      // Create the field.
      $field = $this->entityManager->getStorageController('field_entity')->create($field);
      $field->save();
      $field_id[$meta_name] = $field->id();
      $meta_fields[$meta_name_real] = $field;
    }
    else {
      $field = $meta_fields[$meta_name_real];
    }
    
    // Do nothing if instance already exists.
    if (isset($field['bundles'][$entity_type])
        && in_array($bundle, $field['bundles'][$entity_type])) {
      return;
    }
    
    // Now create field instance attached to requested bundle
    $instance = array(
      'field_name' => $field['field_name'],
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => '(Meta)' . $known_tags[$meta_name]['title'],
      'settings' => array('meta_name' => $meta_name_real),
    );
    $new_instance = $this->entityManager->getStorageController('field_instance')->create($instance);
    $new_instance->save();
/*     $instance = array(
      'field_name' => $field['field_name'],
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => '(Meta)' . $known_tags[$meta_name]['title'],
      'formatter' => 'metatags_quick_default',
      'widget' => array(
        'type' => $known_tags[$meta_name]['widget'],
        'weight' => 0,
      ),
    );
 */
     if (isset($known_tags[$meta_name]['options'])) {
      $instance['settings']['options'] = $known_tags[$meta_name]['options'];
    }
  }
}
