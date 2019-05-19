<?php
/**
 * @file
 * Contains \Drupal\zsm_backup_date\Form\ZSMBackup_datePluginForm.
 */

namespace Drupal\zsm_backup_date\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zsm_core entity edit forms.
 *
 * @ingroup zsm
 */
class ZSMBackupDatePluginForm extends ContentEntityForm {

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        /* @var $entity \Drupal\zsm_backup_date\Entity\ZSMBackupDatePlugin */
        $form = parent::buildForm($form, $form_state);

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state) {
        // Redirect to term list after save.
        $form_state->setRedirect('entity.zsm_backup_date_plugin.collection');
        $entity = $this->getEntity();
        $entity->save();
    }

}
