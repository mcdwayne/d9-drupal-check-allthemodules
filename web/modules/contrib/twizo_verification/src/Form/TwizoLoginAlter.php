<?php
/**
 * Created by PhpStorm.
 * User: WesselVrolijks
 * Date: 24/01/2018
 * Time: 16:07
 */

namespace Drupal\twizo\Form;


use Drupal\Core\Form\FormStateInterface;

class TwizoLoginAlter
{
    public function twizo_form_alter(&$form, FormStateInterface $form_state, $form_id){
        switch($form_id){
            case 'user_login_block':
            case 'user_login':
            case 'user_login_form':
                // Prevents login even if submit button is placed
                $form['#submit'][] = 'twizo_unload_user';

                // Deletes original form button.
                unset($form['actions']['submit']);

                // Adds ajax submit button.
                $form['twizo_login']['ajax-submit'] = array(
                    '#type' => 'button',
                    '#value' => t('Log in'),
                    '#ajax' => array(
                        'callback' => 'twizo_widget_login',
                        'wrapper' => 'twizo_ajax_login',
                        'method' => 'replace',
                        'effect' => 'fade',
                    ),
                );
        }
    }

}