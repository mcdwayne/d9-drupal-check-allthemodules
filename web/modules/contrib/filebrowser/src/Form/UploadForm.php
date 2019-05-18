<?php

namespace Drupal\filebrowser\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class UploadForm extends FormBase {

  /**
   * @var int
   */
  protected $queryFid;

  /**
   * @var string
   */
  protected $relativeRoot;
  /**
   * @var NodeInterface
   */
  protected $node;

  /**
   * @var integer
   */
  protected $nid;

  /**
   * @var \Drupal\filebrowser\Services\Common
   */
  protected $common;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nid= null, $query_fid = null, $fids = null, $ajax = null) {
    $this->common = \Drupal::service('filebrowser.common');
    $this->relativeRoot = $this->common->relativePath($query_fid);
    $this->node = Node::load($nid);
    $this->queryFid = $query_fid;
    $this->nid = $nid;
    $accepted = $this->node->filebrowser->accepted;

    // if this form is opened by ajax add a close link.
    if ($ajax) {
      $form['#attributes'] = [
        'class' => [
          'form-in-slide-down'
        ],
      ];
      $form['close'] = $this->common->closeButtonMarkup();
    }

    $form['u_file'] = [
      '#title' => $this->t('Upload file'),
      '#type' => 'filebrowser_managed_file',
      '#description' => $this->t('File types accepted: @accepted', ['@accepted' => $accepted]) . '<br>' . $this->t('You can upload multiple files.'),
      '#upload_validators' => [
        'file_validate_extensions' => [$this->node->filebrowser->accepted],
      ],
      '#upload_location' => $this->node->filebrowser->folderPath . $this->relativeRoot, '#progress_indicator' => 'bar', '#progress_message' => $this->t('Please wait...'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Upload'),
    ];
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  // all required validation is done in filebrowser_managed_file form element
  }

  /**
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // While we are using the managed_file widget (for convenience), we don't
    // want to save the file in the file_managed table, so we will delete it
    // here.
    // A bit hackish, but it works.

    $file_ids = $form_state->getValue('u_file');
    if (count($file_ids)) {
      $success = \Drupal::service('filebrowser.storage')->genericDeleteMultiple('file_managed', 'fid', join(',', $file_ids));
      if ($success) {
        drupal_set_message($this->t("Your filebrowser upload is completed successfully!"));
      }
      else {
        drupal_set_message($this->t('Your upload completed successfully, but file_managed clean-up failed', 'error'));
      }
    }
    // invalidate the cache for this node
    Cache::invalidateTags(['filebrowser:node:' . $this->nid]);
    $route = $this->common->redirectRoute($this->queryFid, $this->node->id());
    $form_state->setRedirect($route['name'], $route['node'], $route['query']);
  }

}
