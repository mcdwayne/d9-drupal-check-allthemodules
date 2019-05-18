<?php

namespace Drupal\chart_suite\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

use Drupal\chart_suite\Branding;

/**
 * Manages an administrator form to adjust the module's configuration.
 *
 * <b>Access control:</b>
 * The route to this form must be restricted to administrators.
 *
 * <b>Route parameters:</b>
 * The route to this form has no parameters.
 *
 * <B>Warning:</B> This class is strictly internal to the Chart Suite
 * module. The class's existance, name, and content may change from
 * release to release without any promise of backwards compatability.
 *
 * @ingroup chart_suite
 */
final class AdminSettings extends ConfigFormBase {

  /*---------------------------------------------------------------------
   *
   * Form utilities.
   *
   *---------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Base the form ID on the namespace-qualified class name, which
    // already has the module name as a prefix.  PHP's get_class()
    // returns a string with "\" separators. Replace them with underbars.
    return str_replace('\\', '_', get_class($this));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['chart_suite.settings'];
  }

  /*---------------------------------------------------------------------
   *
   * Build form.
   *
   *---------------------------------------------------------------------*/

  /**
   * Builds a form to adjust the module settings.
   *
   * The form has multiple vertical tabs, each built by a separate function.
   *
   * @param array $form
   *   An associative array containing the renderable structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   (optional) The current state of the form.
   *
   * @return array
   *   The form renderable array.
   */
  public function buildForm(
    array $form,
    FormStateInterface $formState = NULL) {

    //
    // Vertical tabs
    // -------------
    // Setup vertical tabs. For these to work, all of the children
    // must be of type 'details' and refer to the 'tabs' group.
    $form['tabs'] = [
      '#type'     => 'vertical_tabs',
      '#attached' => [
        'library' => [
          'chart_suite/chart_suite.core',
          'chart_suite/chart_suite.admin',
        ],
      ],
    ];

    // Create each of the tabs.
    $this->buildAboutTab($form, $formState, 'tabs');
    $this->buildGoogleChartsTab($form, $formState, 'tabs');

    // Build and return the form.
    return parent::buildForm($form, $formState);
  }

  /*---------------------------------------------------------------------
   *
   * Validate.
   *
   *---------------------------------------------------------------------*/

  /**
   * Validates the form values.
   *
   * @param array $form
   *   The form configuration.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The entered values for the form.
   */
  public function validateForm(array &$form, FormStateInterface $formState) {

    $this->validateAboutTab($form, $formState);
    $this->validateGoogleChartsTab($form, $formState);
  }

  /*---------------------------------------------------------------------
   *
   * Submit.
   *
   *---------------------------------------------------------------------*/

  /**
   * Stores the submitted form values.
   *
   * @param array $form
   *   The form configuration.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The entered values for the form.
   */
  public function submitForm(array &$form, FormStateInterface $formState) {

    parent::submitForm($form, $formState);

    $this->submitAboutTab($form, $formState);
    $this->submitGoogleChartsTab($form, $formState);
  }

  /*---------------------------------------------------------------------
   *
   * About tab.
   *
   *---------------------------------------------------------------------*/

  /**
   * Builds the about tab for the form.
   *
   * No settings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   * @param string $tabGroup
   *   The name of the tab group.
   */
  private function buildAboutTab(
    array &$form,
    FormStateInterface $formState,
    string $tabGroup) {

    //
    // Setup
    // -----
    // Set up some variables.
    $tabName = 'chart_suite_about_tab';
    $tabDescriptionClass = 'chart_suite-settings-description';

    //
    // Help link
    // ---------
    // Get a link to the module's help, if the help module is installed.
    $mh = \Drupal::service('module_handler');

    $helpLink = '';
    $helpInstalled = $mh->moduleExists('help');
    if ($helpInstalled === TRUE) {
      // The help module exists. Create a link to this module's help.
      $options['name'] = 'chart_suite';
      $lnk = Link::createFromRoute(t('Module help'), 'help.page', $options);
      if ($lnk !== NULL) {
        $helpLink = $lnk->toString();
      }
    }

    // Create a "See also" message with links. Include the help link only
    // if the help module is installed.
    $seeAlso = '';
    if (empty($helpLink) === FALSE) {
      $seeAlso = $this->t("See also") . ' ' . $helpLink . '.';
    }

    //
    // Create the tab
    // --------------
    // Start the tab with a title, subtitle, and description.
    $form[$tabName] = [
      '#type'           => 'details',
      '#open'           => FALSE,
      '#group'          => $tabGroup,
      '#title'          => $this->t('About'),
      '#description'    => [
        'branding'      => Branding::getBannerBranding(),
        'description'   => [
          '#type'       => 'html_tag',
          '#tag'        => 'p',
          '#value'      => $this->t(
            "<strong>Chart Suite</strong> creates interactive charts using data parsed from files referenced by entity file fields. The module supports a variety of textual file formats describing tables, trees, and graphs, including CSV, TSV, HTML, and several JSON formats. Chart types include line and area plots, scatter plots, bar and pie charts, and tree diagrams."),
          '#attributes' => [
            'class'     => [$tabDescriptionClass],
          ],
        ],
      ],
      '#attributes'     => [
        'class'         => [
          'chart_suite-settings-tab ',
          'chart_suite-about-tab',
        ],
      ],
    ];

    if (empty($seeAlso) === FALSE) {
      $form[$tabName]['#description']['seealso'] = [
        '#type'         => 'html_tag',
        '#tag'          => 'p',
        '#value'        => $seeAlso,
      ];
    }
  }

  /**
   * Validates form items from the about tab.
   *
   * @param array $form
   *   The form configuration.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The entered values for the form.
   */
  private function validateAboutTab(
    array &$form,
    FormStateInterface $formState) {
    // Nothing to do.
  }

  /**
   * Stores the submitted form values for the about tab.
   *
   * @param array $form
   *   The form configuration.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The entered values for the form.
   */
  private function submitAboutTab(
    array &$form,
    FormStateInterface $formState) {
    // Nothing to do.
  }

  /*---------------------------------------------------------------------
   *
   * Google Charts tab.
   *
   *---------------------------------------------------------------------*/

  /**
   * Builds the Google Charts tab for the form.
   *
   * No settings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   * @param string $tabGroup
   *   The name of the tab group.
   */
  private function buildGoogleChartsTab(
    array &$form,
    FormStateInterface $formState,
    string $tabGroup) {

    //
    // Setup
    // -----
    // Set up some variables.
    $tabName = 'chart_suite_google_charts_tab';
    $tabDescriptionClass = 'chart_suite-settings-description';
    $tabSubtitleClass = 'chart_suite-settings-subtitle';

    //
    // Create the tab
    // --------------
    // Start the tab with a title, subtitle, and description.
    $form[$tabName] = [
      '#type'           => 'details',
      '#open'           => FALSE,
      '#group'          => $tabGroup,
      '#title'          => 'Google Charts',
      '#description'    => [
        'subtitle'      => [
          '#type'       => 'html_tag',
          '#tag'        => 'h2',
          '#value'      => $this->t('Charting using Google Charts'),
          '#attributes' => [
            'class'     => [$tabSubtitleClass],
          ],
        ],
        'description'   => [
          '#type'       => 'html_tag',
          '#tag'        => 'p',
          '#value'      => $this->t(
            "<strong>Chart Suite</strong> uses the <em>Google Charts</em> service provided by Google. The service's assets are loaded automatically from Google when a user views a page containing a file field and chartable data. The data itself remains local to this site and the user's browser."),
          '#attributes' => [
            'class'     => [$tabDescriptionClass],
          ],
        ],
        'register'      => [
          '#type'       => 'html_tag',
          '#tag'        => 'p',
          '#value'      => $this->t(
            "Google provides the <em>Google Charts</em> service for free. The company does not require a license, developer account, API key, or web site registration to use the service. For further information, including the terms of use, please see @link.",
            [
              '@link' => Link::fromTextAndUrl(
                'Google Charts',
                Url::fromUri('https://developers.google.com/chart/'))->toString(),
            ]),
          '#attributes' => [
            'class'     => [$tabDescriptionClass],
          ],
        ],
      ],
      '#attributes'     => [
        'class'         => [
          'chart_suite-settings-tab ',
          'chart_suite-google-charts-tab',
        ],
      ],
    ];
  }

  /**
   * Validates form items from the Google Charts tab.
   *
   * @param array $form
   *   The form configuration.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The entered values for the form.
   */
  private function validateGoogleChartsTab(
    array &$form,
    FormStateInterface $formState) {
    // Nothing to do.
  }

  /**
   * Stores the submitted form values for the Google Charts tab.
   *
   * @param array $form
   *   The form configuration.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The entered values for the form.
   */
  private function submitGoogleChartsTab(
    array &$form,
    FormStateInterface $formState) {
    // Nothing to do.
  }

}
