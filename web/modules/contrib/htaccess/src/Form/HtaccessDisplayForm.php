<?php

/**
 * @file
 * Administration pages.
 */

/**
 * Admin settings.
 */

namespace Drupal\htaccess\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
//use Drupal\Component\Utility\UrlHelper;

/**
 * Defines a form to configure RSVP List module settings
 */
class HtaccessDisplayForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'htaccess_admin_display';
  }

  /**
  * {@inheritdoc}
  */
 protected function getEditableConfigNames() {
   return [
   'htaccess.settings'
   ];
 }

  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('htaccess.settings');
    $path = $request->getPathInfo();

    $parts = explode('/', $path);

    $action = $parts[6];
    $id = $parts[7];

    $select = Database::getConnection()->select('htaccess', 'h');
    $select->fields('h');
    $select->condition('id', $id);

    $results = $select->execute();

    $result = $results->fetch();

    $deploymentlink = Link::createFromRoute('Deployment Page', 'htaccess.admin_deployment');

    $htaccess = "<p>". t('Back to %deployment_page', array('%deployment_page' => $deploymentlink->toString())) ."</p>";
    $htaccess .= "<code>".str_replace(PHP_EOL, "<br />", \Drupal\Component\Utility\SafeMarkup::checkPlain($result->htaccess))."</code>";

    $form['htaccess_settings_display'] = array(
      '#type' => 'fieldset',
      '#title' => $id.'. '.$result->name,
      '#description' => $htaccess,
    );

    return parent::buildForm($form,$form_state);
  }

  

  public function htaccess_download($id){
    $htaccess_get = db_select('htaccess', 'h')
      ->fields('h')
      ->condition('id', array(':id' => $id),'IN')
      ->execute()
      ->fetchAssoc();

    $htaccess_content = $htaccess_get['htaccess'];

    // Remove utf8-BOM
    $htaccess_content = str_replace("\xEF\xBB\xBF",'', $htaccess_content);

    $file_name = $htaccess_get['name'].'.htaccess';

    $htaccess_folder = 'public://htaccess';

    if(file_prepare_directory($htaccess_folder, FILE_CREATE_DIRECTORY)) {

      file_create_htaccess($htaccess_folder, true, false);

      $htaccess_file = file_unmanaged_save_data($htaccess_content, $htaccess_folder. '/' .$file_name, FILE_EXISTS_REPLACE);

      file_transfer($htaccess_file, array(
        'Content-Type' => 'application/octet-stream',
        'Content-disposition' => 'attachment; filename='.$file_name));
    }
  }

  public function htaccess_delete($id){
    // Check that the profile is not in use
    $htaccess_check = db_select('htaccess', 'h')
     ->fields('h')
     ->condition('deployed', 1, '=')
     ->condition('id', array(':id' => $id),'IN')
     ->execute()
     ->fetchAssoc();

    if($htaccess_check){
      drupal_set_message(t('This htaccess\'s profile is currently in use'), 'error');
     }
    else{
      $htaccess_get = db_delete('htaccess')
        ->condition('id', array(':id' => $id))
        ->execute();

      drupal_set_message(t('Htacces profile has been removed.'));
    }

    drupal_goto("admin/config/system/htaccess/deployment");
  }

  /**
   * Admin htaccess generate validation handler.
   */
  public function htaccess_admin_settings_generate_validate($form, &$form_state) {
    $profile_name = $form_state['values']['htaccess_settings_generate_name'];

    if(preg_match('/[^a-z0-9]/', $profile_name)) {
       form_error($form, t('The name of the profile must be lowercase and without any special character.'));
    }
    // The name of the profile must be unique
    $htaccess_name = db_select('htaccess', 'h')
      ->fields('h')
      ->condition('name', $profile_name, '=')
      ->execute()
      ->fetchAssoc();

    if($htaccess_name){
      form_error($form, t('The profile @profile already exists.', array('@profile' => $profile_name)));
    }
  }

  /**
   * Admin htaccess generate submit handler.
   */
  public function htaccess_admin_settings_generate_submit($form, &$form_state) {
    \Drupal::moduleHandler()->invokeAll("htaccess_generate_before");

    $htaccess_template = file_get_contents(HTACCESS_TEMPLATE_PATH);

    $rules_before_config = \Drupal::config('htaccess.settings')->get('htaccess_settings_custom_settings');

    $redirection_config = \Drupal::config('htaccess.settings')->get('htaccess_settings_url_prefix_redirection');

    $ssl_config = (\Drupal::config('htaccess.settings')->get('htaccess_settings_ssl') == 'HTTPS_mixed_mode' ? "%{ENV:protossl}" : "s");

    $boot_rules = \Drupal::config('htaccess.settings')->get('htaccess_settings_boost_module_rules');

    switch ($redirection_config) {
      case 'without_www':
        $without_www_config = "RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]" . PHP_EOL;
        $without_www_config.= "RewriteRule ^ http". $ssl_config ."://%1%{REQUEST_URI} [L,R=301]" . PHP_EOL;
        $with_www_config = "#RewriteCond %{HTTP_HOST} ." . PHP_EOL;
        $with_www_config .= "#RewriteCond %{HTTP_HOST} !^www\. [NC]" . PHP_EOL;
        $with_www_config .= "#RewriteRule ^ http". $ssl_config ."://www.%{HTTP_HOST}%{REQUEST_URI} [L,R=301]" . PHP_EOL;
        break;
      case 'with_www':
        $without_www_config = "#RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]" . PHP_EOL;
        $without_www_config.= "#RewriteRule ^ http". $ssl_config ."://%1%{REQUEST_URI} [L,R=301]" . PHP_EOL;
        $with_www_config = "RewriteCond %{HTTP_HOST} ." . PHP_EOL;
        $with_www_config .= "RewriteCond %{HTTP_HOST} !^www\. [NC]" . PHP_EOL;
        $with_www_config .= "RewriteRule ^ http". $ssl_config ."://www.%{HTTP_HOST}%{REQUEST_URI} [L,R=301]" . PHP_EOL;
        break;
      default:
        $without_www_config = "#RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC] " . PHP_EOL;
        $without_www_config.= "#RewriteRule ^ http". $ssl_config ."://%1%{REQUEST_URI} [L,R=301]" . PHP_EOL;
        $with_www_config = "#RewriteCond %{HTTP_HOST} ." . PHP_EOL;
        $with_www_config .= "#RewriteCond %{HTTP_HOST} !^www\. [NC]" . PHP_EOL;
        $with_www_config .= "#RewriteRule ^ http". $ssl_config ."://www.%{HTTP_HOST}%{REQUEST_URI} [L,R=301]" . PHP_EOL;
        break;
    }

    $symbolic_links_config = \Drupal::config('htaccess.settings')->get('htaccess_settings_symlinks');

    switch ($symbolic_links_config) {
      case 'FollowSymLinks':
        $symbolic_links_config = "+FollowSymLinks";
        break;
      case 'SymLinksifOwnerMatch':
        $symbolic_links_config = "+SymLinksifOwnerMatch";
        break;
      default:
        $symbolic_links_config = "+FollowSymLinks";
        break;
    }

    $ssl_force_redirect_rules = "# Force redirect HTTPS." . PHP_EOL;
    $ssl_force_redirect_rules .= "RewriteCond %{HTTPS} off" . PHP_EOL;
    $ssl_force_redirect_rules .= "RewriteCond %{HTTP:X-Forwarded-Proto} !https" . PHP_EOL;
    $ssl_force_redirect_rules .= "RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]" . PHP_EOL;

    $ssl_force_redirect = (\Drupal::config('htaccess.settings')->get('htaccess_settings_ssl') == 'HTTPS_mixed_mode' ? NULL : $ssl_force_redirect_rules);

    $search = array("%%%rules_before%%%", "%%%symbolic_links%%%", "%%%ssl_force_redirect%%%", "%%%with_www%%%", "%%%without_www%%%", "%%%boost_rules%%%");
    $replace = array($rules_before_config, $symbolic_links_config, $ssl_force_redirect, $with_www_config, $without_www_config, $boot_rules);

    $htaccess = str_replace($search, $replace, $htaccess_template);

    $htaccess_profile_name = $form_state['values']['htaccess_settings_generate_name'];
    $htaccess_description = $form_state['values']['htaccess_settings_generate_description'];

    db_insert('htaccess')->fields(array(
      'name' => $htaccess_profile_name,
      'description' => $htaccess_description,
      'htaccess' => $htaccess,
      'created' => REQUEST_TIME,
    ))->execute();

    \Drupal::moduleHandler()->invokeAll("htaccess_generate_after", [$htaccess]);

    drupal_set_message(t('A new htaccess profile has been generated.'));

    drupal_goto("admin/config/system/htaccess/deployment");
  }
}
