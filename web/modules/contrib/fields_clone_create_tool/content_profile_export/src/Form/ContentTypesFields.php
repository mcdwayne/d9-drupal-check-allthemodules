<?php
namespace Drupal\content_profile_export\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


class ContentTypesFields extends ConfigFormBase {


	public function getFormId() {
		return 'content_type_fields_settings';
	}


	public function getEditableConfigNames() {
		return [
			'content_type_fields.settings',
		];
	}


	public function buildForm(array $form,  FormStateInterface $form_state) {
		$config = $this->config('content_type_fields.settings');

		$form['content_type_name_source'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Enter the Machine Name of the Source Content Type'),
			'#default_value' => $config->get('content_type_name_source'),
		];

		$form['content_type_name_destination'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Enter the Machine Name of the Destination Content Type'),
			'#default_value' => $config->get('content_type_name_destination'),
		];

		return parent::buildForm($form, $form_state);

	}


	public function validateForm(array &$form, FormStateInterface $form_state) {

		$getContentTypeValue = $form_state->getValue('content_type_name_source');
		$getProfileTypeValue = $form_state->getValue('content_type_name_destination');


		if(empty($getContentTypeValue))
			$form_state->setErrorByName('content_type_name_source', $this->t('Enter the Source Content Type Name'));

		if(empty($getProfileTypeValue))
			$form_state->setErrorByName('content_type_name_destination', $this->t("Enter the Destination Content Type Name "));

	}



	public function submitForm(array &$form, FormStateInterface $form_state) {

		$contentTypeName = $form_state->getValue('content_type_name_source');
		$destinationTypeName = $form_state->getValue('content_type_name_destination');

		$this->configFactory->getEditable('content_type_fields.settings')
			->set('content_type_name_source', $form_state->getValue('content_type_name_source'))
			->set('content_type_name_destination', $form_state->getValue('content_type_name_destination'))
			->save();


		parent::submitForm($form, $form_state);
	}



}











