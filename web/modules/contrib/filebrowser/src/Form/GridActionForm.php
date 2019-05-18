<?php

namespace Drupal\filebrowser\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\filebrowser\Services\Common;
use Drupal\filebrowser\Services\FormHelper;
use Drupal\node\NodeInterface;

  /**
   * Class GridActionForm.
   *
   * @package Drupal\filebrowser\Form
   */
class GridActionForm extends FormBase {

  /**
   * @var string
   * The relative root for this file listing.
   * The complete location of a file is determined by
   * folder_path + relativeRoot = path:
   * local//: path_to_folderpath/folder1/folder2/file.txt
   * In this example "folder1/folder2" is the relativeRoot
   */
  protected $relativeRoot;

  /**
   * @var array
   * Array of allowed actions by this user on this node.
   * Used to build form buttons and link. Array is created by
   * Common::allowedActions
   */
  protected $allowedActions;

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
   * Node holding this form
   * @var NodeInterface $node
   */
  protected $node;

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
   * @var FormHelper
   */
  protected $helper;

  /**
   * @var boolean $error
   * Set to true when no items are selected on the form
   */
  protected $error;

  public function getFormId() {
    return 'grid_action_form';
  }

  /**
   * @param array $params
   * Required data to build the for:
   * Associative array keyed 'actions', 'node', 'data'
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $items = null, $params = null) {
    $this->common = \Drupal::service('filebrowser.common');
    $this->helper = \Drupal::service('form.helper');
    $this->node = $params['node'];
    $this->nid = $this->node->id();
    $this->relativeFid = empty($params['data']['fid']) ? 0 : $params['data']['fid'];

    $form = [];
    $this->helper->initForm($form, $params['node']);
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'views/views.module';
    $column_width = round(100 / $items['options']['columns']) - 1;

    // Create the form action elements
    // If user has permissions that needs a button or checkbox then has_actions will be true.
    $has_actions = !empty($params['actions']);
    if ($has_actions) {
      // Create the bar containing the action buttons
      $this->helper->createActionBar($form, $params['actions'], $this->relativeFid);

      // Create a "Select all" checkbox
      $form['select_all'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Select all'),
        '#title_display' => 'before',
        '#ajax' => [
          'callback' => [
            $this,
            'selectAll'
          ],
          'progress' => 'none',
        ],
      ];
    }

    // main container
    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => ['filebrowser-checkboxes-container'],
        'class' => ['horizontal', 'clearfix',],
      ],
    ];

    // rows (table-row)
    foreach($items['grid_items'] as $row_nr => $row) {
      $row_name = 'row_' . $row_nr;
      $form['container'][$row_name] = [
        '#type' => 'container',
        '#options' => '',
        '#attributes' => [
          'class' => [
             'filebrowser-grid-row'
          ],
        ],
      ];

      //columns (td)
      foreach($row['row'] as $col_nr => $content) {

        $col_name = 'col_' . $col_nr;
        $form['container'][$row_name][$col_name] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [Common::FILEBROWSER_GRID_CONTAINER_COLUMN_CLASS],
            'width' => $column_width,
          ],
        ];
        if ($has_actions) {
          $form['container'][$row_name][$col_name][$content['content']['file']->fid] = [
            '#type' => 'checkbox',
            '#title' => '',
            '#attributes' => ['class' => ['filebrowser-checkbox',],],
            '#field_suffix' => $content['content']['grid'],
          ];
        }
        else {
          $form['container'][$row_name][$col_name][$content['content']['file']->fid] = [
            '#markup' => render($content['content']['grid']),
          ];
        }
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // All submit button needs selected items. If noting selected generate form_error
    $element = $form_state->getTriggeringElement();
    $this->fids = $this->getFids($form, $form_state);
    if (empty($this->fids)) {
      $form_state->setError($element, $this->t('You didn\'t select any item'));
    }
  }

  public function ajaxValidate(&$form, FormStateInterface $form_state) {
    // All submit button needs selected items. If noting selected generate form_error
    $this->fids = $this->getFids($form, $form_state);
    if (empty($this->fids)) {
      //set the error to the submit function:
      \Drupal::logger('filebrowser')->notice('Error no items selected');
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
    $rows = $form_state->getValue('container');
    // debug($values);
    foreach ($rows as $row) {
      foreach ($row as $item) {
        foreach ($item as $fid => $checked) {
          if ($checked) {
            $fids[] = $fid;
          }
        }
      }
    }
    $selected_fids = empty($fids) ? null : implode(',', $fids);
    return $selected_fids;
  }

  protected function createLink($action) {
    $route = 'filebrowser.action';
    $link_options = [
      'attributes' => [
        'class' => [
          'use-ajax',
          'form-action-link',
          'button'
        ]]];
    return [
      '#markup' => Link::createFromRoute($action['title'], $route, [
        'op' => $action['operation'],
        'method' => 'nojs',
        'nid' => $this->nid,
        'query_fid' => $this->relativeFid,
      ],
        $link_options)->toString(),
    ];
  }

  public function createButton($action) {
    return [
      '#type' => 'submit',
      '#return_value' => $action['operation'],
      '#value' => $action['title'],
      '#name' => $action['operation'],
      '#title' => $action['title'],
      '#attributes' => ['class' => ['use-ajax-submit',],],
      '#validate' => [[$this, 'ajaxValidate']],
      // We do not specify #action so the normal submit function is used
    ];
  }

  public function createNormalButton($action) {
    return [
      '#type' => 'submit',
      '#return_value' => $action['operation'],
      '#value' => $action['title'],
      '#name' => $action['operation'],
      '#title' => $action['title'],
    ];
  }

  public function selectAll($form, FormStateInterface $form_state) {
    $value = $form_state->getValue('select_all');
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('.filebrowser-checkbox', 'val', [['checked', $value]]));
    return $response;
  }

}