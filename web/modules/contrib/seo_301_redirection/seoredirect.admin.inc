<?php
/*
 * form function
 */

function seoredirect_form($form, &$form_state)
{
    
    $form['#attributes'] = array(
        'enctype' => 'multipart/form-data'
    );
    # the options to display checkboxes
    $options = array(
        'trailingslash' => t('Remove Trailing Slash From URL.'),
        'removeindex' => t('Remove index.php from URL.'),
        'urllower' => t('Convert all URLs to lower case.')
    );
    # the drupal checkboxes form field definition
    $form['seosettings'] = array(
        '#title' => t(''),
        '#type' => 'checkboxes',
        '#options' => $options,
        '#default_value' => variable_get('seosettings', array(
            'page'
        )),
        '#prefix'=>"<strong>This form will do 301 redirection for following options.</strong>",
    );
    $form['url_exception'] = array(
        '#type' => 'textarea',
        '#title' => t('Enter substring of URLs which will not be converted to lower case in new lines.'),
        '#default_value' => variable_get('url_exception', array(
            'page'
        )),
        '#suffix' => "<em style='color:green;'>URLs which contain strings <strong>/admin/</strong> or <strong>/ajax/</strong> or <strong>sites/default/files</strong> are not converted to lower case.</em>",
        '#attributes' => array(
            "placeholder" => "/admin/\n/ajax/\nsites/default/files"
        )
    );
    return system_settings_form($form, TRUE);
}