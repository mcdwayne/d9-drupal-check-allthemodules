<?php

/**
 * @file
 * Contains \Drupal\comment_deletion\AlertController.
 */

namespace Drupal\comment_deletion\Controller;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

/**
 * Controller for Alert to Administrator.
 */
class DeleteControllerform extends ConfigFormBase {

  /**
   * Implement comment_deletion function.
   */
    public function getFormId() {
    return 'comment_deletion_settings';
  } 
  
 /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'comment_deletion.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('comment_deletion.settings');
    $form['comment_delete_types'] = array(
	  '#type' => 'select',
      '#multiple' => TRUE,
	  '#title' => $this->t('Currently enabled content types'),
	  '#description' => $this->t('Set types of nodes for which comments needs to be deleted. <strong>Note</strong> that comments may stay open for the  content type.'),
	  '#options' => node_type_get_names(),
	  '#default_value' => $config->get('comment_delete_types'),
	  '#empty_option' => '-None-',
	  '#weight' => -20,
	);
	if ($config->get('comment_delete_types')) {
	  $url = Url::fromRoute('comment_deletion_batch.description');
      $internal_link = \Drupal::l(t('Start Batch'), $url);
      $form['batch_link'] = array(
        '#type' => 'markup',
        '#markup' => $internal_link,
        '#prefix' => t('<strong>Note:</strong> Click&nbsp;'),
        '#suffix' => t('&nbsp;link after save configuration for execution of delete comment.'),
        '#url' => Url::fromRoute('comment_deletion_batch.description'),
		'#weight' => 10000,
      );
    }
    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('comment_deletion.settings')
      ->set('comment_delete_types', $form_state->getValue('comment_delete_types'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
