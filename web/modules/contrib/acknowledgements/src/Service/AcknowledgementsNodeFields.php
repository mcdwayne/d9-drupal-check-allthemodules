<?php

namespace Drupal\sign_for_acknowledgement\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Service to interact with the node fields.
 */
class AcknowledgementsNodeFields {

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   *
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('sign_for_acknowledgement.settings');
  }
  /*
  * node type supported
  *
  */
  public function appliesToBundle($bundle) {
//	  echo "<pre>";print_r($this->config->get('node_types'));exit;
    $apply_to = $this->config->get('node_types');
    if (!isset($apply_to[$bundle])) {
	  return false;
    }
    return $apply_to[$bundle];
  }
  /*
  * node type supported
  *
  */
  public function appliesToUser($node, $user = NULL) {
    if ($user == NULL) {
      $user = \Drupal::CurrentUser();
    }
    $single_user_enabled = FALSE;
    // Check if current user is ok.
	$my_users = $node->get('enable_users')->getValue();
    if (isset($my_users[0]['value'])) {
      $single_user_enabled = TRUE;
      foreach($my_users as $my_user) {
        if ($my_user['value'] == $user->id()) {
          return TRUE;
        }
      }
    }
	// check if roles are ok
	$my_roles = $node->get('enable_roles')->getValue();

	if ((isset($my_roles) == FALSE || count($my_roles) == 0) && !$single_user_enabled) {
      if (\Drupal::service('path.matcher')->isFrontPage() == FALSE && $this->config->get('show_nobody')) {
        drupal_set_message(t('Nobody can sign this content for acknowledgement.<br />If this is unwanted, please modify the content and set up users roles.'), 'warning', TRUE);
      }
	  return FALSE;
	}
    if (isset($my_roles) && (count($my_roles))) {
      foreach($my_roles as $array) {
        foreach($array as $value) {
          if (!(isset($value))) {
            continue;
          }
          $rolename = $value; // == t('authenticated') ? 'authenticated' : $value;
          if ($rolename == 'authenticated' && $user->isAuthenticated()) {
            return TRUE;
          }
          if (in_array($rolename, $user->getRoles(TRUE))) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  function getViewDisplay($entity_type, $bundle, $view_mode = 'default') {
  
  // Try loading the display from configuration.
  $display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load($entity_type . '.' . $bundle . '.' . $view_mode);
  
  // If not found, create a fresh display object. We do not preemptively create
  // new entity_view_display configuration entries for each existing entity type
  // and bundle whenever a new view mode becomes available. Instead,
  // configuration entries are only created when a display object is explicitly
  // configured and saved.
  if (!$display) {
    $display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->create([
      'targetEntityType' => $entity_type,
      'bundle' => $bundle,
      'mode' => $view_mode,
      'status' => TRUE,
    ]);
  }
  return $display;
}
  function getFormDisplay($entity_type, $bundle, $form_mode = 'default') {
  
  // Try loading the display from configuration.
  $display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load($entity_type . '.' . $bundle . '.' . $form_mode);
  
  // If not found, create a fresh display object. We do not preemptively create
  // new entity_form_display configuration entries for each existing entity type
  // and bundle whenever a new view mode becomes available. Instead,
  // configuration entries are only created when a display object is explicitly
  // configured and saved.
  if (!$display) {
    $display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->create([
      'targetEntityType' => $entity_type,
      'bundle' => $bundle,
      'mode' => $form_mode,
      'status' => TRUE,
    ]);
  }
  return $display;
}
  /**
  * Reset custom field.
  * @param string $name : name of the field
  * @param string $type : type of the field
  * @param array $settings : the field settings
  * @param string $label : label of the field
  * @param string $form_type : widget type
  * @param boolean $multiple : whether field has multiple values
  * @param string $view_type : the field view type
  * @param array $settings2 : the field instance settings
  *
  */
  public function resetField($name,$type,$settings,$label,$form_type,$multiple,$view_type=NULL,$settings2=array()) {
  // get display service
  //$disp = \Drupal::service('entity_display.repository');
  // array for deletable instances
  $defs = array();
  // Check if our field is not already created.
  if (!\Drupal::config('field.storage.node.'.$name)->get()) {
	$field_storage = \Drupal\field\Entity\FieldStorageConfig::create(array(
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => $type,
	  'cardinality' => $multiple? -1 : 1,
	  'settings' => $settings,
    ));
	$field_storage->save();
    }
  $node_types = node_type_get_types();
  foreach ($node_types as $node_type) {
    $bundle = $node_type->get('type');
	// prepare instances for deletion
    if (!$this->appliesToBundle($bundle)) {
	  $bundle_fields = \Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions('node', $bundle);
      $field_definition = isset($bundle_fields[$name])? $bundle_fields[$name] : NULL;
	  if ($field_definition) {
		$defs[] = $field_definition;
	    }
      continue;
      }
    // Create the instances on the bundle.
    if (!\Drupal::config('field.field.node.'.$bundle.'.'.$name)->get()) {
      $instance = array(
        'field_name' => $name, 
        'entity_type' => 'node', 
        'label' => $label, 
        'bundle' => $bundle, 
      );
	  \Drupal\field\Entity\FieldConfig::create($instance)->save();
	  $this->getFormDisplay('node', $bundle)
        ->setComponent($name, array(
        'type' => $form_type,
		'multiple' => $multiple,
        ))
        ->save();
	  if ($view_type) $this->getViewDisplay('node', $bundle)
        ->setComponent($name, array(
        'type' => $view_type,
		'settings' => $settings2,
        ))
        ->save();
      }
    }
  foreach ($defs as $def) {
	$def->delete();  
    }
  }
  public function resetFields () {
    $this->resetField('expire_date',
	                  'datetime',
					  ['datetime_type' => 'datetime'],
					  t('Sign within this date'),
					  'text_textfield',
					  FALSE
					  /*
					  ,
					  'datetime_custom',
					  ['date_format' => 'd F Y']
					  */
					  );
    $this->resetField('alternate_form',
	                  'boolean',
					  [],
					  t('Use acknowledgement alternate form'),
					  'boolean_checkbox',
					  FALSE
                      );
    $this->resetField('alternate_form_multiselect',
	                  'boolean',
					  [],
					  t('Use acknowledgement alternate multiselect form'),
					  'boolean_checkbox',
					  FALSE
                      );
    $this->resetField('alternate_form_text',
	                  'string_long',
					  [],
					  t('Selection buttons labels'),
					  'text_textarea',
					  FALSE
                      );
    $this->resetField('annotation_field',
	                  'boolean',
					  [],
					  t('Use annotation field'),
					  'boolean_checkbox',
					  FALSE
                      );
    $this->resetField('annotation_field_required',
	                  'boolean',
					  [],
					  t('Require annotation field'),
					  'boolean_checkbox',
					  FALSE
                      );
    $this->resetField('enable_roles',
	                  'list_string',
					  ['allowed_values_function' => 'sign_for_acknowledgement_get_roles'],
					  t('Select the roles that are required to sign'),
					  'options_buttons',
					  TRUE
                      );
    $this->resetField('enable_users',
	                  'list_string',
					  ['allowed_values_function' => 'sign_for_acknowledgement_get_users'],
					  t('List of all users enabled to sign for acknowledgement.'),
					  'options_select',
					  TRUE
                      );
    $this->resetField('email_roles',
	                  'boolean',
					  [],
					  t('Send e-mail to notify users of selected roles'),
					  'boolean_checkbox',
					  FALSE
                      );
    $this->resetField('email_users',
	                  'boolean',
					  [],
					  t('Send e-mail to notify selected users'),
					  'boolean_checkbox',
					  FALSE
                      );
    $this->resetField('enable_roles_nosign',
	                  'list_string',
					  ['allowed_values_function' => 'sign_for_acknowledgement_get_roles'],
					  t('Select roles to which send notification email (no acknowledgement)'),
					  'options_buttons',
					  TRUE
                      );
	
  }

 
/**
 * Get expiration custom field for current node.
 *
 * @param boolean $timestamp (if TRUE return timestamp)
 * @param int $nodeid (if 0 get the value from url arguments)
 * @param object $node
 * @return timestamp OR string (expiration date)
 * TODO: remove $nodeid and useless argument handling
 */
  public function expirationDate($timestamp = FALSE, $nodeid = 0, $node = NULL)
    {
    $current_url = \Drupal\Core\Url::fromRoute('<current>');
    $path = $current_url->getInternalPath();
    $arg = explode('/', $path);
//	echo "<pre>";print_r($param);exit;
    if ($node != NULL || $nodeid != 0 || ($arg[0] == 'node' && is_numeric($arg[1]))) {
      if ($nodeid == 0) {
        $nodeid = $arg[1];
      }
      if ($node == NULL) {
        $node = \Drupal\node\Entity\Node::load($nodeid);
      }
      if ($node == NULL) {
        return NULL;
      }
      if (!isset($node->expire_date->value)) {
        return NULL;
      }
      $rawdate = $node->expire_date->value;
      $timezone = ' UTC';//$date[0]['timezone_db'];
      if ($rawdate == 'b') {
        return NULL;
      }
      if ($timestamp) {
        return strtotime($rawdate . $timezone);
      }
      $formatted = \Drupal::service('date.formatter')->format(
        strtotime($rawdate . $timezone), 'medium'
      );
	  return $formatted;
    }
    else {
      return NULL;
    }
  }
}

