<?php

/**
 * @file
 * Contains \Drupal\private_notes\Plugin\Block\AddPrivateNotes.
 */

namespace Drupal\private_notes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

//use Drupal\Core\Session\AccountInterface;
/**
 * Provides a 'Add Private Notes' block.
 *
 * @Block(
 *   id = "add_private_notes",
 *   admin_label = @Translation("Private Notes"),
 *   category = @Translation("Custom")
 * )
 */
class AddPrivateNotes extends BlockBase {

    /**
     * {@inheritdoc}
     * Block setting form
     */
    public function blockForm($form, FormStateInterface $form_state) {
        $form = parent::blockForm($form, $form_state);
        // Retrieve existing configuration for this block.
        $config = $this->getConfiguration();
        // Add a form field to the existing block configuration form.
        $node_types = \Drupal\node\Entity\NodeType::loadMultiple();
        $options = [];
        foreach ($node_types as $node_type) {
            $options[$node_type->id()] = $node_type->label();
        }
        $form['private_notes_allowed_content_types'] = array(
            '#type' => 'select',
            '#title' => t('Select Content Types on which you want to allow private notes'),
            '#options' => $options,
            '#default_value' => isset($config['private_notes_allowed_content_types']) ? $config['private_notes_allowed_content_types'] : '',
            '#multiple' => TRUE,
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state) {
        // Save our content types settings when the form is submitted.
        $this->setConfigurationValue('private_notes_allowed_content_types', $form_state->getValue('private_notes_allowed_content_types'));
    }

    /**
     * {@inheritdoc}
     */
    public function blockValidate($form, FormStateInterface $form_state) {
        /*
		$content_types_names = $form_state->getValue('private_notes_allowed_content_types');
        if (empty($content_types_names)) {
            drupal_set_message('Select Content Types on which you want to allow private notes', 'error');
            $form_state->setErrorByName('private_notes_allowed_content_types', t('Please select content type'));
        }
		*/
    }

    /**
     * {@inheritdoc}
     */
    public function build() {
        $renderArray = array();
        $permission = \Drupal::currentUser()->hasPermission('view and add private note');
       // $current_node = \Drupal::request()->attributes->get('node');
       // $istype = $this->private_notes_show_on_node_type($current_node->bundle());
        if ($permission){ 
            $builtForm = \Drupal::formBuilder()->getForm('Drupal\private_notes\Form\PrivateNotesAddForm');
            $renderArray['form'] = $builtForm;
        
        }
        return $renderArray;
    }

    /**
     * {@inheritdoc}
     * 
     * Function to check if notes is allowed on given content type.
     *
     * @return bool
     *   true/false.
     */
    function private_notes_show_on_node_type($type) {
        $config = $this->getConfiguration(); 
        $allowed_content_types = $config['private_notes_allowed_content_types'];
        return (!empty($allowed_content_types) && (in_array($type, $allowed_content_types))) ? TRUE : FALSE;  
       
    }

}
