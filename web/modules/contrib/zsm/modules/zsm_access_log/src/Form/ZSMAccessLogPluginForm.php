<?php
/**
 * @file
 * Contains \Drupal\zsm_access_log\Form\ZSMAccessLogPluginForm.
 */

namespace Drupal\zsm_access_log\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zsm_core entity edit forms.
 *
 * @ingroup zsm_access_log
 */
class ZSMAccessLogPluginForm extends ContentEntityForm {

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        /* @var $entity \Drupal\zsm_access_log\Entity\ZSMAccessLogPlugin */
        $form = parent::buildForm($form, $form_state);

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state) {
        // Redirect to term list after save.
        $form_state->setRedirect('entity.zsm_access_log_plugin.collection');
        $entity = $this->getEntity();
        $entity->save();
    }

}
