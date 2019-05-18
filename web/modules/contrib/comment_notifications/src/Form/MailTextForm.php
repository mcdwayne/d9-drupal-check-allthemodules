<?php

namespace Drupal\comment_notification\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class MailTextForm extends ConfigFormBase{
	public function getFormId() {
		return 'mail_settings';
    }
    
    protected function getEditableConfigNames() {
    	return [
    	'comment_notification.mail_settings',
        ];
    }
    
    public function buildForm(array $form, FormStateInterface $form_state){
    	$config = $this->config('comment_notification.mail_settings');
    	$form['anonymous_usermail'] = array(
    		'#type' => 'textarea',
            '#title' => $this->t('Enter mail text for anonymous user.'),
    		'#default_value' => $config->get('comment_notification.anonymous_usermail'),
    	);
        $form['mail_approver'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Enter mail text for node author for approval.'),
            '#default_value' => $config->get('comment_notification.mail_approver'),
        );
        $form['authenticated_usermail'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Enter mail text for authenticated user.'),
            '#default_value' => $config->get('comment_notification.authenticated_usermail'),
        );
        $form['author_notification'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Enter mail text for node author.'),
            '#default_value' => $config->get('comment_notification.author_notification'),
        );
        return parent::buildForm($form, $form_state);
    }
    
    public function submitForm(array &$form, FormStateInterface $form_state) {
    	$config = $this->config('comment_notification.mail_settings');
        $config->set('comment_notification.anonymous_usermail', $form_state->getValue('anonymous_usermail'));
        $config->set('comment_notification.mail_approver', $form_state->getValue('mail_approver'));
        $config->set('comment_notification.authenticated_usermail', $form_state->getValue('authenticated_usermail'));
        $config->set('comment_notification.author_notification', $form_state->getValue('author_notification'));
        $config->save();
        parent::submitForm($form, $form_state);
    }
}