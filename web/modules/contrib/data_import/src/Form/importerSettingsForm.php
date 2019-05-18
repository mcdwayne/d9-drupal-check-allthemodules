<?php
/**
 * @file
 * Contains \Drupal\data_import\Form\importerSettingsForm.
 */
 
namespace Drupal\data_import\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\data_import\Controller;

class importerSettingsForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'importer_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $importer_id = '') {
    $values = $form_state->getValues();
    $importer = isset($importer_id) ? data_importer_load($importer_id) : '';
    if (!$importer) {
      $importer = array();
    }
  
    $importer += array(
      'importer_id' => '', 'name' => '', 'importer_type' => '', 'ftp_type' => 'radio_FTP',
      'host' => '', 'port' => '', 'username' => '', 'password' => '', 'remote_directory' => '',
      'local_directory' => '', 'file_name' => '', 'upload_file' => '', 'active' => 1,
      'optimizer' => '', 'file_type' => '', 'delimiter' => '', 'skip_line' => '', 'schedule_rule' => ''
    );

    $form = array(
      '#attributes' => array('enctype' => 'multipart/form-data'),
    );

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => $importer['name'],
      '#required' => TRUE,
    );
  
    $form['importer_id'] = array(
      '#type' => 'machine_name',
      '#title' => t('Importer id'),
      '#machine_name' => array(
        'exists' => 'data_importer_load',
        'source' => ['name'],
      ),
      '#default_value' => $importer['importer_id'],
      '#disabled' => !empty($importer['importer_id']), // Cannot change it once set.
      '#description' => t('Unique, machine-readable identifier for this importer.'),
      '#required' => TRUE,
    );
  
    $form['active'] = array(
      '#type' => 'checkbox',
      '#default_value' => $importer['active'],
      '#title' => t('Activate Importer'),
    );
  
    $form['importer_type'] = array(
      '#type' => 'select',
      '#options' => array(
        'none' => t('SELECT'),
        'ftp' => t('FTP/SFTP'),
        'upload' => t('Upload'),
      ),
      '#description' => t('Please select between Upload or FTP/SFTP'),
      '#default_value' => $importer['importer_type'],
      '#ajax' => array(
        'wrapper' => 'data_import_create_form-fieldset',
        'callback' => array($this, 'data_import_dynamic_sections_select_callback'),
      ),
    );
  
    // This fieldset just serves as a container for the part of the form
    // that gets rebuilt.
    $form['data_import_content-fieldset'] = array(
      '#type' => 'fieldset',
      '#prefix' => '<div id="data_import_create_form-fieldset">',
      '#suffix' => '</div>',
    );
  
    $form['data_import_content-fieldset']['ftp_sftp'] = array(
      '#type' => 'container',
      '#states' => array(
        'visible' => array(
          ':input[name="importer_type"]' => array('value' => 'ftp'),
        ),
      ),
    );
  
    $form['data_import_content-fieldset']['ftp_sftp']['ftp_type'] = array(
      '#type' => 'radios',
      '#title' => t('Please choose between FTP or SFTP'),
      '#options' => array(
        'radio_FTP' => t('FTP'),
        'radio_SFTP' => t('SFTP'),
      ),
      '#default_value' => $importer['ftp_type'],
      '#weight' => 1,
    );
  
    $form['data_import_content-fieldset']['ftp_sftp']['host'] = array(
      '#type' => 'textfield',
      '#title' => t('Host'),
      '#size' => 60,
      '#default_value' => $importer['host'],
      '#maxlength' => 128,
      '#required' => TRUE,
      '#weight' => 2,
    );
  
    $form['data_import_content-fieldset']['ftp_sftp']['port'] = array(
      '#type' => 'textfield',
      '#title' => t('Port'),
      '#default_value' => $importer['port'],
      '#size' => 60,
      '#maxlength' => 10,
      '#required' => TRUE,
      '#weight' => 3,
    );
  
    $form['data_import_content-fieldset']['ftp_sftp']['username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#default_value' => $importer['username'],
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#weight' => 4,
    );
  
    $form['data_import_content-fieldset']['ftp_sftp']['password'] = array(
      '#type' => 'password',
      '#title' => t('Password'),
      '#attributes' => array('value' => $importer['password']),
      '#maxlength' => 64,
      '#size' => 15,
      '#required' => TRUE,
      '#weight' => 5,
    );
  
    $form['data_import_content-fieldset']['ftp_sftp']['remote_directory'] = array(
      '#type' => 'textfield',
      '#title' => t('Remote Directory'),
      '#default_value' => $importer['remote_directory'],
      '#size' => 60,
      '#maxlength' => 255,
      '#weight' => 6,
    );
  
    $form['data_import_content-fieldset']['ftp_sftp']['local_directory'] = array(
      '#type' => 'textfield',
      '#title' => t('Local Directory'),
      '#default_value' => $importer['local_directory'],
      '#size' => 60,
      '#maxlength' => 255,
      '#weight' => 7,
    );
  
    $form['data_import_content-fieldset']['ftp_sftp']['file_name'] = array(
      '#type' => 'textfield',
      '#title' => t('File Name'),
      '#default_value' => $importer['file_name'],
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => 8,
    );
  
    $form['data_import_content-fieldset']['upload'] = array(
      '#type' => 'container',
      '#states' => array(
        'visible' => array(// action to take.
          ':input[name="importer_type"]' => array('value' => 'upload'),
        ),
      ),
    );
  
    $form['data_import_content-fieldset']['upload']['upload_file'] = array(
      '#title' => t('Upload File'),
      '#type' => 'managed_file',
      '#name' => 'upload_file',
      '#upload_location' => 'private://data_import/files',
      '#upload_validators' => array(
      'file_validate_extensions' => array('txt xls csv xlsx'),
      ),
      '#default_value' => array($importer['upload_file']),
      '#required' => TRUE,
      '#weight' => 9,
    );
  
    $form['file_type'] = array(
      '#type' => 'select',
      '#options' => array('txt' => 'TXT', 'csv' => 'CSV', 'xls' => 'XLS(X)'),
      '#description' => t('Please select file extension'),
      '#default_value' => $importer['file_type'],
    );
  
    $form['delimiter'] = array(
      '#type' => 'textfield',
      '#title' => t('Delimiter'),
      '#default_value' => $importer['delimiter'],
      '#size' => 8,
      '#maxlength' => 12,
      '#states' => array(
        'invisible' => array(
          ':input[name="file_type"]' => array('value' => 'xls'),
        ),
        'required' => array(
          ':input[name="file_type"]' => array('value' => 'txt'),
        ),
      ),
    );

    $form['skip_line'] = array(
      '#type' => 'textfield',
      '#title' => t('Skip line'),
      '#description' => t('Enter line number to be skip. If several, use comma to separate.'),
      '#default_value' => $importer['skip_line'],
    );

    if (\Drupal::moduleHandler()->moduleExists('elysia_cron')) {
      $options = variable_get('elysia_cron_default_rules', $GLOBALS['elysia_cron_default_rules']);
      $options['custom'] = t('Custom') . ' ...';
    } else {
      $options = array(
        '* * * * *' => t('Every minutes'),
        '*/15 * * * *' => t('Every 15 minutes'),
        '*/30 * * * *' => t('Every 30 minutes'),
        '0 * * * *' => t('Every hour'),
        '0 */6 * * *' => t('Every 6 hours'),
        '0 3 * * *' => t('Every day at 3AM'),
        '0 4 * * *' => t('Every day at 4AM'),
        '0 5 * * *' => t('Every day at 5AM'),
        '4 0 * * 0' => t('Once a week'),
        '4 0 1 * *' => t('Once a month'),
        'custom' => t('Custom') . ' ...',
      );
    }
  
    $rule = $importer['schedule_rule'];
    if ($rule && !isset($options[$rule])) {
      $options[$rule] = $rule;
    }
  
    $form['schedule_select_rule'] = array(
      '#title' => t('Schedule rule'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $rule,
      '#states' => array(
        'invisible' => array(
          ':input[name="schedule_select_rule"]' => array('value' => 'custom'),
        ),
      ),
    );
  
    $form['schedule_rule'] = array(
      '#type' => 'textfield',
      '#title' => t('Cron schedule rule'),
      '#default_value' => $rule,
      '#size' => 8,
      '#maxlength' => 128,
      '#states' => array(
        'visible' => array(
          ':input[name="schedule_select_rule"]' => array('value' => 'custom'),
        ),
      ),
    );
    
    $form['schedule_rule_help'] = array(
      '#type' => 'details',
      '#title' => t('Click for help and cron rules and script syntax'),
      '#open' => FALSE,
      '#description' => "
      <h3>Fields order</h3>
      <pre>
       +---------------- minute (0 - 59)
       |  +------------- hour (0 - 23)
       |  |  +---------- day of month (1 - 31)
       |  |  |  +------- month (1 - 12)
       |  |  |  |  +---- day of week (0 - 6) (Sunday=0)
       |  |  |  |  |
       *  *  *  *  *
      </pre>
      <p>Each of the patterns from the first five fields may be either * (an asterisk),
      which matches all legal values, or a list of elements separated by commas (see below).</p>
      <p>For 'day of the week' (field 5), 0 is considered Sunday, 6 is Saturday
      (7 is an illegal value)</p>
      <p>A job is executed when the time/date specification fields all match the current
      time and date. There is one exception: if both 'day of month' and 'day of week'
      are restricted (not '*'), then either the 'day of month' field (3) or the 'day of week'
      field (5) must match the current day (even though the other of the two fields
      need not match the current day).</p>
      
      <h3>Fields operators</h3>
      <p>There are several ways of specifying multiple date/time values in a field:</p>
      <ul>
      <li>The comma (',') operator specifies a list of values, for example: '1,3,4,7,8'</li>
      <li>The dash ('-') operator specifies a range of values, for example: '1-6', which is equivalent to '1,2,3,4,5,6'</li>
      <li>The asterisk ('*') operator specifies all possible values for a field. For example, an asterisk in the hour time field would be equivalent to 'every hour' (subject to matching other specified fields).</li>
      <li>The slash ('/') operator (called 'step') can be used to skip a given number of values. For example, '*/3' in the hour time field is equivalent to '0,3,6,9,12,15,18,21'.</li>
      </ul>
      
      <h3>Examples</h3>
      <pre>
       */15 * * * * : Execute job every 15 minutes
       0 2,14 * * *: Execute job every day at 2:00 and 14:00
       0 2 * * 1-5: Execute job at 2:00 of every working day
       0 12 1 */2 1: Execute job every 2 month, at 12:00 of first day of the month OR at every monday.
      </pre>
      
      <h3>Script</h3>
      <p>You can use the script section to easily create new jobs (by calling a php function)
      or to change the scheduling of an existing job.</p>
      <p>Every line of the script can be a comment (if it starts with #) or a job definition.</p>
      <p>The syntax of a job definition is:</p>
      <code>
      &lt;-&gt; [rule] &lt;ch:CHANNEL&gt; [job]
      </code>
      <p>(Tokens betweens [] are mandatory)</p>
      <ul>
      <li>&lt;-&gt;: a line starting with '-' means that the job is DISABLED.</li>
      <li>[rule]: a crontab schedule rule. See above.</li>
      <li>&lt;ch:CHANNEL&gt;: set the channel of the job.</li>
      <li>[job]: could be the name of a supported job (for example: 'search_cron') or a function call, ending with ; (for example: 'process_queue();').</li>
      </ul>
      <p>A comment on the line just preceding a job definition is considered the job description.</p>
      <p>Remember that script OVERRIDES all settings on single jobs sections or channel sections of the configuration</p>
      
      <h3>Examples of script</h3>
      <pre>
      # Search indexing every 2 hours (i'm setting this as the job description)
      0 */2 * * * search_cron
      
      # I'll check for module status only on sunday nights
      # (and this is will not be the job description, see the empty line below)
      
      0 2 * * 0 update_status_cron
      
      # Trackback ping process every 15min and on a channel called 'net'
      */15 * * * * ch:net trackback_cron
      
      # Disable node_cron (i must set the cron rule even if disabled)
      - */15 * * * * node_cron
      
      # Launch function send_summary_mail('test@test.com', false); every night
      # And set its description to 'Send daily summary'
      # Send daily summary
      0 1 * * *  send_summary_mail('test@test.com', false);
      "
    );
    
    $form['optimizer'] = array(
      '#type' => 'checkbox',
      '#title' => t('Optimize Import'),
      '#description' => t('This will optimize import. All rows which are similar to rows in the previous batch run will be skipped.'),
      '#default_value' => $importer['optimizer'],
      '#weight' => 10,
    );
    
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save Configuration'),
      '#states' => array(
        'invisible' => array(
          ':input[name="importer_type"]' => array('value' => 'none'),
        ),
      ),
      '#weight' => 10,
    );
  
    $form['action'] = array(
      '#type' => 'hidden',
      '#value' => $importer_id ? 'edit' : 'create',
    );
  
    if ($importer['importer_type'])
      $_SESSION['importer_type'] = $importer['importer_type'];
  
    if (!empty($values['importer_type']) || isset($_SESSION['importer_type'])) {
  
      $question_type = !empty($values['importer_type']) ? $values['importer_type'] : $_SESSION['importer_type'];
  
      switch ($question_type) {
        case 'ftp':
          $_SESSION['importer_type'] = 'ftp';
          unset($form['data_import_content-fieldset']['upload']);
          break;
  
        case 'upload':
          $_SESSION['importer_type'] = 'upload';
          unset($form['data_import_content-fieldset']['ftp_sftp']);
          break;
  
        default :
          unset($form['data_import_content-fieldset']['ftp_sftp']);
          unset($form['data_import_content-fieldset']['upload']);
          unset($form['data_import_content-fieldset']['submit']);
          break;
      }
    }
  
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate field Cron schedule rule
    if (!empty($form_state->getValue('schedule_select_rule')) && $form_state->getValue('schedule_select_rule') !== 'custom') {
      $form_state->setValue('schedule_rule',$form_state->getValue('schedule_select_rule'));
    }
    if (!preg_match('/^\\s*([0-9*,\/-]+[ ]+[0-9*,\/-]+[ ]+[0-9*,\/-]+[ ]+[0-9*,\/-]+[ ]+[0-9*,\/-]+)\\s*$/', $form_state->getValue('schedule_rule'))) {
      $form_state->setErrorByName('schedule_rule', t('Invalid rule: !rule', array('!rule' => $form_state->getValue('schedule_rule'))));
    }

    // Match file type and file upload/filename
    $extension = null;
    if($form_state->getValue('importer_type') != 'none'){
      if(($form_state->getValue('importer_type') == 'upload') && !empty($form_state->getValue('upload_file')) ){
        $file = file_load($form_state->getValue('upload_file')[0]);
        $extension = (pathinfo($file->getFileUri())['extension'] == 'xlsx')?'xls':pathinfo($file->getFileUri())['extension'];
      }else if(($form_state->getValue('importer_type') == 'ftp') && !empty($form_state->getValue('file_name')) ){
        $file = $form_state->getValue('file_name');
        if(!isset(pathinfo($file)['extension'])){
          $form_state->setErrorByName('file_name', t('Invalid filename.'));
        }else{
          $extension = (pathinfo($file)['extension'] == 'xlsx')?'xls':pathinfo($file)['extension'];
        }
      }
      if(!is_null($extension) && (strtolower($extension) != $form_state->getValue('file_type')) ){
        $form_state->setErrorByName('file_type', t('File type not match!'));
      }
    }

    // Validate skip line
    if (!empty($form_state->getValue('skip_line'))){
      $skip_line = $form_state->getValue('skip_line');
      $skip_line_array = explode(',',$skip_line);
      foreach($skip_line_array as $line){
        if(!empty(trim($line)) && !is_numeric(trim($line))){
          $form_state->setErrorByName('skip_line', t('Invalid value in skip line field!'));
        }
      }
    }
    
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    unset($_SESSION['importer_type']);
    data_importer_save($form_state->getValues());
  
    // Save uploaded file id any
/*     if(isset($form_state->getValue('upload_file')[0])) {
      $file = file_load($form_state->getValue('upload_file')[0]);
      $file->setPermanent();
      $file->save();
    } */

    // Redirect to mapping setting page if action is create
    if ($form_state->getValue('action') == 'create'){
      $url = \Drupal\Core\Url::fromRoute('data_import.mapping', ['importer_id' => $form_state->getValue('importer_id')]);
      $form_state->setRedirectUrl($url);
    }
  
    drupal_set_message(t('The configuration have been saved.'));
  }

  /**
    * Function ajax callback
    */
  public function data_import_dynamic_sections_select_callback(array &$form, FormStateInterface $form_state) {
    return $form['data_import_content-fieldset'];
  }

}