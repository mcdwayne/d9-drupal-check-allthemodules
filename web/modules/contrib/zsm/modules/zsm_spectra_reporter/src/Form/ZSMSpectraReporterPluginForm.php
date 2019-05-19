<?php
/**
 * @file
 * Contains \Drupal\zsm_spectra_reporter\Form\ZSMSpectraReporterPluginForm.
 */

namespace Drupal\zsm_spectra_reporter\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zsm_core entity edit forms.
 *
 * @ingroup zsm_spectra_reporter
 */
class ZSMSpectraReporterPluginForm extends ContentEntityForm {

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        /* @var $entity \Drupal\zsm_spectra_reporter\Entity\ZSMSpectraReporterPlugin */
        $form = parent::buildForm($form, $form_state);

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state) {
        // Redirect to term list after save.
        $form_state->setRedirect('entity.zsm_spectra_reporter_plugin.collection');
        $entity = $this->getEntity();
        $entity->save();
    }

}
