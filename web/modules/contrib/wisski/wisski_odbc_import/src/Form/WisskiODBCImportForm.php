<?php
/**
 * @file
 *
 */
   
namespace Drupal\wisski_odbc_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;

   
/**
 * Overview form for ontology handling
 *
 * @return form
 *   Form for the ontology handling menu
 * @author Mark Fichtner
 */
class WisskiODBCImportForm extends FormBase {
    
  const UPDATE_MODE_APPEND = 'append';
  const UPDATE_MODE_NEW = 'new';
  const UPDATE_MODE_TAKE_EXISTING = 'take_existing';
  const UPDATE_MODE_REPLACE = 'replace';
  const UPDATE_MODE_SKIP = 'skip';
  const UPDATE_MODE_TAKE = 'take';
  
  
  public static function log() {
    static $logger = NULL;
    if ($logger === NULL) {
      $logger = \Drupal::logger('WissKI ODBC Import');
    }
    return $logger;
  }


  /**
   * {@inheritdoc}.
   * The Id of every WissKI form is the name of the form class
   */
  public function getFormId() {
    return 'WisskiODBCImportForm';
  }
                        
  public function buildForm(array $form, FormStateInterface $form_state) {
    $items = array();

    $items['source'] = array(
      '#type' => 'fieldset',
      '#title' => t('Specify transformation file'),
      '#required' => TRUE,
      '#weight' => 2,
      'url' => array(
        '#type' => 'textfield',
        '#title' => t('Url'),
        '#default_value' => '',
        '#disabled' => FALSE,
      ),
      'upload' => array(
        '#type' => 'file',
        '#title' => t('File upload'),
        // port to D8:
        // we must explicitly set the extension validation to allow xml files
        // to be uploaded.
        // an empty array disables the extension restrictions:
        // this is theoretically somewhat insecure but we get away with it ftm...
        '#upload_validators' => array(
          'file_validate_extensions' => array(),  // => array('xml')
        ),  
      ),
      'paste' => array(
        '#type' => 'textarea',
        '#title' => $this->t('Direct paste'),
        '#rows' => 4,
      ),
    );

    $items['batch_limit'] = array(
      '#type' => 'number',
      '#title' => $this->t('Items per batch run'),
      '#default_value' => 20,
      '#min' => 0,
      '#max' => 1000,
      '#description' => $this->t('Items / Entities imported per run. Reduce amount to avoid server timeouts. 0 disables batch processing.'),
      '#weight' => 50,
    );

    $items['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 100,
    );

    return $items;

  }   

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Port to D8 file form element handling:
    // file..save_upload must be called in validate, not in submit.
    // it gives us all uploaded files as File objects.
    $file_url = NULL;
    $files = file_managed_file_save_upload($form['source']['upload'], $form_state);
    if ($files) {
      $file = reset($files);  // first one in array (array is keyed by file id)
      $file_url = $file->getFileUri();
    }
    else {
      $file_url = $form_state->getValues()['url'];
    }
    if ($file_url) {
      $xml = simplexml_load_file($file_url);
    }
    elseif ($xml_content = $form_state->getValues()['paste']) {
      $xml = simplexml_load_string($xml_content);
    }
    else {
      // if no file is given, it is an error
      $form_state->setError($form['source'], $this->t('You must specify an import script.'));
    }
    // if we came here, the user uploaded some data, but it may be invalid
    if (!$xml) {
      $form_state->setError($form['source'], $this->t('No valid XML.'));
    }
    else {
      // as we have saved the file already, we cache its path for submitForm()
      $storage = $form_state->getStorage();
      $storage['import_script_content'] = $xml->asXml();
      $form_state->setStorage($storage);
    }
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // parse the import script file into SimpleXMLElement.
    // we already fetched and parsed the xml during validation so we can be
    // sure it's ok
    $xml_content = $form_state->getStorage()['import_script_content'];
    $import_script_xml = simplexml_load_string($xml_content);

    // parse the db parameters 
    $db_params = $this->getConnectionParams($import_script_xml);
    // we have two operation modes: batch and non-batch
    // if limit is 0 we are in non-batch mode, else batch
    $limit = $form_state->getValues()['batch_limit'];
    if ($limit) { // batch mode
      // define the batch
      $batch = [
        'title' => $this->t('Importing'),
        'operations' => [],
        'progress_message' => 'Completed @current / @total tables; time elapsed: @elapsed',
        'progressive' => TRUE,
        'finished' => [static::class, 'finishBatch'],
      ];
      // each table import instruction is a separate operation
      $i = 0;
      foreach ($import_script_xml->table as $table) {
        $batch['operations'][] = [
          [static::class, 'storeTableBatch'],
          // SimpleXMLElement cannot be serialized. Therefore we store the
          // serialized xml fragment and parse it when needed
          [$db_params, $i, $table->asXml(), $limit],
        ];
        $i++;
      }
      // register the batch; batch_process is called automatically(!?)
      self::log()->info('Start import as batch with {ops} operations.', ['ops' => count($batch['operations'])]); 
      batch_set($batch);
    }
    else { // non-batch mode
      self::log()->info('Start import in one go.'); 
      $cnt = $this->importInOneGo($import_script_xml, $db_params);
      drupal_set_message(t('Finished import.'));
      drupal_set_message(t('@num entities have been created or updated.', ['@num' => $cnt]));
      self::log()->info('Completed import. {num} entities have been created or updated.', ['@num' => $cnt]);
    }
  }
  
  
  /**
   * Helper operation to parse the db connection parameters
   */
  protected function getConnectionParams($import_script_xml) {
    $params = [
      'is_drupal_db' => FALSE,
    ];
    $connection_xml = $import_script_xml;
    if (isset($import_script_xml->connection)) {
      $connection_xml = $import_script_xml->connection;
    }
    if (isset($connection_xml['use_drupal_db']) && $connection_xml['use_drupal_db']) {
      $params['is_drupal_db'] = TRUE;
    }
    else {
      $params['dbserver'] = (string) $connection_xml->url;
      $params['dbuser'] = (string) $connection_xml->user;
      $params['dbpass'] = (string) $connection_xml->password;
      $params['db'] = (string) $connection_xml->database;
      $params['dbport'] = isset($connection_xml->port) ? (string) $connection_xml->port : '3306';
    }
    return $params; 
  }
  
  
  /**
   * Helper function that establishes a db connection from the given params.
   */
  public static function getConnection($params) {
    if ($params['is_drupal_db']) {
      $connection = \Drupal::database();
    }
    else {
      $connection = mysqli_connect(
        $params['dbserver'], 
        $params['dbuser'], 
        $params['dbpass'], 
        $params['db'], 
        $params['dbport']
      );
      if(!$connection) {
        drupal_set_message("Connection could not be established!",'error');
        return;
      } else {
        drupal_set_message("Connection established!");
      }
      mysqli_set_charset($connection,"utf8");
    }
    return $connection;
  }

  
  /**
   * Helper function that closes the db connectin if necessary
   */
  public static function closeConnection($connection, $params) {
    if (!$params['is_drupal_db']) {
      mysqli_close($connection);
    }
  }

  
  /** 
   * The main batch operation function and callback.
   * Actually a batch wrapper around the the main import function storeTable().
   * @see callback_batch_operation()
   */
  public static function storeTableBatch($db_params, $table_index, $import_script, $limit, &$context) {
    // get the db connection
    $connection = self::getConnection($db_params);
    // load the table import declarations  
    $table_xml = simplexml_load_string($import_script);
    // init the sandbox
    if (empty($context['sandbox'])) {
      $context['message'] = t('Processing table @t', ['@t' => $table_index]);
      $context['sandbox'] = [
        'offset' => 0,
        'already_seen' => [],
      ];
      if (!isset($context['results']['already_seen'])) {
        $context['results']['already_seen'] = [];
      }
      // take already seen entities from previous operation
      if (isset($context['results']['table'][$table_index - 1]['already_seen'])) {
        $context['sandbox']['already_seen'] = $context['results']['table'][$table_index - 1]['already_seen'];
      }
      self::log()->info("Start import of table index $table_index");
      $context['sandbox']['total_rows'] = self::totalRowCount($db_params, $connection, $table_xml);
    }
    // get data from last run
    $offset = $context['sandbox']['offset'];
    $already_seen = $context['sandbox']['already_seen'];
    // do the import
    $row_count = self::storeTable(
      $table_xml, 
      $already_seen, 
      $connection, 
      $db_params['is_drupal_db'],
      $offset,
      $limit
    );
    self::closeConnection($connection, $db_params);
    // check if we are done with this table and store (intermediate) results
    if ($row_count < $limit) {
      $context['finished'] = 1;
      $context['results']['table'][$table_index]['total'] = $offset + $row_count;
      $context['results']['table'][$table_index]['already_seen'] = $already_seen;
      $context['results']['already_seen'] += $already_seen;
      self::log()->info("Finished import of table index $table_index");
    }
    else {
      // we didn't count total rows, so just make up some %-number
      if ($context['sandbox']['total_rows'] !== NULL) {
        $context['finished'] = (0.0 + $offset + $row_count) / $context['sandbox']['total_rows'];
      }
      else {
        $context['finished'] = max(0, min(0.999, 1 - ($limit**2 / ($offset + $row_count))));
      }
      $context['sandbox']['offset'] = $offset + $row_count;
      $context['sandbox']['already_seen'] = $already_seen;
    } 
  }

  
  /**
   * Callback when batch has finished
   * @see callback_batch_finished()
   */
  public static function finishBatch($success, $results, $operations) {
    if ($success) {
      drupal_set_message(t('Finished import.'));
      drupal_set_message(t('@num entities have been created or updated.', ['@num' => count($results['already_seen'])]));
      self::log()->info(
          'Successfully completed import. {num} entities have been created or updated. Entity IDs:{ids}',
          ['num' => count($results['already_seen']), 'ids' => join(", ", $results['already_seen'])]);
    }
    else {
      drupal_set_message(t('Errors importing tables. @c tables could not be imported.', ['@c' => count($operations)]), 'error');
      self::log()->error(
        'Errors while processing import: {operations} operations left',
        [
          'operations' => count($operations),
        ]
      );
    }
  }


  /**
   * non-batch import function
   */
  public function importInOneGo($import_script_xml, $db_params) {
    $connection = self::getConnection($db_params);
    $alreadySeen = array();
    foreach ($import_script_xml->table as $table) {
      self::storeTable($table, $alreadySeen, $connection, $db_params['is_drupal_db']);
    }
    self::closeConnection($connection, $db_params);
    return count($alreadySeen);
  }

  
  /**
   * Compute the total amount of rows to import for a <table> tag
   */
  public static function totalRowCount($db_params, $connection, $table) {
    // we look for a special <countSql> tag that provides a ready-to-be-used
    // sql query
    $sql = isset($table->countSql) ? trim((string) $table->countSql) : '';
    if (!$sql) {
      $sql = isset($table->sql) ? trim((string) $table->sql) : '';
      if ($sql) {
        // for complete sql queries we cannot compute the count
        return NULL;
      }
      // build the count query
      $tablename = isset($table->name) ? (string) $table->name : '';
      $append = isset($table->append) ? (string) $table->append : '';
      $sql = "SELECT COUNT(*) FROM `$tablename` $append";
    }

    // do the db query; distinguish if local connection or not
    if ($db_params['is_drupal_db']) {
      try {
        return $connection->query($sql)->fetchField();
      }
      catch (\Exception $e) {
        drupal_set_message($e->getMessage(), 'error');
        return NULL;
      }
    }
    else {
      $qry = mysqli_query($connection, $sql);
      if(!$qry) {
        drupal_set_message("Anfrage '$sql' gescheitert!",'error');
        drupal_set_message(mysqli_error($connection), 'error');
        return NULL;
      }
      $row = mysqli_fetch_array($qry);
      return $row[0];
    }
  }

  
  /**
   * Main import function
   *
   * TODO: is $alreadySeen still used??? it is never assigned a value!
   */
  public static function storeTable($table, &$alreadySeen, $connection, $is_drupal_db, $offset = 0, $limit = 0) {
    $rowiter = 0;
    $delimiter = isset($table->delimiter) ? (string) $table->delimiter : '';
    $trim = isset($table->trim) ? (string) $table->trim : FALSE;  
  
    $sql = isset($table->sql) ? trim((string) $table->sql) : '';
    // we introduce the special <sql> tag if you want to define a whole sql 
    // select query. This is more readable for more complex cases.
    if (empty($sql)) {
      $tablename = isset($table->name) ? (string) $table->name : '';
      $append = isset($table->append) ? (string) $table->append : '';
      $select = isset($table->select) ? (string) $table->select : '';
      if(empty($append))
        $append = "";
      $sql = "SELECT $select FROM `$tablename` $append";
    }
    if ($limit) {
      $sql .= " LIMIT $limit";
    }
    if ($offset) {
      $sql .= " OFFSET $offset";
    }
    // do the db query; distinguish if local connection or not
    if ($is_drupal_db) {
      try {
        $qry = $connection->query($sql);
      }
      catch (\Exception $e) {
        drupal_set_message($e->getMessage(), 'error');
        return;
      }
    }
    else {
      $qry = mysqli_query($connection, $sql);
      if(!$qry) {
        drupal_set_message("Anfrage '$sql' gescheitert!",'error');
        drupal_set_message(mysqli_error($connection), 'error');
        return;
      }
    }
    // iterate thru the result and create entities for each result row
    while($is_drupal_db ? $row = $qry->fetchAssoc() : $row = mysqli_fetch_array($qry)) {
      foreach($table->row as $XMLrow) {
        $alreadySeen += self::storeRow($row, $XMLrow, $alreadySeen, $delimiter, $trim);
      }
      $rowiter++;
    }
    // done with import!
    // we now may want to do a postprocessing db query (cleanup or something)
    if (isset($table->postprocess_sql)) {
      foreach ($table->postprocess_sql as $pp_sql) {
        $sql = trim((string) $pp_sql);
        if ($is_drupal_db) {
          try {
            $connection->query($sql);
          }
          catch (\Exception $e) {
            drupal_set_message($e->getMessage(), 'error');
            return;
          }
        }
        else {
          mysqli_query($connection, $sql);
        }
      }
    }
    // return the number of db rows
    return $rowiter;
  }

  
  public static function storeRow($row, $XMLrow, $alreadySeen, $delimiter, $trim) {
    $i = 0;
    $entity_ids = [];
    foreach($XMLrow->bundle as $value) { 
      $bundleid = (string) $value['id'];
      $entity_id = self::storeBundle($row, $value, $bundleid, $delimiter, $trim);
      if ($entity_id) {
        $entity_ids[$entity_id] = $entity_id;
      }
      $i++;
    }
    return $entity_ids;
  }
  
  
  protected static function parseUpdateCondition($cond, $row_values) {
    $operator = isset($cond['operator']) ? (string) $cond['operator'] : '=';
    $value    = isset($cond['value'])    ? (string) $cond['value']    : NULL;
    $field    = isset($cond['field'])    ? (string) $cond['field']    : NULL;
    $column   = isset($cond['column'])   ? (string) $cond['column']   : NULL;
    // if there is a field att, we prepare a entity query condition  
    if ($field !== NULL) {  
      if ($column !== NULL) {
        // we prefer the column attr over the "fixed" value att
        $value = $row_values[$column];
      }
      return [
        'field' => $field,
        'operator' => $operator,
        'value' => $value,
      ];
    }
#dpm([$column, $row_values[$column], $operator, $value], 'col cmp');
    // if there is no field att but a column and a value att, we compare both.
    // currently only equality and inequality can be checked
    if ($column !== NULL && $value !== NULL) {
      if ($operator == '=') {
        return $row_values[$column] == $value;
      }
      elseif ($operator = '!=') {
        return $row_values[$column] != $value;
      }
    }
    // all other cannot be handled
    return NULL;
  }
  

  protected static function checkUpdateMode($mode) {
    $modes = ['new', 'replace', 'skip', 'take', 'take_existing'];
    if (in_array($mode, $modes)) {
      return $mode;
    }
    return NULL;
  }

  
  /** Check whether there are update policies defined and which update policy
   * holds. Policies are evaluated in document order.
   *
   * Mode 'new' is the default mode that corresponds to the former
   * import behavior.
   *
   * @return array, where first item is the update mode, the second item is 
   *         the matching entity and the third one is an array of further 
   *         duplicates / matching entities. Second is null and third is empty
   *         array if none are found.
   */
  protected static function evaluateUpdatePolicies($bundle_xml, $bundleid, $row_values) {
    
    // the default update behavior:
    $mode = 'new';
    $matching_eid = NULL;
    $matching_eids = [];

    // check if there are tags that override the update mode
    foreach ($bundle_xml->update_policy as $update_policy_xml) {
      // First check if there are conditions to be met in order for this update
      // policy to be applied.
      if (isset($update_policy_xml->conditions)) {
        // once there is a <conditions> element, at least one <conditions>
        // must evaluate to TRUE!
        $condition_is_true = FALSE;
        // one may express multiple <conditions> which are handled as OR
        foreach ($update_policy_xml->conditions as $conditions_xml) {
          // multiple <condition> elements are combined as AND
          foreach ($conditions_xml->condition as $condition_xml) {
            $true_false = self::parseUpdateCondition($condition_xml, $row_values);
            if ($true_false === FALSE) {
              // the condition was evaluated to false, so we can break here;
              // there may be subsequent <conditions> elements that need to be
              // checked
#dpm([$condition_xml->asXml(), $row_values], 'cond to false');
              continue 2; // next conditions_xml
            }
            // other values of $true_false:
            // - TRUE just means that we go to the next condition.
            // - NULL means that the condition is wrong, so we ignore it.
            // - an array is actually an entity query condition which we do not
            // support atm
          }
          // all <condition> elements evaluated to TRUE
          // => we can use this policy
          $condition_is_true = TRUE;
          break;  // last conditions_xml; proceed with if in next line
        }
#dpm([$condition_is_true, $conditions_xml->asXml(), $row_values], 'conds to');
        if (!$condition_is_true) {
          // the conditions aren't met => the update policy can't be applied
          continue;  // next update_policy_xml
        }
      }
      // Try to identify the set of entities that can be used for updating.
      // This is also done by conditions, this time for an entity query.
      // Note that identification is optional! But once there is an 
      // <identification> element, the conditions must match some entities
      // in order for the update policy to be applied.
      if (isset($update_policy_xml->identification)) {
        $ident_xml = $update_policy_xml->identification;
        // prepare an entity query with the given conditions
        $query = \Drupal::entityQuery('wisski_individual');
        // set the bundle we search for
        $query->condition('bundle', $bundleid, '=');
        $condition_count = 0;
        foreach ($ident_xml->condition as $condition_xml) {
          $condition = self::parseUpdateCondition($condition_xml, $row_values);
          if (is_array($condition)) {
            $query->condition($condition['field'], $condition['value'], $condition['operator']);
            $condition_count++;
          }
          elseif ($condition === FALSE) {
            // the condition was already evaluated to false
            continue 2;
          }
        }
        // besides the bundle there is no valid condition;
        // i.e. we cannot identify something
        if (!$condition_count) {
          continue;
        }
        // execute the entity query
        $matching_eids = $query->execute();
        // if multiple are found, take the first one (arbitrary!)
        $matching_eid = array_shift($matching_eids);
        // if there are no matches, this identification failed and we go to the 
        // next update policy statement
        if (!$matching_eid) {
#dpm($query, 'mismatch');
          continue; // next update_policy_xml
        }
      }

      // all conditions passed the test. we can set the mode and stop looping
      // over the update policies
      $mode = isset($update_policy_xml['mode']) ? (string) $update_policy_xml['mode'] : '';
#dpm([$update_policy_xml->asXml(), $mode, $matching_eid, $matching_eids, $row_values], 'upd'); 
      break;

    }

    if (!self::checkUpdateMode($mode)) {
      // default mode, see above
      $mode = 'new';
    }

    return [strtolower($mode), $matching_eid, $matching_eids];

  }


  public static function storeBundle($row, $bundle_xml, $bundleid, $delimiter, $trim) {

    list($update_mode, $update_eid, $further_eids) = self::evaluateUpdatePolicies($bundle_xml, $bundleid, $row);
    // $further_eids is not used currently
    // What to do with it?
    
    if ($update_mode == self::UPDATE_MODE_TAKE && $update_eid) {
      // the TAKE mode returns an existing entity as is or -- if not 
      // existent -- creates a new one according to the import declaration.
      // This is useful e.g. for cross-linking to entities created by previous
      // rows using more complex disambiguation criteria or where normal WissKI
      // disambiguation is cumbersome, e.g. when using entity reference fields.
      return $update_eid;
    }

    if ($update_mode == self::UPDATE_MODE_SKIP && $update_eid) {
      // the SKIP mode is like TAKE mode but instead of the entity ID it 
      // returns NULL when there is a matching entity.
      // This can be used for a top <bundle> tag instead of TAKE or to only 
      // make entity references to new entities.
      return NULL;
    }

    if ($update_mode == self::UPDATE_MODE_TAKE_EXISTING) {
      // the TAKE_EXISTING mode returns the matching entity or NULL. It does
      // not create new entities.
      // This mode can be used if an entity reference should only be created if
      // there is already a matching entity.
      // Note that $update_eid is exactly what we want to return!
      return $update_eid;
    }
    
    if ($update_mode == self::UPDATE_MODE_NEW) {
      // the NEW mode always creates a new entity regardless of whether we
      // found a matching one.
      // NEW mode acts like APPEND mode with no matching entity. Thus, we
      // unset the matching entity ID and hijack APPEND
      $update_eid = NULL;
      $update_mode = self::UPDATE_MODE_APPEND;
    }

    // gather field values
    // we have to distinguish normal field values and references
    $entity_fields = array();
    $field_modes = array();
    $found_something = false;
        
    foreach ($bundle_xml->bundle as $sub_bundle_xml) {
      // this could also be a field id of an entity reference
      // so we have to check the target.
      $fieldid = (string) $sub_bundle_xml['id'];
      // as the id attrib name only specifies the field id and the target 
      // bundle is guessed, we provide more unambiguous attributes
      // fieldId and bundleId that override the default id+autodetect
      if (isset($sub_bundle_xml['fieldId'])) {
        $fieldid = (string) $sub_bundle_xml['fieldId'];
      }
      if (isset($sub_bundle_xml['bundleId'])) {
        $targetbundleid = (string) $sub_bundle_xml['bundleId'];
      }
      else {
        // load the fieldconfig
        $fc = FieldConfig::load('wisski_individual.' . $bundleid. '.' . $fieldid);
        // get the target bundle id of the field config
        $targetbundleid = $fc->getSettings()['handler_settings']['target_bundles'];
        $targetbundleid = current($targetbundleid);
      }
      
      // cache the update mode
      $field_modes[$fieldid] = 
          isset($sub_bundle_xml['update_mode']) 
          ? (string) $sub_bundle_xml['update_mode'] 
          : $update_mode;

      // create the referenced entity and set the reference
      $ref_entity_id = self::storeBundle($row, $sub_bundle_xml, $targetbundleid, $delimiter, $trim);
      if ($ref_entity_id) {
        $entity_fields[$fieldid][] = $ref_entity_id;
        $found_something = true;
      }
    }
    
    foreach ($bundle_xml->field as $field_xml) {
      $fieldid = (string) $field_xml['id'];
      $local_delimiter = isset($field_xml['delimiter']) ? (string) $field_xml['delimiter'] : NULL;
      $local_trim = isset($field_xml['trim']) ? (string) $field_xml['trim'] : NULL;
      // cache the update mode
      $local_update_mode = isset($field_xml['update_mode']) ? (string) $field_xml['update_mode'] : $update_mode;
      $field_modes[$fieldid] = $local_update_mode;
      // the row column
      $field_row_id = (string) $field_xml->fieldname;
      // if there is something set on the local delimiters override the global ones
      // so the local ones can deactivate the global setting because isset 
      // reacts just on NULL and empty later on reacts on everything.
      $factual_delimiter = isset($local_delimiter) ? $local_delimiter : $delimiter;
      $factual_trim = isset($local_trim) ? $local_trim : $trim;
      // if there is a delimiter set and we find it
      if(!empty($factual_delimiter) && strpos($row[$field_row_id], $factual_delimiter)) {
        // separate the parts
        $field_row_array = explode($factual_delimiter, $row[$field_row_id]);
      
        // go through it, trim and add it.
        foreach($field_row_array as $one_part) {
          $entity_fields[$fieldid][] = ($trim) ? trim($one_part) : $one_part;
        }
      // else - do the normal way, just trim and add.
      } else {
        $entity_fields[$fieldid][] = ($trim) ? trim($row[$field_row_id]) : $row[$field_row_id];
      }
      // if we found something, we have to go on, otherwise we can skip later.
      if(!empty($row[$field_row_id]))
        $found_something = true;
    }

    // if absolutely nothing was stored - don't create an entity, as it will only
    // take time and produce nothing
    if(!$found_something) {
#dpm([$update_mode, $update_eid, $entity_fields], 'nothing:'.$bundleid);
      return $update_eid;
    }

    
    // the create new entity case:
    if ($update_eid == NULL) {
      // there is nothing to update so we just create a new entity
      // and return its ID.
      // we have to set the bundle manually
      $entity_fields["bundle"] = $bundleid;
      $entity = entity_create('wisski_individual', $entity_fields);
      if ($entity) {
        $entity->save();
      }
#dpm([$update_mode, $entity->id(), $entity_fields], 'tocreate:'.$bundleid);
      return $entity->id();
    }
#dpm([$update_mode, $update_eid, $entity_fields, $field_modes], 'toupdate:'.$bundleid);
    
    // the entity update case:
    $entity = entity_load('wisski_individual', $update_eid);
    if ($entity) {
      self::log()->debug('Update entity {eid} with fields {fields}', ['eid' => $update_eid, 'fields' => serialize($entity_fields)]);
      self::updateEntityFields($entity, $entity_fields, $field_modes);
      $entity->save();
      return $entity->id();
    }
    // should only happen if entity loading fails
    return NULL;
  }


  protected static function updateEntityFields($entity, $fields, $field_modes) {
    foreach ($fields as $name => $values) {
      $values = (array) $values;
      // @var $fil \Drupal\Core\Field\FieldItemListInterface
      $fil = $entity->get($name);
      $mode = $field_modes[$name];
      if ($mode == self::UPDATE_MODE_SKIP && !$fil->isEmpty()) {
        // we do not add anything if there is something
        continue;
      }
      if ($mode == self::UPDATE_MODE_REPLACE) {
        // delete all the existing values
        while (!$fil->isEmpty()) {
          $fil->removeItem(0);
        }
      }
      foreach ($values as $value) {
        $fil->appendItem($value);
      }
    }
  }
  
}                                                                                                                                                                                                                                                                          
