<?php
namespace Drupal\access_by_ref\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\field\FieldConfigInterface;
use Drupal\user\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PhpStorage\PhpStorageFactory;


/**
 * Configure example settings for this site.
 */
class access_by_ref_settings_form extends FormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'access_by_ref_admin_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'access_by_ref.settings',
    ];
  }

  function getBundlesList($type = 'node'){
    $entityManager = \Drupal::service('entity.manager');
   $bundles = $entityManager->getBundleInfo($type);
   foreach($bundles as $key=>&$item){
     $bundles[$key] = $item['label'];
   }
   return $bundles;
  }

function flush_twig() {

  // Wipe the Twig PHP Storage cache. ABR needed
  PhpStorageFactory::get('twig')
    ->deleteAll(); 
  
}



  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

 
  $sql = 'SELECT * FROM {access_by_ref} ORDER BY node_type, reference_type'; // query the database for the set of configured access methods

  $result = \Drupal::database()->query($sql);

  $contentTypesList = $this->getBundlesList('node') ;
  $ntype_fields = array();
  $allFields = array();
  $allFieldData = array();
  foreach ($contentTypesList as $key=> $label) {
 
    $myFields = $this->fieldDataFetch($key);
    unset($myFields['body']);
    $ntype_fields[$key] = $myFields;
    $allFields = array_merge($allFields, $myFields);

    $myTypes =  $this->fieldDataFetch($key,'node', 'omni');
    $allFieldData = array_merge($allFieldData, $myTypes);

  }


  $parFields = array();
  $paragraphsTypesList = $this->getBundlesList('paragraph') ;
  foreach ($paragraphsTypesList as $key=> $label) {
    $myFields = $this->fieldDataFetch($key, 'paragraph');
    $ntype_fields[$key] = $myFields;
    $allFields = array_merge($allFields, $myFields);
    $parFields = array_merge($parFields, $myFields);

    $myTypes =  $this->fieldDataFetch($key,'paragraph', 'omni');
    $allFieldData = array_merge($allFieldData, $myTypes);
  }
 

  
  $ufields = ($this->fieldDataFetch('user', 'user'));
  $allFields = array_merge($allFields, $ufields);

  $ufieldtypes = ($this->fieldDataFetch('user', 'user','omni'));
  $allFieldData = array_merge($allFieldData, $ufieldtypes);

  asort($allFields); // put in alpha order
  asort($parFields); // put in alpha order

  $form = array();

  $form['#attached']['drupalSettings']['ctypes']=$contentTypesList;
  $form['#attached']['drupalSettings']['ufields']=$ufields;
  $form['#attached']['drupalSettings']['parfields']=$parFields;
  $form['#attached']['drupalSettings']['fields']=$ntype_fields;
  $form['#attached']['drupalSettings']['fieldData']=$allFieldData;
  $form['#attached']['library'][] = 'access_by_ref/configform';

  $rows = array('new'=>array('id'=>'new'));

  while($row = $result->fetchAssoc()){
    $rows[] = $row;

    if(!isset($ntype_fields[$row['node_type']])){
      $ntype_fields[$row['node_type']] =  $this->fieldDataFetch($row['node_type']);
    }
  }

  $headers = array('Managed Content Type', 'Reference Type', 'Controlling Field', 'Extra Data','Delete');
  $types = array('user'=>'User referenced', 'user_mail'=>"User's Mail", 'shared'=>'Profile Value', 'inherit'=>'Link to Parent');

  // leaving out  'manage_referenced'=>"Link FROM Parent", 'secret'=>"Secret Code"

  $form['#attached']['drupalSettings']['helps']=$helps;


  $info = "<p>This module extends update permissions to modules not owned by the current user. It works in several modes. The current user may update if:
            <ul>
            <li><b>User Referenced:</b> If a user is referenced in the relevant user reference field</li>
            <li><b>User Mail:</b> If the user's email matches (case insensitive) the email entered in the referenced email field</li>
            <li><b>Profile Value:</b> IIf the value in the user's profile matches the value in the controlling field</li>
            <li><b>Link to Parent:</b> <i>Best Choice</i> The user has edit rights over a node referenced in the controlling field</li>";
           // <li><b>Link FROM Parent:</b> <i>Risky</i> The node is referenced from a node the user can edit. If the user can edit that field, it gives them extensive control over site content</li>
           // <li><b>Secret Code:</b> <i>Experimental</i> The URL includes a parameter that matches the value of the field. E.g. ?field_foo=bar</li>
  $info .= "</ul></p>";
  $info .= "<p>These accesses will only be available to a user with the Access By Reference permissions, so be sure to set those</p>";
  $info .= "<p><b>To Use:</b> Select the content type to be controlled, and then the type of access you want to grant. Choose the field that will contain the effective data or link. In case we are looking for matched values, such as the 'Profile Value', specify in the Extra Field the field in the User Profile that has to match</p>";

  $form['info'] = array(
      '#type' => 'item',
  
  '#markup' => $info,
  );


  $form['abr'] = array(

    '#type' => 'table',
    '#id' => 'abrTable',
    '#caption' => $this->t('See and Review Access by Reference'),
    '#header' => $headers,
   );


    
  foreach($rows as $row){
dsm($row['node_field']);
    
    $form['abr']['id:' . $row['id']] = array(
      
      'ctype' => array(
        '#empty_option' => '-select-',
        '#type' => 'select',
        '#attributes' => array('class'=>array('ctype')),
        '#options' => $contentTypesList,
        '#size' => 1, 
        '#default_value' => $row['node_type'],
      ),
      'rtype' => array(
        '#type' => 'select',
        '#empty_option' => '-select-',
        '#attributes' => array('class'=>array('rtype')),
        '#options' => $types,
        '#size' => 1, 
        '#default_value' => $row['reference_type'],
      ),
      'field' => array(
        '#type' => 'select',
        '#empty_option' => '-select-',
        '#attributes' => array('class'=>array('fields')),
        '#options' => $allFields, //$ntype_fields[$row['node_type']],
        '#size' => 1, 
        '#default_value' => $row['node_field'],
      ),

      'extra' => array(
        '#type' => 'select',
        '#attributes' => array('class'=>array('extra')),
        '#options' => $allFields, //$ntype_fields[$row['node_type']],
        '#default_value' => $row['reference_data'],
        '#size' => 1, 
        
      ),

      'cbox'=>  array(
      '#type' => 'checkbox',
      '#attributes' => array('class'=>array('cbox')),
      
    ),
    );

    foreach($contentTypesList as $key=>$ctype){

      $form['abr']['id:' . $row['id']]['extra']['#options'][$key] = $ctype; // add the ctypes to the fields list
    }




  }

      // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#description' => $this->t('Submit, #type = submit'),
    ];


    return $form;
  }



function fieldDataFetch($contentType, $bundle = 'node', $property = 'label') {
  $entityManager = \Drupal::service('entity.manager');
    $fields = [];

    if(!empty($contentType)) {
        $fields = array_filter(
            $entityManager->getFieldDefinitions($bundle, $contentType), function ($field_definition) {
                return $field_definition instanceof FieldConfigInterface;
            }
        );
    }

    switch($property){
      case 'label':
        foreach($fields as $key=>&$field){
          $fields[$key] = $field->label();
        }
        break;
      case 'type':
        foreach($fields as $key=>&$field){
          $fields[$key] = $field->getType();
        }
        break;
      case 'handler':
        foreach($fields as $key=>&$field){
          $fields[$key] = $field->getSetting('handler');
        }
        break;
      case 'omni':
        foreach($fields as $key=>&$field){
          $vals =  array(
             'handler'=>$field->getSetting('handler'),
             'type' => $field->getType(),
             'label' => $field->label(),
             );
          $fields[$key] = $vals;
        }
        break;

    }


    return $fields;  
}

/**
 * {@inheritdoc}
 */
public function validateForm(array &$form, FormStateInterface $form_state) {
  //dsm($form_state);
}

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration

    $vals = ($form_state->getValues()['abr']);

    foreach($vals as $key => $row){
      $id = explode(':',$key)[1];
      $cbox = $row['cbox'];

      if($cbox == 1){// delete this
        $sql = "DELETE FROM {access_by_ref} WHERE `id` = :rowid";
        $result = db_query($sql,array(':rowid'=>$id));
        continue; // go to next row

      }


      unset ($row['cbox']); // remove it from the array

      $parms = array_combine(array( ':node_type', ':reference_type',':node_field',  ':reference_data'), $row);
      
      if($id == 'new' && strlen($parms[':node_type'])>3 && strlen($parms[':reference_type'])>3){
      
        $sql = "INSERT INTO {access_by_ref} (`node_type`, `node_field`, `reference_type`, `reference_data`) VALUES ( :node_type, :node_field, :reference_type, :reference_data)";
      } else { // it's an update
        $sql = "UPDATE {access_by_ref} SET `node_type` = :node_type, `node_field`= :node_field, `reference_type`=:reference_type, `reference_data` = :reference_data 
            WHERE `id` = :id ";
        $parms[':id'] = $id; // add that parm
      }
// okay, now run the query
      $result = db_query($sql,$parms);
    }

  // flush the cache
   $this->flush_twig();
  
  }
}