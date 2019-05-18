<?php

namespace Drupal\monster_menus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\MMImportExportException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImportTreeForm extends FormBase {
  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_export_form';
  }

  /**
   * Constructs an object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    module_load_include('inc', 'monster_menus', 'mm_import_export');
    if (mm_module_exists('node_export')) {
      $form['include_nodes'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Include page contents (nodes)'),
        '#default_value' => isset($form_state['values']['include_nodes']) ? $form_state['values']['include_nodes'] : FALSE,
      );
    }
    else {
      $form['include_nodes'] = array(
        '#markup' => $this->t('<p>To import page contents (nodes), the <a href=":link">node_export</a> module is required. Only pages will be imported.</p>', array(':link' => Url::fromUri('https://drupal.org/project/node_export')->toString())),
      );
    }
    $form['mode'] = array(
      '#type' => 'radios',
      '#options' => array(
        Constants::MM_IMPORT_ADD    => $this->t('Add-only: Don\'t change existing nodes or pages, just add anything new'),
        Constants::MM_IMPORT_UPDATE => $this->t('Update: Overwrite existing nodes and pages, if different; does not modify any pre-existing nodes/pages not part of the import'),
        Constants::MM_IMPORT_DELETE => $this->t('Delete first: Move any existing nodes and pages to a recycle bin before importing')
      ),
      '#default_value' => 'add',
    );

    $form['mmtid'] = array(
      '#type' => 'mm_catlist',
      '#mm_list_min' => 1,
      '#mm_list_max' => 1,
      '#mm_list_selectable' => Constants::MM_PERMS_WRITE,
      '#title' => $this->t('Start at:'),
      '#required' => TRUE,
      '#description' => $this->t('Import the tree as a child of this location.'),
      '#default_value' => $form_state->getValue('mmtid'),
    );

    $form['test'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Test only'),
      '#description' => $this->t('If checked, test the import but do not actually make any changes.'),
      '#default_value' => FALSE,
    );

    if (isset($form_state->getStorage()['mm_import_result'])) {
      $form['result'] = array(
        '#type' => 'details',
        '#title' => t('Import Results'),
        '#open' => TRUE,
      );
      $results = $form_state->getStorage()['mm_import_result'];
      // Note: These strings are translated later on. Do not translate here.
      foreach (array('errors' => 'Errors (@count)', 'pages' => 'Pages (@count)', 'nodes' => 'Nodes (@count)', 'groups' => 'Groups (@count)') as $type => $desc) {
        if (isset($results[$type]) && $results[$type]) {
          $rows = $this->importItemList($results[$type]);
          $form['result'][$type] = array(
            '#type' => 'details',
            '#title' => $this->t($desc, array('@count' => count($rows))),
            '#open' => $type == 'errors',
            array(
              '#theme' => 'item_list',
              '#items' => $rows,
            ),
          );
        }
      }
      if (!Element::children($form['result'])) {
        $form['result'][] = array(
          '#markup' => $form_state['values']['test'] ? $this->t('No changes would have occurred.') : $this->t('No changes occurred.'),
        );
      }
    }

    $form['code'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Code to Import'),
      '#required' => TRUE,
      '#rows' => 10,
    );

    $form['actions'] = array(
      '#type' => 'actions',
      'submit' => array(
        '#type' => 'submit',
        '#value' => $this->t('Import'),
        '#button_type' => 'primary',
      )
    );

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    module_load_include('inc', 'monster_menus', 'mm_import_export');
    $vals = $form_state->getValues();
    if (!mm_content_user_can($vals['mmtid'], Constants::MM_PERMS_WRITE)) {
      $form_state->setErrorByName('mmtid', t('You do not have permission to write to the starting location.'));
    }
    else if ($vals['mode'] == Constants::MM_IMPORT_DELETE && $vals['test']) {
      $form_state->setErrorByName('mode', t('The "Test only" option cannot be used with "Delete first" mode.'));
    }
    else {
      try {
        $stats = array('suppress_errors' => TRUE);
        mm_import($vals['code'], !empty($vals['mmtid']) ? key($vals['mmtid']) : NULL, $vals['mode'], $vals['test'], !empty($vals['include_nodes']), $stats);
        $form_state->setStorage(['mm_import_result' => $stats]);
      }
      catch (MMImportExportException $e) {
        \Drupal::messenger()->addError(t('An error occurred: @error', array('@error' => $e->getTheMessage())));
      }
      $form_state->setRebuild(TRUE);
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  private function importItemList($array) {
    $out = array();
    foreach ($array as $key => $msg) {
      if (isset($msg['message'])) {
        $out[] = t($msg['message'], $msg['vars']);
      }
      else {  // Nested array, for groups/pages
        $out = array_merge($out, $this->importItemList($msg));
      }
    }
    return $out;
  }

}
