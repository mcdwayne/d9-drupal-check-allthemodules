<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 24.12.2018
 * Time: 15:55
 */

namespace Drupal\hashtags\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Url;

class ConfirmDeleteForm extends ConfirmFormBase {
    private $entity_type;
    private $bundle;

    /**
     * Returns the question to ask the user.
     * @return \Drupal\Core\StringTranslation\TranslatableMarkup
     *   The form question. The page title will be set to this value.
     */
    public function getQuestion() {
        $entity_type_label = _hashtags_get_entity_type_label($this->entity_type);
        $bundle_label = _hashtags_get_bundle_label($this->entity_type,$this->bundle);
        $source = $this->entity_type !== $this->bundle ?
                  ($entity_type_label . ' > ' . $bundle_label) :
                  $entity_type_label;
        return $this->t("Are you sure you want to remove hashtags for <em>{$source}</em>?");
    }

    /**
     * Returns the route to go to if the user cancels the action.
     * @return \Drupal\Core\Url
     *   A URL object.
     */
    public function getCancelUrl() {
        return new Url('hashtags.manager_form');
    }

    /**
     *
     * @return \Drupal\Core\StringTranslation\TranslatableMarkup
     */
    public function getConfirmText() {
        return $this->t('Delete');
    }

    /**
     * Returns a unique string identifying the form.
     * The returned ID should be a unique string that can be a valid PHP function
     * name, since it's used in hook implementation names such as
     * hook_form_FORM_ID_alter().
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'hashtags_delete_form';
    }

    public function buildForm(array $form,
                              FormStateInterface $form_state,
                              $entity_type = '', $bundle = '') {
        $this->entity_type = $entity_type;
        $this->bundle = $bundle;

        return parent::buildForm($form, $form_state);
    }


    /**
     * Form submission handler.
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $hashtags_field_name = \Drupal::config('hashtags.settings')
                                ->get('hashtags_taxonomy_terms_field_name');
        $hashtags_field = FieldConfig::loadByName($this->entity_type,
                                         $this->bundle,
                                         $hashtags_field_name);
        if (!empty($hashtags_field)) {
            $hashtags_field->delete();
            \Drupal::messenger()->addMessage('Hashtags field has been removed.');
            $activated_text_fields = _hashtags_get_activated_text_fields($this->entity_type, $this->bundle);
            foreach ($activated_text_fields as $field_name) {
                $text_field = \Drupal::entityTypeManager()
                    ->getStorage('field_config')
                    ->load("{$this->entity_type}.{$this->bundle}.{$field_name}");
                if (!empty($text_field)) {
                    $text_field->unsetThirdPartySetting('hashtags', 'hashtags_activate');
                    $text_field->save();
                    \Drupal::messenger()->addMessage("Hashtags has been diactivated for {$field_name} field.");
                }
            }
        }
        $form_state->setRedirectUrl(new Url('hashtags.manager_form'));
    }
}
