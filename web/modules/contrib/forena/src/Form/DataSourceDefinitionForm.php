<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/3/2016
 * Time: 9:44 AM
 */

namespace Drupal\forena\Form;


use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\forena\DataManager;

class DataSourceDefinitionForm extends FormBase {

  public function getFormID() {
    return 'forena_data_source_definition_form';
  }

  /**
   * [@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $source=NULL) {
    $form['item'] = [
      '#type' => 'item',
      '#title' => 'Data Source',
      '#markup' => $source
    ];

    $data_list = DataManager::instance()->repositories;
    $adding = !$source || !isset($data_list[$source]);
    $storage = $form_state->getStorage();
    $values = $form_state->getValues();
    // Initialize for the first time through.
    if (!$storage) {
      // We can tell that we are adding because there is no name
      if ($adding) {
        $storage = array(
          'name' => '',
          'title' => '',
          'source' => '',
          'config' => array(
            'source' => 'user',
            'driver' => 'FrxDrupal',
            'database' => 'default',
            'access callback' => 'forena_user_access_check',
            'user callback' => 'forena_current_user_id'
          ),
        );
      }
      else {
        $r = $data_list[$source];

        // Remove the object from the data.
        unset($r['data']);

        $storage = [
          'name' => $source,
          'title' => $r['title'],
          'source' => @$r['source'],
          'config' => $r,
        ];
      }
    }


    $config = $storage['config'];

    $locked = !($adding || (@$config['source'] == 'user'));

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('Machine readable name.  Used in referencing all data used by this source. must should not contain any special characters or spaces.'),
      '#disabled' => !$adding,
      '#default_value' => $storage['name'],
      '#required' => TRUE,
    );

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#required' => TRUE,
      '#description' => t('Human readable name that describes the data source.  This primarily occurs in error messages where the data source cannot be accessed.'),
      '#default_value' => $storage['title'],
    );

    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#description' => t('Disabling will cause all queries to return no data.'),
      '#default_value' => @$storage['enabled']!==0,
    );

    $form['debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Debug'),
      '#description' => t('Write information to the screen and logs for each query executed.'),
      '#default_value' => @$config['debug'],
    );

    $form['source'] = array(
      '#type' => 'textfield',
      '#title' => t('source'),
      '#required' => TRUE,
      '#disabled' => $locked,
      '#description' => t('Directory containing data block files.'),
      '#default_value' => @$storage['source'],
    );

    $user_options = array(
      '' => 'None',
      'forena_current_user_id' => 'UID',
      'forena_current_user_name' => 'User name',
    );

    $form['user_callback'] = array(
      '#type' => 'select',
      '#title' =>  'Current user',
      '#disabled' => $locked,
      '#description' => t('Can be refererenced as :current_user in each data block.'),
      '#options' => $user_options,
      '#default_value' => @$config['user callback'],
    );


    // Access method list
    $access = array(
      'callback' => t('Use drupal permissions'),
      'block' => t('Match values provided by a data block.'),
    );

    $form['access_method'] = array(
      '#type' => 'select',
      '#options' => $access,
      '#disabled' => $locked,
      '#title' => t('Data security method'),
      '#default_value' => empty($config['access block']) ? 'callback' : 'block',
      '#description' => t('Specify how the ACCESS defined for a data block is to be interpreted.'),
      '#ajax' => array(
        'callback' => 'forena_access_info_callback',
        'wrapper' => 'access-details',
      ),
    );

    $form['access_details'] = array(
      '#type' => 'fieldset',
      '#prefix' => '<div id="access-details">',
      '#suffix' => '</div>',
      '#title' => t('Details'),
    ) ;

    switch (!empty($values['access_method']) ? $values['access_method'] : $form['access_method']['#default_value']) {
      case 'block':
        $form['access_details']['access_block'] = array(
          '#type' => 'textfield',
          '#title' => 'Data block providing permissions list',
          '#disabled' => $locked,
          '#autocomplete_path' => 'forena/data_block/autocomplete',
          '#description' => t('The datablock to be used to interpret permissions.  This should return a single column of permissions based on the current user.   May be provided by another repository.'),
          '#default_value' => @$config['access block'],
        );
        break;
      default:
        $form['access_details']['access_callback'] = array(
          '#type' => 'item',
          '#title' => 'Access callback',
          '#disabled' => $locked,
          '#markup' => @$config['access callback'],
        );
    }

    // Driver list
    $drivers = array(
      'FrxDrupal' => t('Drupal'),
      'FrxOracle' => t('Oracle Database'),
      'FrxPDO' => t('PDO other than Drupal'),
      'FrxPostgres' => t('Postgres Database'),
      'FrxMSSQL' => t('MSSQL Database'),
      'FrxFiles' => t('XML Files'),
    );

    $form['driver'] = array(
      '#type' => 'select',
      '#title' => t('Driver'),
      '#description' => t('Forena data connection type'),
      '#options' => $drivers,
      '#disabled' => $locked,
      '#default_value' => $config['driver'],
      '#ajax' => array(
        'callback' => 'forena_connection_info_callback',
        'wrapper' => 'conn-div',
      ),
    );

    $form['connection'] = array(
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => 'Connection info',
      '#prefix' =>  '<div id="conn-div">',
      '#suffix' => '</div>',
    );

    $driver = (!empty($values['driver']) ? $values['driver'] : $config['driver']);

    // Common controls used in mulitple providers.
    $uri = array(
      '#type' => 'textfield',
      '#title' => t('uri'),
      '#descripton' => t('Connection string: see appropriate php documentation for more details.'),
      '#default_value' => @$config['uri'],
      '#required' => TRUE,
    );

    $user = array(
      '#type' => 'textfield',
      '#title' => t('User'),
      '#default_value' => @$config['user'],
    );
    $password= array(
      '#type' => 'password',
      '#title' => t('Password'),
      '#default_value' => @$config['password'],
    );

    switch ($driver) {
      case 'FrxDrupal':
        $db_info = Database::getAllConnectionInfo();
        $db_list = array_combine(array_keys($db_info), array_keys($db_info));
        $form['connection']['database'] = array(
          '#type' => 'select',
          '#title' => t('Database'),
          '#disabled' => $locked,
          '#default_value' => @$config['database'],
          '#options' => $db_list,
          '#markup' => 'Determined by Drupal settings.php file',
        );
        break;
      case 'FrxMSSQL':
        $form['connection']['uri'] = $uri;
        $form['connection']['user'] = $user;
        $form['connection']['new_password'] = $password;
        $form['connection']['database'] = array(
          '#type' => 'textfield',
          '#disabled' => $locked,
          '#title' => t('Database'),
          '#default_value' => $config['database'],
          '#required' => TRUE,
        );
        $form['connection']['mssql_xml'] = array(
          '#type' => 'checkbox',
          '#disabled' => $locked,
          '#title' => t('Microsoft SQL native XML'),
          '#description' => t('Use for XML auto queries to generate XML.'),
          '#default_value' => $config['mssql_xml'],
        );
        break;
      case 'FrxOracle':
        $form['connection']['uri'] = $uri;
        $form['connection']['user'] = $user;
        $form['connection']['new_password'] = $password;
        $form['connection']['character_set'] = array(
          '#type' => 'textfield',
          '#title' => t('Character Set'),
          '#disabled' => $locked,
          '#description' => t('Leave blank for default character set'),
          '#default_value' => @$config['character_set'],

        );
        $form['connection']['oracle_xml'] = array(
          '#type' => 'checkbox',
          '#title' => t('Oracle native XML'),
          '#disabled' => $locked,
          '#description' => t('Use the function provided with Forena to generate XML.  Requires installing a function into the database'),
          '#default_value' => @$config['oracle_xml'],
        );
        break;
      case 'FrxPDO':
        $form['connection']['uri'] = $uri;
        $form['connection']['user'] = $user;
        $form['connection']['new_password'] = $password;
        break;
      case 'FrxPostgres':
        $form['connection']['uri'] = $uri;
        $form['connection']['new_password'] = $password;
        $form['connection']['postgres_xml'] = array(
          '#type' => 'checkbox',
          '#title' => t('Postgres native XML'),
          '#disabled' => $locked,
          '#default_value' => @$config['postgres_xml'],
          '#description' => t('Use Postgres native XML support.  Requires Posgres 8.3 or better'),
        );
        break; 

      default:
        $form['connection']['uri'] = $uri;
    }

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    $form_state->setStorage($storage);
    return $form;
  }

  /**
   * [@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $storage = $form_state->getStorage();
    $name = $values['name'];
    $config = $storage['config'];
    $config['source'] = @$values['source'];
    $config['driver'] = $values['driver'];
    $config['user callback'] = $values['user_callback'];
    $config['debug'] = $values['debug'];
    if (@$values['connection']['new_password']) {
      $values['connection']['password'] = $values['connection']['new_password'];
    }
    if (isset($values['connection']['new_password'])) unset($values['connection']['new_password']);
    if (is_array(@$values['connection'])) $config = array_merge($config, @$values['connection']);
    if ($values['access_method']=='callback') {
      $config['access callback'] = empty($values['access_callback']) ? 'forena_user_access_check' : $values['access_callback'];
      if (isset($config['access block'])) unset($config['access block']);
    }
    else {
      $config['access block'] = $values['access_block'];
    }
    $config_str = serialize($config);
    $result = db_query('SELECT * FROM {forena_repositories} WHERE repository = :name', array(':name' => $name));

    if ($repos = $result->fetchObject()) {
      db_update('forena_repositories')
        ->fields(array(
          'title' => $values['title'],
          'enabled' => $values['enabled'],
          'config' => $config_str,
        ))
        ->condition('repository', $name, '=')
        ->execute();
    }
    else {
      db_insert('forena_repositories')
        ->fields(array(
          'repository' => $name,
          'title' => $values['title'],
          'enabled' => $values['enabled'],
          'config' => $config_str,
        ))
        ->execute();
    }
    drupal_set_message(t('The configuration options have been saved.'));
  }

}