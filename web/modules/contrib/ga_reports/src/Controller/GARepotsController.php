<?php

namespace Drupal\ga_reports\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the GA Reports Controller.
 */
class GARepotsController extends ControllerBase {

  /**
   *
   */
  public function content() {
    $build = [];
    $dev_console_url = Url::fromUri('https://console.developers.google.com');
    $dev_console_link = Link::fromTextAndUrl($this->t('Google Developers Console'), $dev_console_url)->toRenderable();
    $dev_console_link['#attributes']['target'] = '_blank';

    $current_path = \Drupal::service('path.current')->getPath();
    $current_path_url = Url::fromUri('base:/' . $current_path, ['absolute' => TRUE]);

    $setup_help = $this->t('To access data from Google Analytics you have to create a new project in Google Developers Console.');
    $setup_help .= '<ol>';
    $setup_help .= '<li>' . $this->t('Open %google_developers_console.', ['%google_developers_console' => render($dev_console_link)]) . '</li>';
    $setup_help .= '<li>' . $this->t('Along the toolbar click the pull down arrow and the press <strong>Create a Project</strong> button, enter project name and press <strong>Create</strong>.') . '</li>';
    $setup_help .= '<li>' . $this->t('Click <strong>Enable and Manage APIs</strong>.') . '</li>';
    $setup_help .= '<li>' . $this->t('In the search box type <strong>Analytics</strong> and then press <strong>Analytics API</strong>, this opens the API page, press <strong>Enable</strong>.') . '</li>';
    $setup_help .= '<li>' . $this->t('Click on <strong>Go to Credentials</strong>') . '</li>';
    $setup_help .= '<li>' . $this->t('Under <strong>Where will you be calling the API from?</strong> select <strong>Web Browser Javascript</strong> and then select <strong>User Data</strong>') . '</li>';
    $setup_help .= '<li>' . $this->t('Hit <strong>What credentials do I need</strong>, edit the name if necessary.') . '</li>';
    $setup_help .= '<li>' . $this->t('Leave empty <strong>Authorized JavaScript origins</strong>, fill in <strong>Authorized redirect URIs</strong> with <code>@url</code> and press <strong>Create Client ID</strong> button.', ['@url' => $current_path_url->toString()]) . '</li>';
    $setup_help .= '<li>' . $this->t('Type a Product name to show to users and hit <strong>Continue</strong> and then <strong>Done</strong>') . '</li>';
    $setup_help .= '<li>' . $this->t('Click on the name of your new client ID to be shown both the <strong>Client ID</strong> and <strong>Client Secret</strong>.') . '</li>';
    $setup_help .= '<li>' . $this->t('Copy <strong>Client ID</strong> and <strong>Client secret</strong> from opened page to the form below.') . '</li>';
    $setup_help .= '<li>' . $this->t('Press <strong>Start setup and authorize account</strong> in the form below and allow the project access to Google Analytics data.') . '</li>';
    $setup_help .= '</ol>';

    $ga_reports_form['setup'] = [
      '#type' => 'details',
      '#title' => $this->t('Setup Google Analytics Reports'),
      '#description' => $setup_help,
      '#open' => TRUE,
    ];
    $build['ga_reports']['form'] = $ga_reports_form;
    return [
      '#markup' => $setup_help,
    ];
  }

}
