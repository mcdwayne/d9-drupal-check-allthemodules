<?php
/**
 * @file
 * Contains \Drupal\zsm_gitlog\Form\ZSMGitlogPluginForm.
 */

namespace Drupal\zsm_gitlog\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zsm_core entity edit forms.
 *
 * @ingroup zsm_gitlog
 */
class ZSMGitlogPluginForm extends ContentEntityForm {

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        /* @var $entity \Drupal\zsm_gitlog\Entity\ZSMAccessLogPlugin */
        $form = parent::buildForm($form, $form_state);

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state) {
        // Redirect to term list after save.
        $form_state->setRedirect('entity.zsm_gitlog_plugin.collection');
        $entity = $this->getEntity();
        $entity->save();
    }

}
