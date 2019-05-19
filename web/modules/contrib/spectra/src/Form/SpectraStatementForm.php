<?php
/**
 * @file
 * Contains \Drupal\spectra\Form\SpectraStatementForm.
 */

namespace Drupal\spectra\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the spectra_statement entity edit forms.
 *
 * @ingroup spectra
 */
class SpectraStatementForm extends ContentEntityForm {

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        /* @var $entity \Drupal\spectra\Entity\SpectraStatement */
        $form = parent::buildForm($form, $form_state);

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state) {
        // Redirect to term list after save.
        $form_state->setRedirect('entity.spectra_statement.collection');
        $entity = $this->getEntity();
        $entity->save();
    }

}
