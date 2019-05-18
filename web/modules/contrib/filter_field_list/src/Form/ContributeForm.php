<?php

namespace Drupal\filter_field_list\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Contribute form.
 */
class ContributeForm extends FormBase {

	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'amazing_forms_contribute_form';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$form['filter'] = array(
			'#type' => 'fieldset',
			'#title' => t('Show only Field items where'),
			'#weight' => -5,
			'#collapsible' => FALSE,
			'#collapsed' => FALSE,
		);

//$instance = \Drupal::entityManager()->getBundleInfo();
//$instances =  Field::fieldInfo()->getBundleInstance();
		// $instances = field_info_instances();
////  $field_types = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
//  $bundles = field_info_bundles();
		// $modules = system_rebuild_module_data();

		$header = array($this->t('Field name'), $this->t('Field type'), $this->t('Used in bundle (Data count)'));
		$entity_types[] = 'Any';

		//list($rows, $fields_all) = _filter_field_list_table($instances, $bundles, $field_types, $modules, $form_state);

		foreach ($rows as $field_name => $cell) {
			$rows[$field_name]['data'][2] = implode(', ', $cell['data'][2]);
		}
		$field_types_options = array();
		$field_types_options[] = 'Any';
		foreach ($field_types as $key => $value) {
			$field_types_options[$key] = $value['label'];
		}
		$form['filter']['field_type'] = array(
			'#type' => 'select',
			'#title' => t('Field type'),
			'#options' => $field_types_options,
		);

		$fields_options = array();
		$fields_options[] = 'Any';
		foreach ($fields_all as $key => $value) {
			$fields_options[$value] = $value;
		}

		$entity_type_id = 'node';
		$bundle = 'article';
		foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
			if (!empty($field_definition->getTargetBundle())) {
				$bundleFields[$entity_type_id][$field_name]['type'] = $field_definition->getType();
				$bundleFields[$entity_type_id][$field_name]['label'] = $field_definition->getLabel();
			}
		}
		$form['filter']['field'] = array(
			'#type' => 'select',
			'#title' => $this->t('Field'),
			'#options' => $fields_options,
		);

		$bundles = entity_get_bundles();
		foreach ($bundles as $b_type => $b) {
			foreach ($b as $k => $v) {
				$list[ucfirst($b_type)][$k] = $v['label'];
			}
		}
		$form['filter']['bundle'] = array(
			'#type' => 'select',
			'#title' => $this->t('Bundle'),
			'#options' => $list,
		);

		$form['filter']['submit'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Filter'),
			'#submit' => array('filter_field_list_field_submit'),
		);

		//Build the table select.
		$form['nodes'] = array(
			'#theme' => 'table',
			'#header' => $header,
			'#options' => array(1, 2),
			'#empty' => $this->t('No content available.'),
			'#rows' => $rows
		);

		return $form;
	}

	/**
	 * @param $instances
	 * @param $bundles
	 * @param $field_types
	 * @param $modules
	 * @param $form_state
	 * @return array
	 */
	function _filter_field_list_table($instances, $bundles, $field_types, $modules, $form_state) {
		$rows = array();
		$fields_all = array();
		foreach ($instances as $entity_type => $type_bundles) {
			$entity_types[$entity_type] = $entity_type;
			foreach ($type_bundles as $bundle => $bundle_instances) {
				foreach ($bundle_instances as $field_name => $instance) {
					$field = field_info_field($field_name);
					$fields_bundles = array();
					foreach ($field['bundles'] as $entity => $field_bundles) {
						foreach ($field_bundles as $v) {
							$fields_bundles[] = $v;
						}
					}
					$fields_all[] = $field_name;
					if (!empty($form_state['values']['field'])) {
						if ($field_name == $form_state['values']['field']) {
							$field = field_info_field($field_name);
							if (!empty($form_state['values']['field_type'])) {
								if ($form_state['values']['field_type'] != $field['type']) {
									continue;
								}
							}
							$fields[] = $field_name;
						}
						else {
							continue;
						}
						if (!empty($form_state['values']['bundle'])) {
							if (!in_array($form_state['values']['bundle'], $fields_bundles)) {
								continue;
							}
						}
					}
					else {
						if (!empty($form_state['values']['field_type'])) {
							if ($form_state['values']['field_type'] != $field['type']) {
								continue;
							}
						}
						if (!empty($form_state['values']['bundle'])) {
							if (!in_array($form_state['values']['bundle'], $fields_bundles)) {
								continue;
							}
						}
					}
					// Initialize the row if we encounter the field for the first time.
					if (!isset($rows[$field_name])) {
						$rows[$field_name]['class'] = $field['locked'] ? array('menu-disabled') : array('');
						$rows[$field_name]['data'][0] = $field['locked'] ? t('@field_name (Locked)', array('@field_name' => $field_name)) : $field_name;
						$module_name = $field_types[$field['type']]['module'];
						$rows[$field_name]['data'][1] = $field_types[$field['type']]['label'] . ' ' . t('(module: !module)', array('!module' => $modules[$module_name]->info['name']));
					}

					// Add the current instance.
					$admin_path = _field_ui_bundle_admin_path($entity_type, $bundle);

					// Used in column data
					// @FIXME
// l() expects a Url object, created from a route name or external URI.
// $rows[$field_name]['data'][2][] = $admin_path ? l($bundles[$entity_type][$bundle]['label'], $admin_path . '/fields') : $bundles[$entity_type][$bundle]['label'];
				}
			}
		}
		return array($rows, $fields_all);
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state) {
		// Validate video URL.
		if (!UrlHelper::isValid($form_state->getValue('video'), TRUE)) {
			$form_state->setErrorByName('video', $this->t("The video url '%url' is invalid.", array('%url' => $form_state->getValue('video'))));
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		// Display result.
		foreach ($form_state->getValues() as $key => $value) {
			drupal_set_message($key . ': ' . $value);
		}
	}

}
