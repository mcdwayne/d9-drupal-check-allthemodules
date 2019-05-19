<?php
/**
 * @file
 * Contains \Drupal\zsm_memswap\Form\ZSMMemSwapPluginForm.
 */

namespace Drupal\zsm_memswap\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zsm_core entity edit forms.
 *
 * @ingroup zsm_memswap
 */
class ZSMMemSwapPluginForm extends ContentEntityForm {

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        /* @var $entity \Drupal\zsm_memswap\Entity\ZSMMemSwapPlugin */
        $form = parent::buildForm($form, $form_state);

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state) {
        // Redirect to term list after save.
        $form_state->setRedirect('entity.zsm_memswap_plugin.collection');
        $entity = $this->getEntity();
        $entity->save();
    }

}
