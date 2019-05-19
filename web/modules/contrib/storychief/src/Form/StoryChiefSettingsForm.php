<?php namespace Drupal\storychief\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Mailchimp settings for this site.
 */
class StoryChiefSettingsForm extends ConfigFormBase {

	/**
	 * {@inheritdoc}
	 */
	public function getFormID() {
		return 'storychief_admin_settings';
	}

	protected function getEditableConfigNames() {
		return ['storychief.settings'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		global $base_url;

		$config = $this->config('storychief.settings');
		$node_type = $config->get('node_type');

		$form['connect'] = array(
			'#type'       => 'fieldset',
			'#title'      => t('Connect'),
			'api_key'     => array(
				'#type'          => 'textfield',
				'#title'         => t('Story Chief API Key'),
				'#required'      => true,
				'#default_value' => $config->get('api_key'),
				'#description'   => t('Your encryption key is given when you add a Drupal destination on Story Chief'),
			),
			'website_url' => array(
				'#type'        => 'textfield',
				'#title'       => 'Website url',
				'#disabled'    => true,
				'#value'       => $base_url,
				'#description' => t('Copy past this url in your Drupal destination configuration on Story Chief'),
			)
		);

		if ($config->get('api_key')) {
			$types = \Drupal::entityTypeManager()
				->getStorage('node_type')
				->loadMultiple();
			$node_type_options = array();
			foreach ($types as $type) {
				$node_type_options[$type->id()] = $type->label();
			}
			$form['node_settings'] = array(
				'#type'        => 'fieldset',
				'#title'       => t('Node settings'),
				'#description' => t('Step 2: Choose a node type where Story Chief must save stories to'),
				'type'         => array(
					'#type'       => 'container',
					'#attributes' => array('class' => array('container-inline')),
					'node_type'   => array(
						'#type'          => 'select',
						'#empty_option'  => t('- Choose node type -'),
						'#default_value' => $node_type,
						'#options'       => $node_type_options,
					),
					'actions'     => array(
						'#type'  => 'actions',
						'submit' => array(
							'#type'  => 'submit',
							'#value' => t('Update'),
						),
					),
				),
			);

			if (empty($node_type)) {
				$form['fields'] = array(
					'#type'  => 'fieldset',
					'#title' => t('Field mapping'),
					'empty'  => array(
						'#type'   => 'markup',
						'#markup' => '<p><i>You need to select a node type before you can start mapping your fields</i></p>',
					)
				);
			} else {
				$node_fields_by_type = $this->getCoreNodeFieldsByType($node_type);

				$form['fields'] = array(
					'#type'  => 'fieldset',
					'#title' => t('Field mapping'),
					'table'  => array(
						'#type'    => 'container',
						'#prefix'  => '<p>' . t("Last step! Let's map the Story Chief fields to your node:") . '</p><table class="sticky-enabled tableheader-processed sticky-table"><thead></thead><tr><th>' . t('Source: Story Chief story') . '</th><th width="50%">' . t('Target: @type node', array('@type' => $node_type_options[$node_type])) . '</th></tr>',
						'#suffix'  => '</table>',
						'content'  => array(
							'#type'         => 'container',
							'#prefix'       => '<tr class="odd"><td><strong>Content & Excerpt <span class="form-required" title="This field is required."></span></strong><br /><div class="description"> ' . t('This field is required and must be of type <i>@field</i>.<br />The excerpt will be inserted in the summary and can be used for teaser views.', array('@field' => t('Long text and summary'))) . '</div></td><td>',
							'field_content' => array(
								'#type'          => 'select',
								'#empty_option'  => t('- Choose field -'),
								'#title'         => t('Content'),
								'#title_display' => 'attribute',
								'#default_value' => $config->get('mapping.field_content'),
								'#disabled'      => empty($node_fields_by_type['content']),
								'#required'      => true,
								'#options'       => $node_fields_by_type['content'],
								'#description'   => empty($node_fields_by_type['content']) ? t('No <i>@field</i> field found on <i>@node</i> node',
									array(
										'@field' => t('Long text and summary'),
										'@node'  => $node_type_options[$node_type],
									)
								) : '',
							),
							'#suffix'       => '</td><tr>',
						),
						'image'    => array(
							'#type'                => 'container',
							'#prefix'              => '<tr class="even"><td><strong>Cover image</strong><br /><div class="description"> ' . t('The cover image for teaser views and/or header image.') . '</div></td><td>',
							'field_featured_image' => array(
								'#type'          => 'select',
								'#empty_option'  => t('- Choose field -'),
								'#title'         => t('Cover image'),
								'#title_display' => 'attribute',
								'#default_value' => $config->get('mapping.field_featured_image'),
								'#disabled'      => empty($node_fields_by_type['image']),
								'#options'       => $node_fields_by_type['image'],
								'#description'   => empty($node_fields_by_type['image']) ? t('No <i>@field</i> field found on <i>@node</i> node',
									array(
										'@field' => t('Image'),
										'@node'  => $node_type_options[$node_type],
									)
								) : '',
							),
							'#suffix'              => '</td><tr>',
						),
						'tags'     => array(
							'#type'      => 'container',
							'#prefix'    => '<tr class="odd"><td><strong>Tags</strong><br /><div class="description"> ' . t('Micro-categories for your story used to show related stories.') . '</div></td><td>',
							'field_tags' => array(
								'#type'          => 'select',
								'#empty_option'  => t('- Choose field -'),
								'#title'         => t('Tags'),
								'#title_display' => 'attribute',
								'#default_value' => $config->get('mapping.field_tags'),
								'#disabled'      => empty($node_fields_by_type['tags']),
								'#options'       => $node_fields_by_type['tags'],
								'#description'   => empty($node_fields_by_type['tags']) ? t('No <i>@field</i> field with multiple values found on <i>@node</i> node',
									array(
										'@field' => t('Term reference'),
										'@node'  => $node_type_options[$node_type],
									)
								) : '',
							),
							'#suffix'    => '</td><tr>',
						),
						'category' => array(
							'#type'          => 'container',
							'#prefix'        => '<tr class="even"><td><strong>Category</strong><br /><div class="description"> ' . t('The general topic the story can be classified in.<br />Readers can browse specific categories to see all stories in the category.') . '</div></td><td>',
							'field_category' => array(
								'#type'          => 'select',
								'#empty_option'  => t('- Choose field -'),
								'#title'         => t('Category'),
								'#title_display' => 'attribute',
								'#default_value' => $config->get('mapping.field_category'),
								'#disabled'      => empty($node_fields_by_type['category']),
								'#options'       => $node_fields_by_type['category'],
								'#description'   => empty($node_fields_by_type['category']) ? t('No <i>@field</i> field found on <i>@node</i> node',
									array(
										'@field' => t('Term reference'),
										'@node'  => $node_type_options[$node_type],
									)
								) : '',
							),
							'#suffix'        => '</td><tr>',
						),
					),
				);
			}
		}

		// Load custom fields.
		$custom_fields = $config->get('custom_field_mapping');
		$node_fields = $this->getCoreNodeFields($node_type);
		if (count($custom_fields)) {
			$form['custom_fields'] = array(
				'#type'  => 'fieldset',
				'#title' => t('Custom Field mapping'),
				'table'  => [
					'#type'   => 'container',
					'#prefix' => '<p>' . t("You've got custom fields. Nice! Let's map the custom fields to your node:") . '</p><table class="sticky-enabled tableheader-processed sticky-table"><thead></thead><tr><th>' . t('Source: Story Chief story') . '</th><th width="50%">' . t('Target: @type node', array('@type' => $node_type_options[$node_type])) . '</th></tr>',
					'#suffix' => '</table>',
				]
			);
			foreach ($custom_fields as $field_name => $custom_field) {
				$form['custom_fields']['table'][$field_name] = array(
					'#type'                       => 'container',
					'#prefix'                     => '<tr><td><strong>' . $custom_field['label'] . '</strong><br /><div class="description">key: ' . $field_name . '<br>type: ' . $custom_field['type'] . '</div></td><td>',
					'custom_field_' . $field_name => array(
						'#type'          => 'select',
						'#empty_option'  => t('- Choose field -'),
						'#default_value' => $custom_field['field'],
						'#options'       => $node_fields,
					),
					'#suffix'                     => '</td><tr>',
				);
			}
		}

		return parent::buildForm($form, $form_state);
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state) {
		parent::validateForm($form, $form_state);
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$config = $this->config('storychief.settings');
		$config->set('api_key', $form_state->getValue('api_key'))->save();
		$config->set('node_type', $form_state->getValue('node_type'))->save();

		foreach ($form_state->getValues() as $field_name => $field_value) {
			if (substr($field_name, 0, 6) === "field_") {
				$config->set('mapping.' . $field_name, $field_value)->save();
			} elseif (substr($field_name, 0, 13) === "custom_field_") {
				$field_name = str_replace("custom_field_", "", $field_name);
				$config->set('custom_field_mapping.' . $field_name . '.field', $field_value)->save();
			}
		}

		parent::submitForm($form, $form_state);
	}

	protected function getCoreNodeFieldsByType($node_type) {
		$entityManager = \Drupal::service('entity_field.manager');
		$fieldDefinitions = $entityManager->getFieldDefinitions('node', $node_type);

		$field_by_type = array(
			'content'  => [],
			'image'    => [],
			'tags'     => [],
			'category' => [],
		);
		foreach ($fieldDefinitions as $field_name => $field) {
			$storychief_field_value = $field_name . ' (' . $field->getLabel() . ')';
			switch ($field->getType()) {
				case 'image':
					$field_by_type['image'][$field_name] = $storychief_field_value;
					break;

				case 'text_with_summary':
					$field_by_type['content'][$field_name] = $storychief_field_value;
					break;

				case 'entity_reference':
					if ($field->getSetting('target_type') === 'taxonomy_term') {
						$cardinality = $field->getFieldStorageDefinition()->getCardinality();
						if ($cardinality === -1 || $cardinality > 1) {
							$field_by_type['tags'][$field_name] = $storychief_field_value;
						}
						$field_by_type['category'][$field_name] = $storychief_field_value;
					}
					break;
				default:
					break;
			}
		}

		return $field_by_type;
	}

	protected function getCoreNodeFields($node_type) {
		$entityManager = \Drupal::service('entity_field.manager');
		$fieldDefinitions = $entityManager->getFieldDefinitions('node', $node_type);

		$fields = array();
		foreach ($fieldDefinitions as $field_name => $field) {
			if (substr($field_name, 0, 6) === "field_") {
				$fields[$field_name] = $field_name . ' (' . $field->getLabel() . ')';
			}
		}

		return $fields;
	}
}
