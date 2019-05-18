<?php

/**
 * @file
 * Contains \Drupal\restrict_abusive_words\Form\DeleteWordForm.
 */

namespace Drupal\restrict_abusive_words\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

/**
 * Contribute form.
 */
class DeleteWordForm extends ConfirmFormBase {

    protected $wid;

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'restrict_abusive_words_admin_form_delete_confirm';
    }

    /**
     * {@inheritdoc}
     * */
    public function getQuestion() {
        return t('Are you sure you want to delete this word or phrase from the abusive word list?', array('%id' => $this->id));
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl() {
        return new Url('restrict_abusive_words.add_words');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText() {
        return $this->t('Delete');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $wid = NULL) {
        $this->wid = $wid;
        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $connection = Database::getConnection();

        if(!empty($this->wid)) {
          $connection->delete('restrict_abusive_words')
            ->condition('id', $this->wid)
            ->execute();
        }
        $form_state->setRedirect('restrict_abusive_words.list_words');
    }

}
