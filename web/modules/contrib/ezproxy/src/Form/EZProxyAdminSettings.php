<?php
/**
 * @file
 * Contains \Drupal\ezproxy\Form\EZProxyAdminSettings.
 */

namespace Drupal\ezproxy\Form;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity;
use Drupal\field\FieldConfigInterface;

class EZProxyAdminSettings extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'ezproxy_admin_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return ['ezproxy.settings'];
    }

    public function buildForm(array $form, FormStateInterface $form_state, Request $request = null) {
        $config = $this->config('ezproxy.settings');

		$host = $config->get('ezproxy_host');
        $port = $config->get('ezproxy_port');
        $ticket = $config->get('ezproxy_ticket_secret');
        
        $form = array();
  
        $form['ezproxy_host'] = array(
            '#type' => 'textfield',
            '#title' => t('EZproxy host'),
            '#default_value' => $host,
            '#description' => t('The hostname of your exproxy server'),
        );
        $form['ezproxy_port'] = array(
            '#type' => 'textfield',
            '#title' => t('EZproxy port'),
            '#default_value' => $port,
            '#description' => t('The port used by the exproxy server'),
        );
        $form['ezproxy_ticket_secret'] = array(
            '#type' => 'textfield',
            '#title' => t('EZproxy secret'),
            '#default_value' => $ticket,
            '#description' => t('NOTE this is only used with the ticket and CGI authentication methods'),
        );
        $form['submit'] = array(
    		'#type' => 'submit',
    		'#value' => 'Submit',
  		);

        return $form;
    }
	
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config('ezproxy.settings')
            ->set('ezproxy_host', $form_state->getValue('ezproxy_host'))
            ->set('ezproxy_port', $form_state->getValue('ezproxy_port'))
            ->set('ezproxy_ticket_secret', $form_state->getValue('ezproxy_ticket_secret'))
            ->save();
        parent::submitForm($form, $form_state);
    }
}