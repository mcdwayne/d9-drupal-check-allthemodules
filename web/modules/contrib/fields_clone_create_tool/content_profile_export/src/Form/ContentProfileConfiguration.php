<?php
namespace Drupal\content_profile_export\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


class ContentProfileConfiguration extends ConfigFormBase {


	public function getFormId() {
		return 'content_profile_export_settings';
	}


	public function getEditableConfigNames() {
		return [
			'content_profile_export.settings',
		];
	}


	public function buildForm(array $form,  FormStateInterface $form_state) {
		$config = $this->config('content_profile_export.settings');

		$form['content_type_name'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Enter the Machine Name of the Content Type'),
			'#default_value' => $config->get('content_type_name'),
		];

		$form['profile_type_name'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Enter the Machine Name of the Profile Type'),
			'#default_value' => $config->get('profile_type_name'),
		];

		return parent::buildForm($form, $form_state);

	}


	public function validateForm(array &$form, FormStateInterface $form_state) {

		$getContentTypeValue = $form_state->getValue('content_type_name');
		$getProfileTypeValue = $form_state->getValue('profile_type_name');


		if(empty($getContentTypeValue))
			$form_state->setErrorByName('content_type_name', $this->t('Enter the Content Type Name'));

		if(empty($getProfileTypeValue))
			$form_state->setErrorByName('profile_type_name', $this->t("Enter the Profile Type Name "));

	}



	public function submitForm(array &$form, FormStateInterface $form_state) {

		$contentTypeName = $form_state->getValue('content_type_name');
		$profileTypeName = $form_state->getValue('profile_type_name');

		$this->configFactory->getEditable('content_profile_export.settings')
			->set('profile_type_name', $form_state->getValue('profile_type_name'))
			->set('content_type_name', $form_state->getValue('content_type_name'))
			->save();


		parent::submitForm($form, $form_state);
	}



}











