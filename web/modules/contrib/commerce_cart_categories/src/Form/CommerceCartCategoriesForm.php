<?php

/**
* @file
* Contains \Drupal\commerce_cart_categories\Form\CommerceCartCategoriesForm.
**/

namespace Drupal\commerce_cart_categories\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CommerceCartCategoriesForm extends ConfigFormBase {

    /**
     * Provides form ID
     */

    public function getFormId() {
	return 'commerce_cart_categories.form';
    }

    /**
     * Builds form
     */

    public function buildForm(array $form, FormStateInterface $form_state) {
        $taxonomy_fields = [];
        $entity_type_id = "commerce_product";

        // This chunk of code is used to determine which taxonomy reference fields are attached to existing bundles of @commerce_product entity.
        // We need to form list of such fields to attach it to configuration form
        $product_types = \Drupal::service("entity_type.bundle.info")->getBundleInfo($entity_type_id);
        foreach($product_types AS $key => $value) {
            $bundle_fields = \Drupal::entityManager()->getFieldDefinitions($entity_type_id, $key);
            foreach($bundle_fields AS $fieldname => $fieldobj) {
                if(substr($fieldname, 0, 6) == "field_") {
                    $settings = $fieldobj->getFieldStorageDefinition()->getSettings();
                    if(isset($settings['target_type']) && $settings['target_type'] == "taxonomy_term") {
                        $taxonomy_fields[] = $fieldname;
                    };
                };
            };
        };

	$form['products_taxonomy_field'] = array(
	    '#type' => 'select',
	    '#options' => [],
	    '#required' => TRUE,
	    '#empty_option' => '-- please select --',
	    '#title' => $this->t('Product field (which references taxonomy vocabulary) to classify your products by'),
	    '#default_value' => $this->config('commerce_cart_categories.conf')->get('products_taxonomy_field'),
	);

        foreach($taxonomy_fields AS $field) {
            $form['products_taxonomy_field']['#options'][$field] = $field;
        };

	return parent::buildForm($form, $form_state);
    }

    /**
     * Validates form
     */

    public function validateForm(array &$form, FormStateInterface $form_state) {
	if ($form_state->isValueEmpty('products_taxonomy_field')) {
	    $form_state->setErrorByName('products_taxonomy_field', t('This field can\'t be blank!'));
	}
    }

    /**
     * Processes form submit
     */

    public function submitForm(array &$form, FormStateInterface $form_state) {
	parent::submitForm($form, $form_state);
	$this->configFactory->getEditable('commerce_cart_categories.conf')
		->set('products_taxonomy_field', $form_state->getValue('products_taxonomy_field'))
		->save();
    }

    /**
     * Returns form config name
     */

    protected function getEditableConfigNames() {
	return ['commerce_cart_categories.conf'];
    }

}