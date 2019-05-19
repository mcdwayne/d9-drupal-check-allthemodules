<?php

/**
 * @file
 * Contains \Drupal\smart_glossary\Controller\SmartGlossaryController class.
 */

namespace Drupal\smart_glossary\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\smart_glossary\Entity\SmartGlossaryConfig;
use Drupal\smart_glossary\SmartGlossary;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for the Smart Glossary module.
 */
class SmartGlossaryController extends ControllerBase {
  /**
   * Returns markup for the display of the Smart Glossary.
   *
   * @param SmartGlossaryConfig $smart_glossary_config
   *   The Smart Glossary to display.
   * @param string $glossary_language
   *   The iso-code of the current language
   *
   * @return array
   *   Renderable array of the Smart Glossary output.
   */
  public function showGlossary($smart_glossary_config, $glossary_language = NULL) {
    $output = array();

    $smart_glossary = SmartGlossary::getInstance($smart_glossary_config);
    $smart_glossary->setLanguage($glossary_language);

    $output['header'] = $smart_glossary->themeGlossaryArea('header');
    $output['start'] = $smart_glossary->themeGlossaryArea('start');

    return $output;
  }

  /**
   * Returns markup for the display of the Smart Glossary.
   *
   * @param SmartGlossaryConfig $smart_glossary_config
   *   The Smart Glossary to display.
   * @param string $glossary_language
   *   The iso-code of the current language
   * @param string $character
   *   The character to get concepts starting with (a-z)
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Renderable array of the Smart Glossary output.
   */
  public function showConceptList($smart_glossary_config, $glossary_language, $character) {
    $output = array();

    $smart_glossary = SmartGlossary::getInstance($smart_glossary_config);
    $smart_glossary->setLanguage($glossary_language);
    $glossary_language = $smart_glossary->getCurrentLanguage();

    if (in_array($character, range('a', 'z')) || $character == 'all') {
      $output['header'] = $smart_glossary->themeGlossaryArea('header');

      // Get all concepts.
      if ($character == 'all') {
        $list = $smart_glossary->getList('', $glossary_language);
        $output['#title'] = t('All Terms');
      }
      // Get concepts of a single character.
      else {
        $list = $smart_glossary->getList($character, $glossary_language);
        $output['#title'] = t('Terms with %character', array('%character' => strtoupper($character)));
      }

      $output['list'] = $smart_glossary->themeGlossaryArea('list', array('list' => $list));
    }
    else {
      return new RedirectResponse(Url::fromRoute('smart_glossary.display.' . $smart_glossary_config->id())->toString());
    }

    return $output;
  }

  /**
   * Returns markup for the display of the Smart Glossary.
   *
   * @param SmartGlossaryConfig $smart_glossary_config
   *   The Smart Glossary to display.
   * @param string $glossary_language
   *   The iso-code of the current language
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Renderable array of the Smart Glossary output.
   */
  public function showGlossaryDetails($smart_glossary_config, $glossary_language) {
    $output = array();

    $smart_glossary = SmartGlossary::getInstance($smart_glossary_config);
    $smart_glossary->setLanguage($glossary_language);
    $glossary_language = $smart_glossary->getCurrentLanguage();

    if (isset($_GET['uri'])) {
      $concept_uri = $_GET['uri'];
      $output['header'] = $smart_glossary->themeGlossaryArea('header');

      $concept = $smart_glossary->getConcept($concept_uri, $glossary_language);
      if (is_null($concept)) {
        drupal_set_message(t('Term with URI "@uri" not found.', array('@uri' => $concept_uri)), 'error');
        return new RedirectResponse(Url::fromRoute('smart_glossary.display.' . $smart_glossary_config->id())->toString());
      }

      $label = isset($concept->prefLabel) ? $concept->prefLabel : $concept->prefLabelDefault;
      $output['#title'] = $label;

      $output['details'] = $smart_glossary->themeGlossaryArea('details', array('term' => $concept));
    }
    else {
      return new RedirectResponse(Url::fromRoute('smart_glossary.display.' . $smart_glossary_config->id())->toString());
    }

    return $output;
  }

  /**
   * Get the data for the Visual Mapper inside a Smart Glossary.
   *
   * @param SmartGlossaryConfig $smart_glossary_config
   *   The Smart Glossary to display.
   * @param boolean $fetch_relations
   *   TRUE if relations (broader, narrower, related) shell be fetched for the
   *   concept, FALSE if not.
   */
  public function getVisualMapperDataAjax($smart_glossary_config, $fetch_relations = TRUE) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    if (isset($_GET['lang']) && !empty($_GET['lang'])) {
      $glossary_language = $_GET['lang'];
    }
    else {
      $language_mapping = $smart_glossary_config->getLanguageMapping();
      if (isset($language_mapping[$language]) && !empty($language_mapping[$language]['glossary_languages'])) {
        $glossary_languages = $language_mapping[$language]['glossary_languages'];
        $glossary_language = $glossary_languages[0];
      }
      else {
        $glossary_language = \Drupal::languageManager()->getDefaultLanguage()->getId();
      }
    }

    $smart_glossary = SmartGlossary::getInstance($smart_glossary_config);
    $data = $smart_glossary->getVisualMapperData(
      isset($_GET['uri']) && !empty($_GET['uri']) ? $_GET['uri'] : NULL,
      $glossary_language,
      $fetch_relations,
      (isset($_GET['parent_info']) && $_GET['parent_info'])
    );

    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
  }

  /**
   * Get the data for the Visual Mapper inside a Smart Glossary.
   *
   * @param SmartGlossaryConfig $smart_glossary_config
   *   The Smart Glossary to display.
   */
  public function autocompleteConcepts($smart_glossary_config) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    if (isset($_GET['term'])) {
      $smart_glossary = SmartGlossary::getInstance($smart_glossary_config);

      if (isset($_GET['lang']) && !empty($_GET['lang'])) {
        $glossary_language = $_GET['lang'];
      }
      else {
        $language_mapping = $smart_glossary_config->getLanguageMapping();
        if (isset($language_mapping[$language]) && !empty($language_mapping[$language]['glossary_languages'])) {
          $glossary_languages = $language_mapping[$language]['glossary_languages'];
          $glossary_language = $glossary_languages[0];
        }
        else {
          $glossary_language = \Drupal::languageManager()->getDefaultLanguage()->getId();
        }
      }

      $list = $smart_glossary->autocomplete($_GET['term'], 15, $glossary_language);
    }
    else {
      $list = array();
    }

    header("Content-Type: application/json");
    echo json_encode($list);
    exit;
  }
}
