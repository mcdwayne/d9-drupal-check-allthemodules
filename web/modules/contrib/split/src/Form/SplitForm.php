<?php

/**
 * @file
 * Contains \Drupal\resume\Form\ContributeForm.
 */

namespace Drupal\split\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Contribute form.
 */
class SplitForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'split_amount_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['amount'] =  array(
		'#type' => 'textfield',
		'#title' => 'Split Amount',
	);
	$users_option = array();
	$users = db_query('select uid from users')->fetchCol();
	foreach ($users as $key => $value) {
		if($value != 0) {
			$account = \Drupal\user\Entity\User::load($value); // pass your uid
     		$name = $account->getUsername();
			$users_option[$value] = $name;
		}
	}
	$form['users'] = array( 
		'#title' => t('Users'),
		'#type' => 'select',
		'#description' => "Select the users.",
		'#options' => $users_option,
		'#multiple' => TRUE,
		'#required' => TRUE
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Split')
	);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $amount = $form_state->getValue('amount');
    $user_ids = $form_state->getValue('users');
    $spliiter_amount = (float)$amount/count($user_ids);
    foreach ($user_ids as $uid => $sender_uid) {
		$query1 = db_query('SELECT * from split_data where splitter_uid = '.$user->id().' AND sender_uid = '.$sender_uid)->fetchAll();
		$query2 = db_query('SELECT * from split_data where splitter_uid = '.$sender_uid.' AND sender_uid = '.$user->id())->fetchAll();
		// if no records exist for spliiter and sender
		if(empty($query1) && empty($query2)) {
			$id = db_insert('split_data') 
				->fields(array( 'splitter_uid' => $user->id(), 'sender_uid' => $sender_uid, 'amount' => $spliiter_amount )) 
				->execute();
		}
		// if spliiter->sender exist but sender->splitter doesn't exist
		if(!empty($query1) && empty($query2)){
			// need to implement logic
			$prev_balance = db_query('select amount from split_data where splitter_uid = '.$user->id().' AND sender_uid = '.$sender_uid.' order by id desc')->fetchCol()[0];
			$total_balance = $prev_balance + $spliiter_amount;
			$id = db_insert('split_data') 
				->fields(array( 'splitter_uid' => $user->id(), 'sender_uid' => $sender_uid, 'amount' => $total_balance )) 
				->execute();
		}
		// if spliiter->sender doesn't exist but sender->splitter exist
		if(empty($query1) && !empty($query2)) {
			$opposite_balance = db_query('SELECT amount from split_data where splitter_uid = '.$sender_uid.' AND sender_uid = '.$user->id().' order by id desc')->fetchCol()[0];
			$balance = $opposite_balance - $spliiter_amount;
			// adjust the balalnce for both party to zero
			if($balance == 0) {
				$id = db_insert('split_data') 
				->fields(array( 'splitter_uid' => $user->id(), 'sender_uid' => $sender_uid, 'amount' => 0 )) 
				->execute();
				$id = db_insert('split_data') 
				->fields(array( 'splitter_uid' => $sender_uid, 'sender_uid' => $user->id(), 'amount' => 0 )) 
				->execute();
			}
			// update the balance of sender
			if($balance > 0) {
				$id = db_insert('split_data') 
				->fields(array( 'splitter_uid' => $user->id(), 'sender_uid' => $sender_uid, 'amount' => 0 )) 
				->execute();
				$id = db_insert('split_data') 
				->fields(array( 'splitter_uid' => $sender_uid, 'sender_uid' => $user->id(), 'amount' => $balance )) 
				->execute();
			}
			// update the balance of splitter
			if($balance < 0) {
				$id = db_insert('split_data') 
				->fields(array( 'splitter_uid' => $user->id(), 'sender_uid' => $sender_uid, 'amount' => -($balance) )) 
				->execute();
				$id = db_insert('split_data') 
				->fields(array( 'splitter_uid' => $sender_uid, 'sender_uid' => $user->id(), 'amount' => 0 )) 
				->execute();
			}
		}
		// both relationship exist
		if(!empty($query1) && !empty($query2)) {
			if($user->id() == $sender_uid) {
				$prev_balance = db_query('select amount from split_data where splitter_uid = '.$user->id().' AND sender_uid = '.$sender_uid.' order by id desc')->fetchCol()[0];
				$total_balance = $prev_balance + $spliiter_amount;
				$id = db_insert('split_data') 
					->fields(array( 'splitter_uid' => $user->id(), 'sender_uid' => $sender_uid, 'amount' => $total_balance )) 
					->execute();
			}
			else {
				$current_balance = db_query('SELECT amount from split_data where splitter_uid = '.$user->id().' AND sender_uid = '.$sender_uid.' order by id desc')->fetchCol()[0];
				$opposite_balance = db_query('SELECT amount from split_data where splitter_uid = '.$sender_uid.' AND sender_uid = '.$user->id().' order by id desc')->fetchCol()[0];
				if($current_balance == 0) {
					$balance = $opposite_balance - $spliiter_amount;
					if($balance == 0) {
						$id = db_insert('split_data') 
						->fields(array( 'splitter_uid' => $user->id(), 'sender_uid' => $sender_uid, 'amount' => 0 )) 
						->execute();
						$id = db_insert('split_data') 
						->fields(array( 'splitter_uid' => $sender_uid, 'sender_uid' => $user->id(), 'amount' => 0 )) 
						->execute();
					}
					// update the balance of sender
					if($balance > 0) {
						$id = db_insert('split_data') 
						->fields(array( 'splitter_uid' => $user->id(), 'sender_uid' => $sender_uid, 'amount' => 0 )) 
						->execute();
						$id = db_insert('split_data') 
						->fields(array( 'splitter_uid' => $sender_uid, 'sender_uid' => $user->id(), 'amount' => $balance )) 
						->execute();
					}
					// update the balance of splitter
					if($balance < 0) {
						$id = db_insert('split_data') 
						->fields(array( 'splitter_uid' => $user->id(), 'sender_uid' => $sender_uid, 'amount' => -($balance) )) 
						->execute();
						$id = db_insert('split_data') 
						->fields(array( 'splitter_uid' => $sender_uid, 'sender_uid' => $user->id(), 'amount' => 0 )) 
						->execute();
					}
				}
				else {
					$balance = $current_balance  + $spliiter_amount;
					$id = db_insert('split_data') 
						->fields(array( 'splitter_uid' => $user->id(), 'sender_uid' => $sender_uid, 'amount' => $balance )) 
						->execute();
				}

			}

		}
	}
    drupal_set_message(t('Amount split successfully'));
  }
}
