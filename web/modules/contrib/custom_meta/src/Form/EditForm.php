<?php

/**
 * @file
 * Contains \Drupal\custom_meta\Form\EditForm.
 */

namespace Drupal\custom_meta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the custom meta tag edit form.
 */
class EditForm extends CustomMetaFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_meta_admin_edit';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPath($meta_uid) {
    return $this->metaStorage->load(array('meta_uid' => $meta_uid));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $meta_uid = NULL) {
    $form = parent::buildForm($form, $form_state, $meta_uid);

    $form['#title'] = $this->custom_meta['meta_attr_value'];
    $form['meta_uid'] = array(
      '#type' => 'hidden',
      '#value' => $this->custom_meta['meta_uid'],
    );
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#submit' => array('::deleteSubmit'),
    );
    return $form;
  }

  /**
   * Submits the delete form.
   */
  public function deleteSubmit(array &$form, FormStateInterface $form_state) {
    $url = new Url('custom_meta.delete', array(
      'meta_uid' => $form_state->getValue('meta_uid'),
    ));

    if ($this->getRequest()->query->has('destination')) {
      $url->setOption('query', $this->getDestinationArray());
      $this->getRequest()->query->remove('destination');
    }

    $form_state->setRedirectUrl($url);
  }

}
