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
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form to configure RSVP List module settings
 */
class HtaccessDeploymentForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'htaccess_admin_deployment';
  }

  /**
  * {@inheritdoc}
  */
 protected function getEditableConfigNames() {
   return [
   'htaccess.settings'
   ];
 }

 /**
 * {@inheritdoc}
 */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

    // Get the current htaccess deployed
    $select = Database::getConnection()->select('htaccess', 'h');
    $select->fields('h');
    $select->condition('deployed', 1);

    $results = $select->execute();
    $result = $results->fetch();

    if (!empty($result)) {

      $current = $result->name;
    }
    else {
      $current = t('none');
    }

    $form['htaccess_settings_current'] = array(
    '#type' => 'fieldset',
    '#title' => t('Status'),
    '#description' => t('Current deployed profile: <b>@current</b>.', array('@current' => $current)),
    );


    $form['htaccess_settings_version'] = array(
      '#prefix' => '<table>',
      '#suffix' => '</table>',
      '#tree' => TRUE,
      '#weight' => '110',
    );

    $form['htaccess_settings_version']['htaccess_settings_version_header'] = array(
    '#markup' => '<thead>
      <tr>
        <th>'.t('ID').'</th>
        <th>'.t('Created date').'</th>
        <th>'.t('Name').'</th>
        <th>'.t('Description').'</th>
        <th>'.t('Operations').'</th>
      </tr>
    </thead>',
    );

    $htaccess = Database::getConnection()->select('htaccess', 'h');
    $htaccess->fields('h');

    $results = $htaccess->execute();

    //$htaccess_count = count($results);

    $i=0;
    //for ($i=0; $i<$htaccess_count; $i++) {
    foreach($results as $result) {

      $form['htaccess_settings_version']['row_' . $i] = array(
        '#prefix' => '<tr class="'.($i % 2 ? "odd" : "even").'">',
        '#suffix' => '</tr>',
      );
      $form['htaccess_settings_version']['row_' . $i]['htaccess_settings_version_number'] = array(
        '#prefix' => '<td>',
        '#suffix' => '</td>',
        '#markup' => $result->id,
      );
      $form['htaccess_settings_version']['row_' . $i]['htaccess_settings_version_created'] = array(
        '#prefix' => '<td>',
        '#suffix' => '</td>',
        '#markup' => format_date($result->created, 'short'),
      );
      $form['htaccess_settings_version']['row_' . $i]['htaccess_settings_version_name'] = array(
        '#prefix' => '<td>',
        '#suffix' => '</td>',
        '#markup' => $result->name,
      );
      $form['htaccess_settings_version']['row_' . $i]['htaccess_settings_version_description'] = array(
        '#prefix' => '<td>',
        '#suffix' => '</td>',
        '#markup' => $result->description,
      );

      $viewlink = Link::createFromRoute('View', 'htaccess.admin_view', ['name' => $result->id]);
      $deploylink = Link::createFromRoute('Deploy', 'htaccess.admin_deploy', ['name' => $result->id]);
      $downloadlink = Link::createFromRoute('Download', 'htaccess.admin_download', ['name' => $result->id]);
      $deletelink = Link::createFromRoute('Delete', 'htaccess.admin_delete', ['name' => $result->id]);

      $form['htaccess_settings_version']['row_' . $i]['htaccess_settings_version_operation'] = array(
        '#markup' => $viewlink->toString() . ' ' . $deploylink->toString() . ' ' . $downloadlink->toString() . ' ' . $deletelink->toString(),
        '#prefix' => '<td>',
        '#suffix' => '</td>',
      );
      $i++;
    }
    return parent::buildForm($form,$form_state);
  }
}
