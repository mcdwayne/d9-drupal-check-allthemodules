<?php
/**
 * @file
 * Contains \Drupal\spectra_flat\Form\SpectraFlatStatementForm.
 */

namespace Drupal\spectra_flat\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the spectra_flat_statement entity edit forms.
 *
 * @ingroup spectra_flat
 */
class SpectraFlatStatementForm extends ContentEntityForm {

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        /* @var $entity \Drupal\spectra_flat\Entity\SpectraFlatStatement */
        $form = parent::buildForm($form, $form_state);

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state) {
        // Redirect to term list after save.
        $form_state->setRedirect('entity.spectra_flat_statement.collection');
        $entity = $this->getEntity();
        $entity->save();
    }

}
