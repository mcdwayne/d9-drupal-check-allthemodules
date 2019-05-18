<?php

namespace Drupal\pending_user_notification\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

class ApproveUserForm extends FormBase
{
	public function getFormId()
	{
		return 'pending_user_notification_approve_user_form';
	}

	public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL)
	{
		if($user)
		{
			if($user->isActive())
			{
				$form['user_already_approved'] = [
					'#markup' => $this->t('This user has already been approved'),
				];
			}
			else
			{
				$form['#user'] = $user;
				$form['header'] = [
					'#prefix' => '<p>',
					'#suffix' => '</p>',
					'#markup' => $this->t("Are you sure you want to active %username's acount?", array('%username' => $user->getDisplayName())),
				];

				$form['actions'] = [
					'#type' => 'actions',
				];

				$form['actions']['activate'] = [
					'#type' => 'submit',
					'#value' => $this->t('Activate account'),
				];

				$form['actions']['cancel'] = [
					'#type' => 'submit',
					'#value' => $this->t('Cancel'),
				];
			}
		}
		else
		{
			$form['user_not_provided'] = [
				'#markup'=> $this->t('User not provided'),
			];
		}

		return $form;
	}

	public function submitForm(array &$form, FormStateInterface $form_state)
	{
		if($form_state->getValue('op') == $form_state->getValue('activate'))
		{
			$user = $form['#user'];
			$user->activate();

			if($user->save())
			{
				drupal_set_message($this->t("%username's account has been activated", array('%username' => $user->getDisplayName())));
			}
			else
			{
				drupal_set_message($this->t('Sorry, an unknown error occurred'));
			}
		}
	}
}
