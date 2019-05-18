<?php
namespace Drupal\guest_invite\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Implements an Guest Invite form
 */
class GuestInviteForm extends FormBase {
  /**
   * {@inheritdoc}
   */
	public function getFormId() {
		return 'guest_invite_form';
	}

	/**
     * {@inheritdoc}
    */
	public function buildForm(array $form, FormStateInterface $form_state) {
		//$form['#attached']['library'][] = 'core/drupal.dialog.ajax';
		$form['#prefix'] = '<div id="guest_invite_form">';
		$form['#suffix'] = '</div>';
		
		// The status messages that will contain any form errors.
		$form['status_messages'] = [
			'#type' => 'status_messages',
			'#weight' => -10,
		];
					
		$form['invite_email'] = [
                          '#type' => 'textfield',
                          '#title' => '',
                          '#name' => 'invite_email',
                          '#placeholder' => 'Email Address',
                          '#required' => TRUE,
                        ];
		
		$form['invite_message'] = [
                            '#type' => 'textarea',
                            '#title' => '',
                            '#name' => 'invite_message',
                            '#rows' => 4,
                            '#cols' => 50,
                            '#placeholder' => 'Message',
                            '#attributes' => ['class' => ['form-field']],
                            '#required' => TRUE,
		];
		
		$form_state->setCached(FALSE);
		$form['actions']['submit'] = [
			'#type' => 'submit',
			'#name' => 'submit',
			'#value' => $this->t('Send Invite'),
			'#attributes' => [
				'class' => [
					'use-ajax',
					'btn-primary',
				],
			],
			'#ajax' => [
				'callback' => '::submitModalFormAjax',        
				'event' => 'click',
			],
		];
		$form['#theme'] = 'guest_invite_form';
		return $form;
	}

	/**
		* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
		//Nothing to do
	}

	/**
     * {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$account = \Drupal::currentUser();
		$uid = $account->id();
		$message = !empty($form_state->getValue('invite_message')) ? $form_state->getValue('invite_message') : '';
		$email_ids = $form_state->getValue(['invite_email']);
		$emails = explode(',', $email_ids);
                $validEmails = array_map(function($val) {
                  return (filter_var($val, FILTER_VALIDATE_EMAIL)) ? $val : ''; 
                }, $emails);
                $validEmailString = implode(',', array_filter($validEmails));
                foreach ($emails as $key => $option) {
                                    $emailall[] = $option;

                            }

                $created = time();
		$created_by = $uid;
		
			$result = \Drupal::database()->insert('guest_invite')
			->fields([
				'email',
				'message',
				'created',
				'created_by',
			])
			->values([
				    $validEmailString,
				$message,
				$created,
				$created_by,
			]);
			$invite_id = $result->execute();
                        $newMail = \Drupal::service('plugin.manager.mail');
                         
                         $params['message']     = $message;
                         $params['email']       = $validEmailString;
                         $params['created']     = $created;
                         $params['created_by']  = $created_by;
                         
                        $newMail->mail('guest_invite', 'inviteMail', $validEmailString, 'en', $params, $reply = NULL, $send = TRUE);


      drupal_set_message($this->t('Guest Invite sent successfully.'));
  }

  public function postFormAjaxCallback(array &$form, FormStateInterface $form_state) {
    $form = ['messages' => ['#type' => 'status_messages']] + $form;
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#guest_invite_form', $form));
    return $response;
  }
	
  /**
   * AJAX callback handler that displays any errors or a success message.
   */
	public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
		$response = new AjaxResponse();
		// If there are any form errors, AJAX replace the form.
		if ($form_state->hasAnyErrors()) {
			$response->addCommand(new ReplaceCommand('#guest-invite-form', $form));
		} else {
			$response->addCommand(new OpenModalDialogCommand("Success!", 'Guest Invitation has been sent successfully.'));
      $form = ['messages' => ['#type' => 'status_messages']];
      $response->addCommand(new ReplaceCommand('#guest-invite-message', $form));
      //$response->addCommand(new CloseModalDialogCommand());
		}
		return $response;
	}
}
