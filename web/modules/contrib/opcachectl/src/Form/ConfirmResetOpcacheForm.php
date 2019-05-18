<?php

namespace Drupal\opcachectl\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 */
class ConfirmResetOpcacheForm extends ConfirmFormBase {

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		// TODO: check for opcache_get_status()[opcache_enabled]
		// TODO: check for opcache_get_status()[restart_pending] or/and  opcache_get_status()[restart_in_progress]
		return parent::buildForm($form, $form_state);
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		if (opcachectl_reset()) {
			\Drupal::messenger()->addStatus( t('OPcache reset.') );
		} else {
			\Drupal::messenger()->addError( t('OPcache reset failed.') );
		}
		$form_state->setRedirect('opcachectl.report.stats');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFormId() : string {
		return "confirm_reset_opcache_form";
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCancelUrl() {
		return new Url('opcachectl.report.stats');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getQuestion() {
		return t('Do you want to reset the OPcache?');
	}

}
