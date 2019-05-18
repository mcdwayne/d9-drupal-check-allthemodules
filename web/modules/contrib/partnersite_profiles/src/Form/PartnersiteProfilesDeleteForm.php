<?php

namespace Drupal\partnersite_profile\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;


/**
 * Builds the form to delete Partnersite profiles entities.
 */
class PartnersiteProfilesDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.partnersite_profiles.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  	$this->entity->delete();

    $users_id = \Drupal::entityQuery('user')
			->condition('name', $this->entity->label(), '=')
			->condition('roles', 'partner_users', '=')
//			->addTag('debug')
			->execute();

		$users = User::loadMultiple($users_id);

		foreach ( $users as $user_id => $userObj)
		{
			user_delete($user_id);
		}

		$this->messenger()->addMessage(
      $this->t('content @type: deleted @label.',
        [
          '@type' => $this->entity->bundle(),
          '@label' => $this->entity->label(),
        ]
        )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
