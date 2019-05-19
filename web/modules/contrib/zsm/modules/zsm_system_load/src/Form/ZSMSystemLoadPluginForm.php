<?php
/**
 * @file
 * Contains \Drupal\zsm_system_load\Form\ZSMSystemLoadPluginForm.
 */

namespace Drupal\zsm_system_load\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zsm_core entity edit forms.
 *
 * @ingroup zsm_system_load
 */
class ZSMSystemLoadPluginForm extends ContentEntityForm {

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        /* @var $entity \Drupal\zsm_system_load\Entity\ZSMSystemLoadPlugin */
        $form = parent::buildForm($form, $form_state);

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state) {
        // Redirect to term list after save.
        $form_state->setRedirect('entity.zsm_system_load_plugin.collection');
        $entity = $this->getEntity();
        $entity->save();
    }

}
