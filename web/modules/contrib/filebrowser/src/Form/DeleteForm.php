<?php

namespace Drupal\filebrowser\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\RedirectCommand;

class DeleteForm extends ConfirmFormBase {

  /**
   * @var int
   */
  protected $queryFid;

  /**
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Common methods
   * @var \Drupal\filebrowser\Services\Common
   */
  protected $common;

  /**
   * Validator methods
   *
   * @var \Drupal\filebrowser\Services\FilebrowserValidator
   */
  protected $validator;

  /**
   * Filebrowser object holds specific data
   *
   * @var \Drupal\filebrowser\Filebrowser
   */
  protected $filebrowser;

  /**
   * @var array
   * Array of fid of files to delete
   */
  protected $itemsToDelete;

  /**
   * ConfirmForm constructor.
   */
  public function __construct() {
    $this->validator = \Drupal::service('filebrowser.validator');
    $this->common = \Drupal::service('filebrowser.common');
    $this->itemsToDelete = null;
  }

  public function getFormId() {
    return 'filebrowser_delete_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $nid = null, $query_fid = 0, $fids_str =  null, $ajax = null) {
    $this->node = Node::load($nid);
    $this->queryFid = $query_fid;
    $this->filebrowser = $this->node->filebrowser;
    $fids = explode(',', $fids_str);

    // $this->error = false;

    // This flag indicates that a folder has been selected for deletion.
    $folder_selected = false;
    $files = $this->common->nodeContentLoadMultiple($fids);
    foreach ($files as $fid => $file) {
      // Additional data.
      $file['type'] = unserialize($file['file_data'])->type;
      $file['full_path'] = $this->validator->encodingToFs($this->filebrowser->encoding, $this->validator->getNodeRoot($this->filebrowser->folderPath . $file['path']));
      $file['display_name'] = $this->validator->safeBaseName($file['full_path']);

      // Store item data.
      $this->itemsToDelete[$fid] = $file;
    }

    // Compose the list of files being deleted.
    $list = '<ul>';
    foreach ($this->itemsToDelete as $item) {
      $list .= '<li>';
      if ($item['type'] == 'dir') {
        $folder_selected = true;
        $list .= '<b>' . $item['display_name'] . '</b>';
      }
      else {
        $list .= $item['display_name'];
      }
      $list .= '</li>';
    }
    $list .= '</ul>';

    if ($ajax) {
      $form['#attributes'] = [
        'class' => [
          'form-in-slide-down'
        ],
      ];
      // Add a close slide-down-form button
      $form['close_button'] = $this->common->closeButtonMarkup();
    }
    $form['items'] = [
      '#type' => 'item',
      '#title' => $this->t('Items being deleted'),
      '#markup' => $list
    ];

    // If at least a folder has been selected, add a confirmation checkbox.
    if ($folder_selected) {
      $form['confirmation'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Confirm deletion of selected <b>folders</b> and all of their content.'),
        '#default_value' => false,
      ];
    }
    else {
      // No confirmation needed, we'll add a "fake" field.
      $form['confirmation'] = [
        '#type' => 'value',
        '#value' => TRUE,
      ];
    }
    $form = parent::buildForm($form, $form_state);
    $form['actions']['cancel']['#attributes']['class'][] = 'button btn btn-default';
    if($ajax) {
      $form['actions']['submit']['#attributes']['class'][] = 'use-ajax-submit';
      $this->ajax = true;
    }
    return $form;
  }

  public function getQuestion() {
    return $this->t('Are you sure you want to delete the following items?');
  }

  public function getCancelUrl() {
    return $this->node->urlInfo();
  }

  public function getDescription() {
    return $this->t('this action can not be undone.');
  }

  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

        if ($this->error) {
             // Create an AjaxResponse.
            $response = new AjaxResponse();
            // Remove old events
            $response->addCommand(new RemoveCommand('#filebrowser-form-action-error'));
            $response->addCommand(new RemoveCommand('.form-in-slide-down'));
            // Insert event details after event.
            $response->addCommand(new AfterCommand('#form-action-actions-wrapper', $form));
            // $response->addCommand(new AfterCommand('#form-action-actions-wrapper', $html));
            $response->addCommand(new AlertCommand($this->t('You must confirm deletion of selected folders.')));
            $form_state->setResponse($response);
          } else {
            foreach ($this->itemsToDelete as $item) {
                $data = unserialize($item['file_data']);
                $success = file_unmanaged_delete_recursive($data->uri);
                if ($success) {
                    // invalidate the cache for this node
                    Cache::invalidateTags(['filebrowser:node:' . $this->node->id()]);
                  }
        else {
                    drupal_set_message($this->t('Unable to delete @file', ['@file' => $data->uri]), 'warning');
                  }
      }
          $route = $this->common->redirectRoute($this->queryFid, $this->node->id());
                if($this->ajax) {
                    $response_url = Url::fromRoute($route['name'], $route['node'], $route['query']);
                    $response = new AjaxResponse();
                    $response->addCommand(new RedirectCommand($response_url->toString()));
                    $form_state->setResponse($response);
                  } else {
                    $form_state->setRedirect($route['name'], $route['node'], $route['query']);
      }
    }
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check if the confirmation checkbox has been checked.
    if (empty($form_state->getValue('confirmation'))) {
      // commented out original code below
      // https://www.drupal.org/project/filebrowser/issues/2955654
      //  $this->common->debugToConsole('validate');
      //  $form_state->setErrorByName('confirmation', $this->t('You must confirm deletion of selected folders.'));
      $this->error = true;
    }
    // Check if the confirmation checkbox has been checked.
    parent::validateForm($form, $form_state);
  }

}