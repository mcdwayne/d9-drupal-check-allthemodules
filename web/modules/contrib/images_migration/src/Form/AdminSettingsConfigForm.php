<?php
/**
 * @file
 * Contains \Drupal\image_migration\Form\AdminSettingsConfigForm
 */

namespace Drupal\image_migration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure Image Migration module settings
 */
class AdminSettingsConfigForm extends ConfigFormBase {
	/**
	 * {@inheritdoc}
	 */
	public function getFormID() {
		return 'image_migration_admin_settings';
	}
	/**
	 * {@inheritdoc}
	 */
	protected function getEditableConfigNames() {
		return [
				'image_migration.settings'
		];
	}
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
		$config = $this->config('image_migration.settings');
		// content type list loading
		$contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
		$contentTypesList = [];
		foreach ($contentTypes as $contentType) {
			$contentTypesList[$contentType->id()] = $contentType->label();
		}
		
		$form['source_path'] = array(
				'#type' => 'textfield',
				'#title' => $this->t('Source Path'),
				'#default_value' => $config->get('source_path'),
				'#size' => 30,
				'#required' => TRUE,
				'#description' => $this->t('Set source path for your Drupal 7 image assets')
		);
		
		$form['destination_path'] = array(
				'#type' => 'textfield',
				'#title' => $this->t('Destination Path:'),
				'#default_value' => $config->get('destination_path'),
				'#size' => 30,
				'#required' => TRUE,
				'#description' => $this->t('Set destination path for your Drupal 8 image assets')
		);
		
		$form['content_type_list'] = array(
				'#type' => 'checkboxes',
				'#title' => $this->t('Select Content Type:'),
				'#default_value' => $config->get('content_type_list'),
				'#options' => $contentTypesList,
				'#required' => TRUE,
				'#description' => $this->t('Select content types for image migrations')
		);
		
		$form['db_settings'] = array(
				'#type' => 'fieldset',
				'#title' => $this->t('Source DB Settings:'),
		);
		
		$form['db_settings']['db_name'] = array(
				'#type' => 'textfield',
				'#title' => $this->t('Source Database Name:'),
				'#default_value' => $config->get('db_name'),
				'#size' => 30,
				'#required' => TRUE,
				'#description' => $this->t('It is an assumption that Drupal 7 and Drupal 8 sites are hosted on the same host (localhost)'),
		);
		
		return parent::buildForm($form,$form_state);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Drupal\Core\Form\FormBase::validateForm()
	 */
	public function validateForm(array &$form, FormStateInterface $form_state) {
		$content_type_list = $form_state->getValue('content_type_list');
		foreach ($content_type_list as $type) {
			if ($type != '0') {
				$check_status = \Drupal::service('image_migration.checklist')->isDone($type);
				if ($check_status) {
					$form_state->setErrorByName('content_type_list', t('Images are migrated for selected content types'));
				}
			}
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function submitForm (array &$form, FormStateInterface $form_state) {
		$source_path = $form_state->getValue('source_path');
		$destination_path = $form_state->getValue('destination_path');
		$db_name = $form_state->getValue('db_name');
		$content_type_list = $form_state->getValue('content_type_list');
		$this->config('image_migration.settings')
		->set('source_path', $source_path)
		->save();
		
		$this->config('image_migration.settings')
		->set('destination_path', $destination_path)
		->save();
		
		$this->config('image_migration.settings')
		->set('content_type_list', $content_type_list)
		->save();
		
		$this->config('image_migration.settings')
		->set('db_name', $db_name)
		->save();
		
		
		$batch = array(
				'title' => t('Image Migration is in Progress...'),
				'operations' => array(
						array(
								$this->copyAssets($source_path, $destination_path),
								$this->imageMigration($content_type_list)
						),
				),
				'finished' => drupal_flush_all_caches()
		);
		
		batch_set($batch);	
		parent::submitForm($form, $form_state);
	}
	
	/**
	 *
	 */
	public function imageMigration($content_types) {
		$entity_type = 'node';
		$field_type = 'image';
		foreach ($content_types as $type) {
			if($type != '0') {
				// Calling Service
				$service = \Drupal::service('image_migration.imagemigration');
				// Establish External DB Connection
				$service->setExternalDBConnection();
				// Setup Source Data Object
				$sourceArray = $service->getExternalSourceDataObject($entity_type, $type, $field_type);
				// Get back to Active connection
				$service->setActiveDBConnection();
				// Write Active DB Tables.
				$service->setInternalDestinationObject($sourceArray, $type);
			}
		}
	}
	
	
	/**
	 *
	 * @param unknown $source
	 * @param unknown $dest
	 * @return boolean|unknown
	 */
	public function copyAssets($source, $dest) {
		// Check for symlinks
		if (is_link($source)) {
			return symlink(readlink($source), $dest);
		}
		
		// Simple copy for a file
		if (is_file($source)) {
			return copy($source, $dest);
		}
		
		// Make destination directory
		if (!is_dir($dest)) {
			mkdir($dest);
		}
		
		// Loop through the folder
		$dir = dir($source);
		while (false !== $entry = $dir->read()) {
			// Skip pointers
			if ($entry == '.' || $entry == '..') {
				continue;
			}
			
			// Deep copy directories
			// $this->copyAssets("$source/$entry", "$dest/$entry");
			\Drupal\image_migration\Form\AdminSettingsConfigForm::copyAssets("$source/$entry", "$dest/$entry");
		}
		
		// Clean up
		$dir->close();
		return true;
	}
}