<?php
/**
 * @file
 * Contains \Drupal\nodes_csv_import\Form\NodesCSVImportForm.
 */
 namespace Drupal\nodes_csv_import\Form;
 
 use Drupal\Core\Form\FormBase;
 use Drupal\Core\Form\FormStateInterface;
 use Drupal\file\Entity\File;
 use Drupal\node\Entity\Node;
 use Drupal\field\FieldConfigInterface;
 use Drupal\Core\Ajax\AjaxResponse;
 use Drupal\Core\Ajax\HtmlCommand;
 
 class NodesCSVImportForm extends FormBase {

	function contentTypeFields($contentType) {
		$entityManager = \Drupal::service('entity.manager');
		$fields = [];

		if(!empty($contentType)) {
			$fields = array_filter(
				$entityManager->getFieldDefinitions('node', $contentType), function ($field_definition) {
					return $field_definition instanceof FieldConfigInterface;
				}
			);
		}

		return $fields;
	}
	
	/**
	 * {@inheritdoc}
	 */
    public function getFormId() {
		return 'nodes_csv_import_form';
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$form = array(
			'#attributes' => array('enctype' => 'multipart/form-data')
		);
		
		$types = array();
		$types = \Drupal::service('entity.manager')
				->getStorage('node_type')
				->loadMultiple();
				
		$content_types = array();
		foreach($types as $key => $obj) {
			$content_types[$key] = $key;
		}
		
		$form['import_node_type'] = array(
			'#type' => 'select',
			'#title' => t('Select node type'),
			'#options' => $content_types,
			'#default_value' => t('Select'),
			'#required' => TRUE,
			'#ajax' => array(
				'event' => 'change',
				'callback' => '::import_node_type_change_callback',
				'wrapper' => 'import_node_type_change_wrapper',
				'progress' => array(
					'type' => 'throbber',
					'message' => NULL,
				),
			),
		);
		
		$form['import_ct_markup'] = array(
			'#markup' => t('Please make sure your csv file contains all the below columns'),
			'#suffix' => '<div id="import_node_type_change_wrapper"><table border=1><tr><td></td><td></td></tr></table></div>',
		);
		
		$validators = array('file_validate_extensions' => 'csv');
		$form['csv_file_upload'] = array(
			'#type' => 'managed_file',
			'#title' => $this->t('Upload CSV file'),
			'#required' => TRUE,
			'#description' => t('Please upload a CSV file only. Make sure the file type is in \'UTF-8\' format'),
			'#upload_validators' => $validators,
			'#upload_location' => 'public://node_imports/',
		);
		
		$form['actions']['#type'] = 'actions';
		$form['actions']['submit'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Process'),
			'#button_type' => 'primary',
		);
		
		$form['instructions'] = array(
			'#markup' => t('Please follow the instructions below:'),
			'#suffix' => '<ul>
						  <li>Please upload \'UTF-8\' file types</li>
						  <li>The dropdown lists all the content types in your current site. The table below the dropdown shows automatically all the fields added for that content type.</li>
						  <li>Once you select the content type, along with all the fields, \'nid \' is also listed.</li>
						  <li>If \'nid \' column is updated in the CSV file column, then the node content with that specific nid will be updated. If the nid column is empty in the CSV file, then a new node will be created of the same content type.</li>
						  </ul>',
		);
		
		return $form;
	}
	
	public function import_node_type_change_callback(array &$form, FormStateInterface $form_state) {
		$ajax_response = new AjaxResponse();
		$contentType = $form_state->getValue('import_node_type');
		$fields = $this->contentTypeFields($contentType);
		$result = '<table border=1><tr>';
		$result .= '<td>nid</td>';
		foreach($fields as $key => $val) {
			$result .= '<td>'.$key.'</td>';
		}
		$result .= '</tr></table>';
		//$form['import_ct_markup']['#markup'] = $result;
		//return $form['import_ct_markup'];
		$ajax_response->addCommand(new HtmlCommand('#import_node_type_change_wrapper', $result));
		return $ajax_response;
	}
	
	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
		
	}
	
	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		//node type imported type
		$import_node_type = $form_state->getValue('import_node_type');
		//get filename and File URI
		$fid = $form_state->getValue('csv_file_upload')[0];
		//loading file using File Class
		$file = File::load($fid);
		$uri = $file->getFileUri();
		$stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($uri);
		$file_path = $stream_wrapper_manager->realpath();
		
		//batch process 
		$batch = array(
			'title' => t('Updating nodes...'),
			'operations' => array(
				array(
					'\Drupal\nodes_csv_import\ImportNodesBatchProcess::NodesCSVBatchStart', array($import_node_type, $file_path)
				),
			),
			'finished' => '\Drupal\nodes_csv_import\ImportNodesBatchProcess::NodesCSVBatchFinished',
		);
		batch_set($batch);	
		
	}
 }
 