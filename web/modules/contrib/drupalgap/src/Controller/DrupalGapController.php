<?php

/**
 * @file
 * Contains \Drupal\drupalgap\Controller\DrupalGapController.
 */

namespace Drupal\drupalgap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;


/**
 * Returns responses for DrupalGap module routes.
 */
class DrupalGapController extends ControllerBase {

  /**
   * Return the DrupalGap configuration page.
   *
   * @return string
   *   A render array containing our DrupalGap configuration page content.
   */
  public function drupalgapConfig() {
    $output = array();

    $output['drupalgap'] = array(
      '#attached' => array(
        'library' => array('drupalgap/drupalgap.config')
      ),
      // Render the Status connection and some help docs.
     '#markup' => \Drupal::service('renderer')->render($this->drupalStatus()),
    );

    return $output;
  }

  /**
   * Checks the current status and add help link.
   *
   * @return array
   *   An array to be rendered with the id for the div where we will check the status and an information link.
   */
  public function drupalStatus() {
    // Set div id for system connect status message box.
    $div_id = 'drupalgap-system-connect-status-message';

    // Build more info text and help link string.
    $msg = t('Please refer to the <a href="@help_page">DrupalGap Module Help Page</a> for more information.', array('@help_page' => 'admin/help/drupalgap'));

    // Create output fieldsets.
    $output = array(
      'drupalgap_system_connect_status' => array(
        '#theme' => 'fieldset',
        '#title' => t('System Connect Status'),
        '#description' => '<div id="' . $div_id . '">&nbsp;</div>',
      ),
      'drupalgap_information' => array(
        '#theme' => 'fieldset',
        '#title' => t('More Information'),
        '#description' => "<p>$msg</p>"
      ),
    );

    return $output;
  }

  /**
   * @see https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Entity!EntityManagerInterface.php/interface/EntityManagerInterface/8
   */
  public function drupalgapConnect() {
    $response = new Response();
    $result = new \stdClass();
    
    //$entity_type = 'node';
    //$bundle = 'course';
    //$field_name = 'field_address';
    
    // @TODO make this configurable.
    $ok_entity_types = array('comment', 'file', 'node', 'taxonomy_term', 'user');

    // Field map.
    $result->fieldMap = array();
    $fieldMap = \Drupal::entityManager()->getFieldMap();
    foreach($fieldMap as $entity_type => $_fieldMap) {
      if (!in_array($entity_type, $ok_entity_types)) { continue; }
      $result->fieldMap[$entity_type] = $_fieldMap;
    }
    
    // All bundle info.
    $allBundleInfo = \Drupal::entityManager()->getAllBundleInfo();
    $result->allBundleInfo = array();
    foreach($allBundleInfo as $entity_type => $_allBundleInfo) {
      if (!in_array($entity_type, $ok_entity_types)) { continue; }
      $result->allBundleInfo[$entity_type] = $_allBundleInfo;
    }
    //$result->getBundleInfo = \Drupal::entityManager()->getBundleInfo($entity_type);
    
    // Field definitions and storage configs.
    $result->fieldDefinitions = array();
    $result->fieldStorageConfig = array();
    $result->entityFormDisplay = array();
    //$result->FieldConfig = \Drupal\field\Entity\FieldConfig::loadByName($entity_type, $bundle, $field_name);
    //$result->fieldDefinitions = \Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle);
    // For each entity type...
    foreach($ok_entity_types as $entity_type) {
      
      // Add the field definition for each bundle...
      $result->fieldDefinitions[$entity_type] = array();
      $result->entityFormDisplay[$entity_type] = array();
      foreach ($result->allBundleInfo[$entity_type] as $bundle_name => $bundle) {
        
        $result->fieldDefinitions[$entity_type][$bundle_name] =
          \Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle_name);
        
        $form_mode = 'default';
        $result->entityFormDisplay[$entity_type][$bundle_name] =
          entity_get_form_display($entity_type, $bundle_name, $form_mode)->getMode();
 
      }
      
      // Add the field storage config for each field on the entity type.
      $result->fieldStorageConfig[$entity_type] = array();
      foreach ($result->fieldMap[$entity_type] as $field_name => $_data) {
        
        
        // @todo we should be using the loadByName function here, but it isn't
        // working
        // @see http://drupal.stackexchange.com/q/167001/10645
        //$result->fieldStorageConfig[$entity_type][$field_name] = 
          //\Drupal\field\Entity\FieldStorageConfig::loadByName($entity_type, $field_name);
        $result->fieldStorageConfig[$entity_type][$field_name] = 
          \Drupal::config('field.storage.' . $entity_type . '.' . $field_name)->get();
        
        
          
      }
      
      // DISPLAYS

      //core.entity_form_display.node.course.default
      //core.entity_view_display.node.course.default
      //core.entity_view_mode.node.full
      
      
      
    }
   
    
    // May be useful at some point...
    //$result->getAllViewModes = \Drupal::entityManager()->getAllViewModes();
    //$result->getViewModes = \Drupal::entityManager()->getViewModes($entity_type);
    //$result->getAllFormModes = \Drupal::entityManager()->getAllFormModes();
    
    // Doesn't work.
    //\Drupal\field\Entity\FieldStorageConfig::loadByName($entity_type, $field_name);
    // Does work.
    //\Drupal::config('field.storage.node.field_address')->get();
    
    
    //$result->getFieldStorageDefinitions = \Drupal::entityManager()->getFieldStorageDefinitions($entity_type);
    //$result->loadMultiple = \Drupal::entityManager()->getStorage('field_storage_config')->loadMultiple();

    $response->setContent(json_encode($result));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  /**
   *
   *
   */
  public function drupalgapSystemConnect() {
    $response = new Response();
    // @see https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Session!AccountProxyInterface.php/interface/AccountProxyInterface/8
    $user = \Drupal::currentUser();
    $_account = $user->getAccount();
    $account = new \stdClass();
    $account->uid = $_account->id();
    $account->name = $_account->getUsername();
    $account->roles = $_account->getRoles();
    foreach ($_account as $key => $value) { $account->{$key} = $value; }
    unset($account->pass);
    if (!$_account->hasPermission('administer users')) {
      unset($account->init);
    }
    if (!$_account->isAuthenticated()) {
      $account->roles = array('anonymous user');
    }
    $json = array(
      'user' => $account,
      'remote_addr' => $_SERVER['REMOTE_ADDR']
    );
    $response->setContent(json_encode($json));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }


  /**
   * Obtain current user data. This is basically copied from D7 Services module.
   * For more info take a look at _system_resource_connect() function (Services).
   */
  public function userSystemResources() {
    // Currently logged in user.
    $output = new \stdClass();
    //$output->sessid = \Drupal::currentUser()->getSessionId();
    //$output->user_name = \Drupal::currentUser()->getSessionData();
    $output->uid = \Drupal::currentUser()->getAccount()->id();
    $output->roles = (object) \Drupal::currentUser()->getRoles();


    return $output;
  }


  /**
   * Returns a collection of variables from the current Drupal site.
   *
   * @return array
   *   Array of variables from the configuration.
   */
  public function drupalgapResourceSystemSiteSettings() {

    // Config names.
    $names = array(
      // @TODO for the moment only working for 'user_register'
      /*'admin_theme',
      'clean_url',
      'date_default_timezone',
      'site_name',
      'theme_default',
      'user_pictures',
      'user_email_verification',*/
      'user.settings' => array('register'),
    );

    // Invoke hook_drupalgap_site_settings() to let others specify variable names
    // to use.
    if (sizeof(\Drupal::moduleHandler()->getImplementations('drupalgap_site_settings')) > 0) {
      \Drupal::moduleHandler()->invokeAll('drupalgap_site_settings', $names);
    }

    // Now fetch the values.
    $settings = new \stdClass();
    foreach($names as $settingKeys => $settingKey) {
      foreach($settingKey as $name) {
        $value = \Drupal::config($settingKeys)->get($name);
        $settings->variable = new \stdClass();
        $settings->variable->$name = $value;
      }
    }

    // Add Drupal core version into settings.
    $settings->variable->drupal_core = "8";

    return $settings->variable;

  }

}
