<?php

namespace Drupal\planyo\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

class ModuleConfigurationForm extends ConfigFormBase {
  public function getFormId() {
    return 'planyo_admin_settings';
  }

  protected function getEditableConfigNames() {
    return [
      'planyo.settings',
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('planyo.settings');

    $form['planyo_site_id'] = array(
                                    '#type' => 'textfield',
                                    '#title' => $this->t('Planyo site ID'),
                                    '#default_value' => $config->get('planyo_site_id') ? $config->get('planyo_site_id') : 'demo',
                                    '#description' => $this->t("ID of your planyo site. If you don't have a planyo site yet, create one first at www.planyo.com. The default value (demo) will use a demonstration site."),
                                    '#required' => TRUE,
                                    );

    $form['planyo_language'] = array(
                                     '#type' => 'select',
                                     '#title' => $this->t('Language of Planyo interface'),
                                     '#default_value' => $config->get('planyo_language') !== null ? $config->get('planyo_language') : '0',
                                     '#description' => $this->t('Choose one of the supported languages. You can also modify the templates (in your planyo administration panel) to display the language choice to the user or pass the language as a parameter in the URL (lang).'),
                                     '#options' => array('0' => $this->t('Auto-detect'),
                                                         'EN' => $this->t('English'),
                                                         'FR' => $this->t('French'),
                                                         'IT' => $this->t('Italian'),
                                                         'ES' => $this->t('Spanish'),
                                                         'DE' => $this->t('German'),
                                                         'PL' => $this->t('Polish'),
                                                         'SV' => $this->t('Swedish'),
                                                         'DA' => $this->t('Danish'),
                                                         'FI' => $this->t('Finnish'),
                                                         'BR' => $this->t('Portuguese (Brazil)'),
                                                         'PT' => $this->t('Portuguese (Portugal)'),
                                                         'RU' => $this->t('Russian'),
                                                         'NL' => $this->t('Dutch'),
                                                         'EL' => $this->t('Greek'),
                                                         'RO' => $this->t('Romanian'),
                                                         'IS' => $this->t('Icelandic'),
                                                         'NO' => $this->t('Norwegian'),
                                                         'CS' => $this->t('Czech'),
                                                         'HR' => $this->t('Croatian'),
                                                         'ET' => $this->t('Estonian'),
                                                         'KL' => $this->t('Greenlandic'),
                                                         'HU' => $this->t('Hungarian'),
                                                         'SK' => $this->t('Slovak'),
                                                         'SL' => $this->t('Slovenian'),
                                                         'KO' => $this->t('Korean'),
                                                         'CA' => $this->t('Catalan'),
                                                         'ET' => $this->t('Estonian'),
                                                         'JA' => $this->t('Japanese'),
                                                         'TW' => $this->t('Chinese (Traditional)')),
                                     );

    $form['planyo_page_title'] = array(
                                       '#type' => 'textfield',
                                       '#title' => $this->t('Title of the Planyo module page'),
                                       '#default_value' => $config->get('planyo_page_title') ? $config->get('planyo_page_title') : 'Reservation',
                                       '#description' => $this->t('Enter the title used on the module page'),
                                       '#required' => FALSE,
                                       );

    /*    $form['planyo_page_path'] = array(
                                      '#type' => 'textfield',
                                      '#title' => $this->t('Path to the Planyo module page'),
                                      '#default_value' => $config->get('planyo_page_path') ? $config->get('planyo_page_path') : 'planyo',
                                      '#description' => $this->t('Enter the relative path that will be used to display the content from Planyo. The default value will make the module accessible at ?q=planyo or /planyo. You can use the special value &lt;block&gt; if you don&apos;t want to create a separate page but only wish to use planyo in blocks (on a page of your choice). You may need to clear the cache after changing this value.'),
                                      '#required' => FALSE,
                                      );*/

    /*
    $block_options = array();
    for ($bo = 0; $bo < 50; $bo++) {
      $block_options ["$bo"] = t("$bo");
    }

    $form['planyo_block_count'] = array(
                                        '#type' => 'select',
                                        '#title' => $this->t('Number of drupal blocks to be created'),
                                        '#default_value' => $config->get('planyo_block_count') ? $config->get('planyo_block_count') : 1,
                                        '#description' => $this->t('Enter the number of blocks which you want to be added to the drupal blocks page. Each block can be used to display different type of content. By default it is the default mode specified below but you can change the attribute string of each block to display different content (e.g. a widget showing details of a resource, upcoming availability or a search box).'),
                                        '#options' => $block_options,
                                        );
    */

    $form['planyo_default_mode'] = array(
                                         '#type' => 'select',
                                         '#title' => $this->t('Default mode'),
                                         '#default_value' => $config->get('planyo_default_mode') ? $config->get('planyo_default_mode') : 'resource_list',
                                         '#description' => $this->t("Choose the initial (default) mode: 'Search box' to allow clients to search for available dates or 'Resource list' to display a list of all resources (in such case search must be initiated by embedding an extra search box -- see last step of integration in Planyo's admin panel). Choosing 'Do nothing' will not display anything by default but will require you to either pass the resource ID to the module as parameter in the URL (resource_id) or add an external search box or calendar preview."),
                                         '#options' => array('search' => $this->t('Search box'),
                                                             'resource_list' => $this->t('Resource list'),
                                                             'upcoming_availability' => $this->t('Upcoming availability'),
                                                             'empty' => $this->t('Do nothing')),
                                         );

    $form['planyo_extra_search_fields'] = array(
                                                '#type' => 'textfield',
                                                '#maxlength' => 512,
                                                '#title' => $this->t('Additional fields of the search box (search mode)'),
                                                '#default_value' => $config->get('planyo_extra_search_fields') !== null ? $config->get('planyo_extra_search_fields') : '',
                                                '#description' => $this->t("Comma-separated extra fields of the search box. Can be left empty. Example: 'Number of persons'. You first need to define these fields in settings/custom resource properties"),
                                                '#required' => FALSE,
                                                );

    $form['planyo_sort_fields'] = array(
                                        '#type' => 'textfield',
                                        '#title' => $this->t('Sort-by field choices (search mode)'),
                                        '#default_value' => $config->get('planyo_sort_fields') ? $config->get('planyo_sort_fields') : 'name,price',
                                        '#description' => $this->t('Comma-separated possible sort fields. A single value will hide this parameter, more than one value will give the user a choice in form of a drop-down box. Allowed values: name, price, prop_res_xxx (custom resource properties). Can be left empty'),
                                        '#required' => FALSE,
                                        );

    $form['planyo_resource_ordering'] = array(
                                              '#type' => 'textfield',
                                              '#title' => $this->t('Ordering of resources (resource list mode)'),
                                              '#default_value' => $config->get('planyo_resource_ordering') ? $config->get('planyo_resource_ordering') : 'name',
                                              '#description' => $this->t('Sorting criterium for the listing of resources in the resource list view. This can be set to name (this is the default) which sorts by resource name, or one of prop_res_xxx (custom resource property defined in Planyo). Can be left empty.'),
                                              '#required' => FALSE,
                                              );

    $form['planyo_use_login'] = array(
                                      '#type' => 'select',
                                      '#title' => $this->t('Integrate with drupal login'),
                                      '#default_value' => $config->get('planyo_use_login') !== null ? $config->get('planyo_use_login') : '0',
                                      '#description' => $this->t("Choose whether the plugin should use the login information from this drupal site. If used, the reservation form items will be automatically prefilled with known values and subsequent reservations will use previously entered data."),
                                      '#options' => array('1' => $this->t('Yes'),
                                                          '0' => $this->t('No')),
                                      );

    $form['planyo_login_integration_code'] = array(
                                                   '#type' => 'textfield',
                                                   '#title' => $this->t('Login integration code'),
                                                   '#default_value' => $config->get('planyo_login_integration_code') !== null ? $config->get('planyo_login_integration_code') : '',
                                                   '#description' => $this->t("If integration with drupal login is switched on, you will need to enter the login integration code which you'll find in advanced integration settings in the Planyo backend."),
                                                   '#required' => FALSE,
                                                   );

    /*    $form['planyo_seo_friendly'] = array(
                                         '#type' => 'select',
                                         '#title' => $this->t('SEO friendly'),
                                         '#default_value' => $config->get('planyo_seo_friendly') !== null ? $config->get('planyo_seo_friendly') : '1',
                                         '#description' => $this->t("Choose whether the plugin in the resource list and resource details modes should be SEO friendly (information retrieved from the server when loading the page) or not (information retrieved using Javascript/AJAX). Choosing yes will add a slight delay to the loading time of the page but will let search engines index the resource names, descriptions and photos."),
                                         '#options' => array('1' => $this->t('Yes'),
                                                             '0' => $this->t('No')),
                                         );
    */

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('planyo.settings')
      ->set('planyo_site_id', $values['planyo_site_id'])
      ->set('planyo_page_title', $values['planyo_page_title'])
      ->set('planyo_page_path', $values['planyo_page_path'])
      ->set('planyo_block_count', $values['planyo_block_count'])
      ->set('planyo_default_mode', $values['planyo_default_mode'])
      ->set('planyo_extra_search_fields', $values['planyo_extra_search_fields'])
      ->set('planyo_sort_fields', $values['planyo_sort_fields'])
      ->set('planyo_resource_ordering', $values['planyo_resource_ordering'])
      ->set('planyo_use_login', $values['planyo_use_login'])
      ->set('planyo_login_integration_code', $values['planyo_login_integration_code'])
      ->set('planyo_seo_friendly', $values['planyo_seo_friendly'])
      ->save();
  }
}