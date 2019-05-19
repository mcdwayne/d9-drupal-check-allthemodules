<?php

/**
 * @file
 * Contains \Drupal\timelogin\Form\TimeloginTimeslotDeleteConfirm.
 */

namespace Drupal\timelogin\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\ban\BanIpManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TimeloginTimeslotDeleteConfirm extends ConfirmFormBase {

    /**
     * The ID of the item to delete.
     *
     * @var string
     */
    protected $id;

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'timelogin_timeslot_delete_confirm';
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion() {
        return t('Are you sure you want to delete %id?', array('%id' => $this->id));
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription() {
        return $this->t('This action cannot be undone.');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText() {
        return $this->t('Confirm');
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl() {
        return new Url('timelogin.manage_timeslot');
    }

    /**
     * {@inheritdoc}
     *
     * @param string $ban_id
     *   The IP address record ID to unban.
     */
    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL, $operation = NULL) {
        if (is_numeric($id) && $operation == 'delete') {
            $this->id = $id;
            return parent::buildForm($form, $form_state);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        if ($form_state->getValue(['confirm'])) {
            //Delete record for this id.
            timelogin_timeslot_delete($this->id);
            drupal_set_message(t('Specific time slot has been deleted successfully.'));
            $form_state->setRedirectUrl($this->getCancelUrl());
        }
    }

}
