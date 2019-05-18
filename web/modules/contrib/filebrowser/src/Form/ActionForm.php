<?php


namespace Drupal\filebrowser\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Class ActionForm.
 *
 * @package Drupal\filebrowser\Form
 */
class ActionForm extends FormBase {

  /**
   * @var string
   * The relative root for this file listing.
   * The complete location of a file is determined by
   * folder_path + relativeRoot = path:
   * local//: path_to_folderpath/folder1/folder2/file.txt
   * In this example "folder1/folder2" is the relativeRoot
   */
  // protected $relativeRoot;

  /**
   * @var int
   * fid of the relative root
   */
  protected $relativeFid;

  /**
   * @var string
   *String containing Id's of the selected files
   */
  protected $fids;

  /**
   * @var integer $nid
   */
  protected $nid;

  /**
   * {@inheritdoc}
   */

  /**
   * @var \Drupal\filebrowser\Services\Common
   */
  protected $common;

  /**
   * @var \Drupal\filebrowser\Services\FormHelper
   */
  protected $helper;

  /**
   * @var boolean $error
   * Set to true when no items are selected on the form
   */
  protected $error;

  public function getFormId() {
    return 'base_action_form';
  }

  /**
   * @param array $params
   * Array with filebrowser data keyed:
   * 'header': the header to build the table select
   * 'rows' : array of file info to display
   * 'actions': Form actions that are allowed for current user
   *  'dbFileList': all the ifo about the files:
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $params = null) {
    /** @var NodeInterface $node */
    $this->error = false;
    $node = $params['node'];
    $actions = $params['actions'];
    $this->nid = $node->id();
    $this->relativeFid = empty($params['dbFileList']['data']['fid']) ? 0 : $params['dbFileList']['data']['fid'];
    $this->common = \Drupal::service('filebrowser.common');
    $this->helper = \Drupal::service('form.helper');

    // Initiate the form according default filebrowser requirements
    $form = [];
    $this->helper->initForm($form, $node);

    // Create the form action button and links (Upload, Rename etc.)
    $this->helper->createActionBar($form, $actions, $this->relativeFid);

    // prepare the $rows array for $options
    $options = [];
    foreach ($params['rows'] as $row) {
      $fid = $row['fid'];
      unset($row['fid']);
      foreach($row as $key => $value) {
        $options[$fid][$key] = [
          'data' => $value,
        ];
      }
    }

    $form['table'] = [
      '#type' => 'tableselect',
      '#tableselect' => true,
      '#header' => $params['header'],
      '#options' => $params['rows'],
      '#multiple' => TRUE,
      '#empty' => $this->t('This directory is empty.'),
    ];
    // search $options array to find the "Up Dir" line and de-activate the checkbox
    foreach ($params['rows'] as $key => $option) {
      if (empty($option['fid'])) {
        $form['table'][$key]['#disabled'] = true;
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // \Drupal::logger('filebrowser')->notice('NORMAL ACTION FORM VALIDATE');
    // All submit button needs selected items. If noting selected generate
    // form_error
    $element = $form_state->getTriggeringElement();
    $this->fids = $this->getFids($form, $form_state);
    if (empty($this->fids)) {
      $form_state->setError($element, $this->t('You didn\'t select any item'));
    }
  }

  public function ajaxValidate(&$form, FormStateInterface $form_state) {
    // \Drupal::logger('filebrowser')->notice('AJAX FORM VALIDATE');
    // All submit button needs selected items. If noting selected generate form_error
    //$element = $form_state->getTriggeringElement();
    $this->fids = $this->getFids($form, $form_state);
    if (empty($this->fids)) {
      //set the error to the submit function:
      // \Drupal::logger('filebrowser')->notice('Error no items selected');
      $this->error = true;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No items are selected and we have to display an error. We have to do
    // it here for the ajax enabled buttons. For the normal submit buttons
    // this will be handled by the form validation method.
    // \Drupal::logger('filebrowser')->notice('SUBMIT FORM');
    if ($this->error) {
      // set the error in the slide down window
      $form_state->setRedirect('filebrowser.no_items_error');
    }
    else {
      $op = $form_state->getTriggeringElement()['#return_value'];
      $route_param = $this->common->routeParam($this->nid, $this->relativeFid);
      $route_param['method'] = 'ajax';
      $route_param['op'] = $op;
      $route_param['fids'] = $this->fids;
      $route = 'filebrowser.action';

      // NB: links are not submitted and do not pass through this submit function
      $form_state->setRedirect($route, $route_param);
    }
  }

  protected function getFids($form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('table') as $key => $value) {
      if ($value) {
        $fids[] = $form['table']['#options'][$key]['fid'];
      }
    }
    $selected_fids = empty($fids) ? null : implode(',', $fids);
    return $selected_fids;
  }

}
