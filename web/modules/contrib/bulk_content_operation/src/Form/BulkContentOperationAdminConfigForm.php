<?php
/**
 * @file
 * Contains \Drupal\bulk_content_operation\Form\BulkContentOperationAdminConfigForm
 */

namespace Drupal\bulk_content_operation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure Bulk Content Operation module settings
 */
class BulkContentOperationAdminConfigForm extends ConfigFormBase {
	/**
	 * {@inheritdoc}
	 */
	public function getFormID() {
		return 'bulk_content_operation_admin_config_form';
	}
	/**
	 * {@inheritdoc}
	 */
	protected function getEditableConfigNames() {
		return [
				'bulk_content_operation.settings'
		];
	}
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
		$types = node_type_get_names();
		$config = $this->config('bulk_content_operation.settings');
		$form['bulk_content_operation_types'] = array(
				'#type' => 'checkboxes',
				'#title' => $this->t('The content types to enable for bulk content operations'),
				'#default_value' => $config->get('allowed_types'),
				'#options' => $types,
				'#description' => $this->t('On the specified node types, a bulk content operation will be available and can be enabled while content type listing.'),
		);
		$form['array_filter'] = array('#type' => 'value', '#value' => TRUE);
		
		return parent::buildForm($form,$form_state);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$allowed_types = array_filter($form_state->getValue('bulk_content_operation_types'));
		sort($allowed_types);
		$this->config('bulk_content_operation.settings')
		->set('allowed_types', $allowed_types)
		->save();
		parent::submitForm($form, $form_state);
	}
}



