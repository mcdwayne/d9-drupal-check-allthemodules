<?php

namespace Drupal\qualtricsxm\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * @file
 * Contains \Drupal\qualtricsxm\Controller\DefaultController.
 */

/**
 * Default controller for the qualtricsxm module.
 */
class DefaultController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'qualtricsxm';
  }

  /**
   * Helper function to generate renderable HTML markup.
   *
   * @param string $survey_id
   *   Qualtrics survey ID.
   *
   * @return array
   *   Redenable markup.
   */
  public function qualtricsxmSurveyPage($survey_id) {
    $qualtrics = qualtricsxm_static();
    $survey_data = $qualtrics->getSurvey($survey_id);

    if (!$survey_data) {
      return $this->t('Survey is unavailable.');
    }

    $embed_url = qualtricsxm_get_base_url() . "/$survey_id";

    $qualtricsxm_embed_width = qualtricsxm_get_config_width_height()['width'];
    $qualtricsxm_embed_height = qualtricsxm_get_config_width_height()['height'];
    return [
      '#markup' => "<iframe src=\"$embed_url\" height=\"$qualtricsxm_embed_height\" width=\"$qualtricsxm_embed_width\" frameborder=\"0\" scrolling=\"no\" class=\"qualtrics_iframe\"></iframe>",
    ];
  }

  /**
   * Set page title. Comment it out if no needs for title.
   *
   * @param string $survey_id
   *   ID of the survey to be loaded.
   *
   * @return string|null
   *   Page title.
   */
  public function getTitle($survey_id) {
    $survey = qualtricsxm_get_survey($survey_id);
    $title = !empty($survey->name) ? $survey->name : NULL;
    return $title;
  }

  /**
   * Get surveys list by survey token.
   *
   * @return array|string
   *   Survey lists.
   */
  public function qualtricsxmSurveysList() {
    return qualtricsxm_get_survey_list_table();
  }

}
