<?php

namespace Drupal\dblog_quick_filter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;

/**
 * Class FilterForm.
 *
 * @package Drupal\dblog_quick_filter\Form
 */
class FilterForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'filter_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        
        $types= array();
        $rest_btn = array();
        
        $form['filters'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('Search log message'),
        );
        
        $form['filters']['ajs_filter'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Search'),
            '#description' => $this->t('Enter search term'),
            '#maxlength' => 64,
            '#size' => 20,
            '#attributes' => array('ng-model' => 'query'),
            '#suffix' => '<div id="feedLoading" ng-show="isLoading" class="display-loader">Loading...</div>',
        );

        $form['filters']['refresh'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Auto Refresh'),
            '#options' => array('1' => $this->t('On')),
            '#attributes' => array(
                'ng-model' => 'isRefresh',
            ),
            '#prefix' => '<div class="radio-holder">'
        );
                        
        $form['filters']['ajs_filter_case'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Match case'),
            '#options' => array('1' => $this->t('On')),
            '#attributes' => array(
                'ng-model' => 'match',
            ),
            '#suffix' => '</div>'
        );
        
        foreach (_dblog_get_message_types() as $type) {
           $types[$type] = $type;
        }
                
        $rest_btn = array(
            'rest_btn' => array(
                '#type' => 'inline_template',
                '#template' => '<a href="javascript:;" ng-click="clearFilter(\'type\');" >#Clear</a>',
            )
          ) ;

        $form['filters']['type'] = array(
            '#type' => 'select',
            '#title' => $this->t('Type'),
            '#options' => $types,
            '#multiple' => TRUE,
            '#size' => 8,
            '#attributes' => array(
                'ng-model' => 'type',
            ),
            '#field_suffix' => render($rest_btn),
        );
        
        $rest_btn2 = array(
            'rest_btn'=> array(
                '#type' => 'inline_template',
                '#template' => '<a href="javascript:;" ng-click="clearFilter(\'severity\')" >#Clear</a>',
              )
            );
        $form['filters']['severity'] = array(
            '#type' => 'select',
            '#title' => $this->t('Severity'),
            '#options' => RfcLogLevel::getLevels(),
            '#multiple' => TRUE,
            '#size' => 8,
            '#attributes' => array(
                'ng-model' => 'severity',
            ),
            '#field_suffix' => render($rest_btn2),
        );
        
        $rest_btn3 = array(
                'rest_btn'=> array(
                    '#type' => 'inline_template',
                    '#template' => '<a href="javascript:;" ng-click="clearFilter(\'user\')" >#Clear</a>',
                  )
            ) ;
        $logged_users = dblog_quick_filter_get_logged_users();
        $logged_users['Anonymous'] = t('Anonymous');
        $form['filters']['user'] = array(
            '#type' => 'select',
            '#title' => $this->t('User'),
            '#options' => $logged_users,
            '#multiple' => TRUE,
            '#size' => 8,
            '#attributes' => array(
                'ng-model' => 'user',
            ),
            '#field_suffix' => render($rest_btn3),
        );
        
       
        $form['filters']['items_display'] = array(
            '#type' => 'select',
            '#title' => $this->t('Items Display'),
            '#options' => array(
                '10' => $this->t('10'),
                '100' => $this->t('100'),
                '1000' => $this->t('1000'),
                '5000' => $this->t('5000')
            ),
            '#size' => 3,
            '#attributes' => array(
                'ng-model' => 'perpage',
                'ng-change' => 'perPage()',
            ),
        );

        $form['#attached']['library'][]  =  'dblog_quick_filter/dblog_quick_filter';
        
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        
    }

}
