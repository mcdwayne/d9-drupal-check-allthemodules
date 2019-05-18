<?php
/**
* @file
* Contains \Drupal\rsvplist\Form\RSVPForm
*/

namespace Drupal\rsvplist\Form;


use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
* Provides an RSVP email form.
*/

class RSVPForm extends FormBase {
	/**
	 * (@inheritdoc)
	 */
	public function getFormId() {
		return 'rsvplist_email_form';
	}
	/**
	* (@inheritdoc)
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		$node = \Drupal::routeMatch()->getParameter('node');
		$nid  = $node->nid->value;
		$form['email']  = array(
									  '#title' => t('Email Address'),
									  '#type' => 'textfield',
									  '#size' => 25,
									  '#description' => t("We'll send updates to the mail address you provide."),
									  '#required' => TRUE );
  	$form['submit'] = array(
									  '#type' => 'submit',
									  '#value' => t('RSVP'));
		$form['nid']    = array(
									  '#type' => 'hidden',
									  '#value' => $nid);
    return $form;

	}
	
	/**
	  * (@inheritdoc)
	  */
  public function validateForm(array &$form,FormStateInterface $form_state) {
   $value = $form_state->getValue('email');
	  if($value == !\Drupal::service('email.validator')->isValid($value)) {
	   	$form_state->setErrorByName('email', t('The email address %mail is not valid.', array('%mail' => $value)));
	   	return;
	   }
	   $node = \Drupal::routeMatch()->getParameter('node');
	   // Check if mail already is set for this node
	   $select = Database::getConnection()->select('rsvplist', 'r');
	   $select->fields('r', array('nid'));
	   $select->condition('nid', $node->id());
	   $select->condition('mail', $value);
	   $results = $select->execute();
	   if(!empty($results->fetchCol())) {
	   	// We found a id with this node id and email
	   	$form_state->setErrorByName('email', t('The address %mail is already subscribed to this list.', array('%mail' => $value )));
	   }
  }

	/**
	  * (@inheritdoc)
	  */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  	$user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
  	db_insert('rsvplist')
  	   ->fields(array(
           'mail' => $form_state->getValue('email'),
           'nid' => $form_state->getValue('nid'),
           'uid' => $user->id(),
           'created' => time(),
  	   ))
  	   ->execute();

		drupal_set_message(t('Thank you for your RSVP, you are the one listed for the event'));
	}
	
}