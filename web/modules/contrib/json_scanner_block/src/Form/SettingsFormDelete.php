<?php

namespace Drupal\json_scanner_block\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\json_scanner_block\Controller\SettingsListController;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 */
class SettingsFormDelete extends ConfirmFormBase {

    /**
     * ID of the item to delete.
     *
     * @var int
     */
    protected $id;

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
        $this->id = $id;
        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {        
        $remove = new SettingsListController;
        $remove->removeData($this->id);
        $form_state->setRedirect('json_scanner_block.list_data');
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string {
        return "confirm_json_scanner_block_delete";
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl() {
        return new Url('json_scanner_block.list_data');
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion() {
        return t('Do you want to delete %id?', ['%id' => $this->id]);
    }

}
