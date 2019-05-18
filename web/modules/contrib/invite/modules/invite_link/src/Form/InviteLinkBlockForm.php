<?php

namespace Drupal\invite_link\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\invite\Entity\Invite;

/**
 * Class InviteLinkBlockForm.
 *
 * @package Drupal\invite\Form
 */
class InviteLinkBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invite_link_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['create_invite'] = [
      '#type' => 'button',
      '#value' => $this->t('Create invite'),
      '#limit_validation_errors' => [['create_invite']],
      '#executes_submit_callback' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxReplaceInviteContainer',
        'wrapper' => 'invite',
        'method' => 'replace',
      ],
    ];

    $form['invite_containter'] = [
      '#type' => 'container',
      '#prefix' => '<div id="invite">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * Function to Ajax Replace.
   */
  public function ajaxReplaceInviteContainer($form, FormStateInterface $form_state) {
    $form['invite_container']['#markup'] = '<div id="invite">  <a href="/invite/accept/' . $form_state->getTemporaryValue('invite')->getRegCode() . '">Invite Link</a></div>';
    return $form['invite_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $invite_type = $form_state->getBuildInfo()['args'][0];

    $invite = Invite::create(['type' => $invite_type]);
    $invite->setPlugin('invite_link');
    $invite->save();

    $form_state->setTemporaryValue('invite', $invite);

  }

}
