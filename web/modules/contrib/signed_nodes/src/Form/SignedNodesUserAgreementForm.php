<?php
/**
 * @file
 * Contains \Drupal\signed_nodes\Form\SignedNodesForm.
 **/
   
namespace Drupal\signed_nodes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SignedNodesUserAgreementForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'signed_node_user_agreement_form';
  }
   
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $snid = NULL) {

    $this->id = $snid;

    if ($snid == NULL) {
      $node = \Drupal::routeMatch()->getParameter('node');
      $account = \Drupal::currentUser();
      $uid = $account->id();
      $snid = db_query("SELECT snid FROM {signed_nodes} where nid = :nid and year = :year", array(':nid' => $nid, ':year' => date('Y')))->fetchField();
    }
    // Return array of Form API elements.

    if ($snid) {
    $agreement = db_query("SELECT agreement_message FROM {signed_nodes} where snid = :snid", array(':snid' => $snid))->fetchField();
    $form['snid'] = array(
      '#type' => 'hidden',
      '#value' => $snid,
    );
    }
    $form['agreementfieldset'] = array(
      '#type' => 'fieldset',
      '#title' => t('Node Agreement'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['agreementfieldset']['agreement'] = array(
      '#type' => 'checkbox',
      '#required' => TRUE,
      '#title' => t($agreement),
    );
    $form['agreementfieldset']['button'] = array(
        '#value' => 'submit',
        '#type' => 'submit',
      );
    $form['#cache']['max-age'] = 0;
    \Drupal::service('page_cache_kill_switch')->trigger();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('agreement')) {
      $agreement = $form_state->getValue('agreement');
    }
    if ($agreement == 0) {
      $form_state->setErrorByName('agreementfieldset', t('Please check the checkbox to agree to this node agreement.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->id) {
      $connection = \Drupal::database();
      $account = \Drupal::currentUser();
      $uid = $account->id();
      $user = \Drupal\user\Entity\User::load($uid);

      $insert = $connection->insert('signed_nodes_user')
        ->fields([
          'snid' => $this->id,
          'uid' => $uid,
        ])
        ->execute();

      $message = t('User %name agreed to the terms of the SignedNode Agreement %snode', array('%name' => $user->get('name')->value, '%snode' => $this->id));
      \Drupal::logger('signed_nodes_user')->notice($message);

      drupal_set_message(t('You have agreed to the terms of this post.'), 'status');
    }
  }
}