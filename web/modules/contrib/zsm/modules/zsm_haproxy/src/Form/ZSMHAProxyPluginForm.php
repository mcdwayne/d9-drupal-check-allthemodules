<?php
/**
 * @file
 * Contains \Drupal\zsm_haproxy\Form\ZSMHAProxyPluginForm.
 */

namespace Drupal\zsm_haproxy\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zsm_core entity edit forms.
 *
 * @ingroup zsm_haproxy
 */
class ZSMHAProxyPluginForm extends ContentEntityForm {

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        /* @var $entity \Drupal\zsm_haproxy\Entity\ZSMSpectraReporterPlugin */
        $form = parent::buildForm($form, $form_state);

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state) {
        // Redirect to term list after save.
        $form_state->setRedirect('entity.zsm_haproxy_plugin.collection');
        $entity = $this->getEntity();
        $entity->save();
    }

}
