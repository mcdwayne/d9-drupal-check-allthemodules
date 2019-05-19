<?php

/**
 * @file
 * Contains \Drupal\smallads_import\ImportSmallads.
 *
 */
namespace Drupal\smallads_import;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use GuzzleHttp;

class ImportSmallads extends \Drupal\Core\Form\FormBase {

  private $database;

  /**
   * @var \GuzzleHttp
   */
  private $client;

  /**
   * @todo injections
   */
  function __construct() {
    $this->database = \Drupal::database();
    $this->client = \Drupal::httpClient();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'smallads_import_csv_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $type = 'offer';

    $form['type'] = [
      '#title' => $this->t('Small ad type'),
      '#type' => 'radios',
      '#options' => [
        //@todo make this dynamic on the small ad types
        'offer' => $this->t('Offer'),
        'want' => $this->t('Want')
      ],
      '#required' => TRUE,
      '#weight' => 1
    ];
    $form['which'] = [
      '#title' => $this->t('Data source'),
      '#type' => 'radios',
      '#options' => [
        'inline' => $this->t('Inline'),
        'url' => $this->t('remote')
      ],
      '#default_value' => 'inline',
      '#required' => TRUE,
      '#weight' => 2
    ];

    $placeholder = '';
    foreach (csv_headers($type) as $col) {
      $placeholder[] = '"'.$col.'"';
    }
    $form['data'] = array (
      '#title' => 'paste data',
      '#type' => 'textarea',
      '#default_value' => $form_state->getValue('data'),
      '#placeholder' => implode(', ',  $placeholder),
      '#rows' => 15,
      '#weight' => 1,
      '#states' => [
        'visible' => [
          ':input[name="which"]' => ['value' => 'inline']
        ]
      ],
      '#weight' => 3
    );

    $form['url'] = [
      '#title' => $this->t('Url'),
      '#type' => 'url',
      '#default_value' => $form_state->getValue('url'),
      '#states' => [
        'visible' => [
          ':input[name="which"]' => ['value' => 'url']
        ]
      ],
      '#weight' => 3
    ];
    $form['test'] = array(
      '#title' => t('Test mode'),
      '#type' => 'checkbox',
      '#default_value' => TRUE,
      '#weight' => 3
    );
    $form['submit'] = array (
      '#type' => 'submit',
      '#value' => 'import',
      '#weight' => 4
    );
    $form['#redirect'] = \Drupal\Core\Url::fromRoute('entity.smallad.collection');
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getvalues('test')) {
      $form_state->setErrorByName('', t('Submission blocked: Test Mode in ON'));
    }

    $rows = $this->loadCsv($form_state);
    $incoming_terms = [];

    foreach ($rows as $rownum => $row) {
      //check that all the users are valid
      if (!$this->ow_import_row_get_user($row)) {
        $form_state->setErrorByName('data', t('Unknown user in row @num: @row', array('@num' => $rownum + 1, '@row' => print_r($row, 1))));
      }
      foreach (array('created', 'changed', 'expires') as $time) {
        if (array_key_exists($time, $row) && !is_numeric($row[$time])) {
          $int = strtotime($row[$time]);
          if (!$int) {
            $form_state->setErrorByName('data', t('invalid @type on row @num', array('@type' => $time, '@num' => $rownum + 1)));
          }
        }
      }
      if (array_key_exists('status', $row)) {
        if (!in_array($row['status'], array('', '0', '1'))) {
          \Drupal::messenger()->addStatus('status columnn must be 1 or 0 in line $rownum');
        }
      }
      //check for new categories that will need creating
      if (array_key_exists('categories', $row) && strlen($row['categories'])) {
        $incoming_terms = array_merge($incoming_terms, explode('|', strtolower($row['categories'])));
      }
    }
    return;//


    foreach ($incoming_terms as $key => $term) {
      $incoming_terms[$key] = trim($term);
    }
    $all_terms = db_query("SELECT LOWER(name) FROM {taxonomy_term_data}")->fetchCol();
    $new_terms = array_diff(array_unique($incoming_terms), $all_terms);

    if (count($new_terms)) {
      $this->messenger()->addStatus(
        t(
          'The following new terms would be created: @terms',
          array('@terms' => implode(', ', $new_terms))),
        'warning'
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $rows = $this->loadCsv($form_state);

    //get the terms so we can lookup the ids
    $all_terms = db_query("
      SELECT d.tid, d.vid, d.name, LOWER(d.name) as term_name, v.name, v.machine_name
      FROM {taxonomy_term_data} d
      LEFT JOIN {taxonomy_vocabulary} v ON d.vid = v.vid
      WHERE d.vid > 0 AND v.machine_name = '".SMALLAD_CATEGORIES."'")
    ->FetchAllAssoc('term_name');
    $imported = 0;

    foreach (ow_getcsv($form_state['values']['data'], $form_state) as $row) {
      $account = ow_import_row_get_user($row);
      $term_names = drupal_explode_tags($row['categories']);
      $taxonomy = array();
      foreach (array_filter($term_names) as $name) {
        if (!array_key_exists(strtolower($name), $all_terms)) {
          $new_term = (object)array(
            'vid' => taxonomy_vocabulary_machine_name_load($form_state['values']['vocab'])->vid,
            'name' => $name,
            'description' => '',
            'weight' => 0,
            'format' => 'plain_text'
          );
          taxonomy_term_save($new_term);
          $new_term->machine_name = $form_state['values']['vocab'];
          $all_terms[strtolower($name)] = $new_term;
        }
        $term = $all_terms[strtolower($name)];
        $taxonomy[$term->machine_name][LANGUAGE_NONE][]['tid'] = $term->tid;
      }
      if (isset($row['expires'])) {
        $expires = is_numeric($row['expires']) ? $row['expires'] : strtotime($row['expires']);
      }

      $node = new stdClass();
      $node->title = $row['title'];
      $node->type = 'proposition';
      $node->status = array_key_exists('status', $row) ? intval($row['status']) : 1;
      $node->language = Language::getId();
      $node->uid = $account->uid;
      $node->name = $account->name;
      $node->want = $form_state['values']['want'];
      $node->end = isset($expires) ? $expires : 0;
      if (array_key_exists('created', $row)) {
        $node->created = is_numeric($row['created']) ? $row['created'] : strtotime($row['created']);
      }

      if (array_key_exists('body', $row) && strlen($row['body'])) {
        $node->body[LANGUAGE_NONE][0] = array(
          'value' => str_replace(array("\r", "\r\n", "\n"), "\n", $row['body']),
          'format' => 'plain_text'
        );
      }
      foreach ($taxonomy as $vocab_name => $field) { //assumes the fieldname is the same as the vocab name
        $node->{$vocab_name} = $field;
      }
      node_save($node);
      if (array_key_exists('created', $row)) {
        db_query(
          "UPDATE {node} SET changed = :changed where nid = $node->nid",
          array('changed' => is_numeric($row['created']) ? $row['created'] : strtotime($row['created']))
        );
      }
      $imported++;
    }
    $this->messenger()->addStatus($this->t("imported @count nodes", array('@count' => $imported)));

  }


  private function ow_import_row_get_user(array $row) {
    if (array_key_exists('uid', $row) && is_numeric($row['uid'])) {
      $account = User::load($row['uid']);
    }
    elseif (array_key_exists('mail', $row)) {
      $account = User::loadbyMail($row['mail']);
    }
    if ($account->isAuthenticated()){
      return $account;
    }
  }

  private function ow_getcsv($string, $form_state) {

  }


  function loadCsv($form_state) {
    if ($form_state->getValue('which') == 'inline' ) {
      $data = $form_state->getValue('data');
    }
    else {
      $data = $this->client->get($form_state->getValue('url'))->getBody()->getContents();

    }
    $firstline = array_shift($data);

    foreach ($this->ow_get_fields_from_stringline($firstline) as $key => $field) {
      $headers[] = strtolower(trim($field));
    }
    debug($headers);
    foreach($data as $key => &$row) {
      $nextrow = str_getcsv($row);
      if (count($nextrow) != count($headers)) {
        $form_state->setErrorByName('data', t('Number of fields does not match header in row @num',  array('@num' => $key + 1)));
        continue;
      }
      $row = array_combine($headers, str_getcsv($row));
    }
    return $data;
  }

  private function ow_get_fields_from_stringline($string) {
    $fields = explode(',', $string);
    foreach ($fields as $key => $field) {
      $fields[$key] = trim($field, '" ');
    }
    return $fields;
  }


}
