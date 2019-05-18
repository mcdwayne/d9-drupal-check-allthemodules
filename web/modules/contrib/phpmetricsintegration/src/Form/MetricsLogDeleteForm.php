<?php

namespace Drupal\phpmetricsintegration\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete an MetricsLog.
 */

class MetricsLogDeleteForm extends EntityConfirmFormBase
{

  /**
   * {@inheritdoc}
   */
    public function getQuestion()
    {
        return $this->t('Are you sure you want to delete %name?', array('%name' => $this->entity->id()));
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        return new Url('entity.phpmetricsintegration.collection');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText()
    {
        return $this->t('Delete');
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $created = $this->entity->getCreated();
        $reportDir = $this->entity->getReportPath() . "-" . $created;
        $dirReportLoc = "sites/default/files/" . $reportDir;
        $command = "rm -rf ".$dirReportLoc;
        $op = [];
        $status = 0;
        exec($command, $op, $status);
        $this->entity->delete();
        drupal_set_message(
            $this->t(
                'Analysis log %label has been deleted.', 
                array(
                    '%label' => $this->entity->getCreated()
                )
            )
        );

        $form_state->setRedirectUrl($this->getCancelUrl());
    }
}
