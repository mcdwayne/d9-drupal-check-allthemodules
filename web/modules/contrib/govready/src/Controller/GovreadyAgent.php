<?php

/**
 * @file
 * Collects data and sends it to the GovReady API.
 */

namespace Drupal\govready\Controller;

class GovreadyAgent {

  /**
   * Generic callback for ?action=govready_v1_trigger&key&endpoint&siteId.
   *
   * Examples:
   * ?action=govready_v1_trigger&key=plugins&endpoint=plugins&siteId=xxx
   * ?action=govready_v1_trigger&key=accounts&endpoint=accounts&siteId=xxx
   * ?action=govready_v1_trigger&key=stack&endpoint=stack/phpinfo&siteId=xxx.
   */
  public function ping() {
    // print_r($_POST);
    $options = \Drupal::config('govready.settings')->get('govready_options');
    // @todo: check that request is coming from plugin.govready.com, or is properly nonced (for manual refreshes)
    if ($_POST['siteId'] == $options['siteId']) {

      if (!empty($_POST['key'])) {
        $key = $_POST['key'];
        $data = call_user_func(array($this, $key));
        // print_r($data);
        if (!empty($data)) {
          if (!empty($_POST['endpoint'])) {
             print_r($data);
            $endpoint = '/sites/' . $options['siteId'] . '/' . $_POST['endpoint'];
            $return = \Drupal\govready\Controller\GovreadyPage::govready_api($endpoint, 'POST', $data);
            // drupal_json_output($data);
            print_r($return);
          }
          // @TODO return meaningful information
          drupal_json_output(array('response' => 'ok'));
        }
      }

    }
    else {
      print_r('Invalid siteId');
    }
  }

  /**
   * Callback for /govready/trigger, key=plugins.
   */
  public function plugins() {
    $out = array();

    // Hint to use system_rebuild_module_data() came from
    // http://stackoverflow.com/questions/4232113/drupal-how-to-get-the-modules-list
    $modules = system_rebuild_module_data();

    foreach ($modules as $key => $module) {
      
      // Make sure not hidden, testing, core, or submodule.
      $output_module = !(!empty($module->info['hidden']) && $module->info['hidden'] == 1)
                    && !(!empty($module->info['package']) && $module->info['package'] === 'Testing')
                    && !(!empty($module->info['package']) && $module->info['package'] === 'Core')
                    &&  (empty($module->info['project']) || empty($out[$module->info['project']]));

      if ($output_module) {
        $out_key = !empty($module->info['project']) ? $module->info['project'] : $module->name;
        $out[$out_key] = array(
          'label' => $module->info['name'],
          'namespace' => $key,
          'status' => (boolean) $module->status,
          'version' => $module->info['version'],
          'project_link' => !empty($module->info['project']) ? 'https://www.drupal.org/project/' . $module->info['project'] : '',
        );

      } //if

    } //foreach

    return array('plugins' => array_values($out), 'forceDelete' => TRUE);

  }

  /**
   * Callback for ?action=govready_v1_trigger&key=accounts.
   */
  public function accounts() {
    $out = array();

    $accounts = user_load_multiple();

    foreach ($accounts as $key => $account) {
      $data = $account->toArray();
      if ($key > 0) {
        array_push($out, array(
          'accountId' => $data['uid'][0]['value'],
          'accountname' => $data['name'][0]['value'],
          'email' => $data['mail'][0]['value'],
          'name' => $data['name'][0]['value'],
          'created' => $data['created'][0]['value'],
          //'roles' => array_values($account->roles),
          //'superAdmin' => $account->hasPermission('administer site configuration'),
          'lastLogin' => $data['login'][0]['value'],
        ));
      }

    }
    return array('accounts' => $out, 'forceDelete' => TRUE);

  }

  /**
   * Callback for ?action=govready_v1_trigger&key=stack.
   */
  public function stack() {

    $stack = array(
      'os' => php_uname('s') . ' ' . php_uname('r'),
      'language' => 'PHP ' . phpversion(),
      'server' => $_SERVER["SERVER_SOFTWARE"],
      'application' => array(
        'platform' => 'Drupal',
        'version' => \Drupal::VERSION,
      ),
      'database' => function_exists('mysql_get_client_info') ? 'MySQL ' . mysql_get_client_info() : NULL,
    );

    return array('stack' => $stack);

  }

  /**
   * Callback for ?action=govready_v1_trigger&key=changeMode.
   */
  private function changeMode() {

    $options = \Drupal::config('govready.settings')->get('govready_options');
    $options['mode'] = $_POST['mode'];
    \Drupal::configFactory()->getEditable('govready.settings')
      ->set('govready_options', $options)
      ->save();

    return array('mode' => $options['mode']);

  }

}
// Class.
