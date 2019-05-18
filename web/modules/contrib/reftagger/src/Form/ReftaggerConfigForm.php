<?php

/**
 * @file
 * Contains Drupal\reftagger\Form\ReftaggerConfigForm.
 */

namespace Drupal\reftagger\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ReftaggerConfigForm.
 *
 * @package Drupal\reftagger\Form
 */
class ReftaggerConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'reftagger.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reftagger_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('reftagger.settings');

    $form['version'] = array(
      '#type' => 'select',
      '#title' => $this->t('Bible Version'),
      '#options' => reftagger_bible_versions(),
      '#default_value' => $config->get('version'),
      '#description' => $this->t('Select a Bible version to use. RefTagger may not support some Bible versions in the ToolTip feature.'),
    );
    $form['reader'] = array(
      '#type' => 'select',
      '#title' => $this->t('Online Bible reader'),
      '#options' => array(
        'biblia' => $this->t('Biblia'),
        'bible.faithlife' => $this->t('Faithlife Study Bible'),
      ),
      '#default_value' => $config->get('reader'),
    );
    $form['case_insensitive'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Case Insensitive?'),
      '#default_value' => $config->get('case_insensitive'),
      '#description' => $this->t('By default Reftagger only tags references using proper name capitalization (2 Corinthians 5:20). Check this to make Reftagger case insensitive (2 corinthians 5:20, 2 CORINTHIANS 5:20).'),
    );
    $form['social_sharing'] = array(
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Social sharing'),
      '#options' => _reftagger_get_social_sharing_options(),
      '#default_value' => $config->get('social_sharing'),
      '#description' => $this->t('Select which social icons to appear in tool-tip.'),
    );
    $form['chapter_tagging'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Chapter-level tagging'),
      '#description' => $this->t('Enables tagging of chapters without verses (e.g. Genesis 12).'),
      '#default_value' => $config->get('chapter_tagging'),
    );
    $form['link_target'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Links open in'),
      '#options' => array(
        $this->t('Existing window'),
        $this->t('New window'),
      ),
      '#default_value' => $config->get('link_target'),
    );
    $form['tooltip_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show ToolTips'),
      '#default_value' => $config->get('tooltip_enable'),
      '#description' => $this->t('Show a tooltip containing verse text when the mouse hovers over a reference.'),
    );

    $form['logos'] = array(
      '#type' => 'details',
      '#title' => $this->t('Logos integration'),
      '#open' => TRUE,
    );
    $form['logos']['logos_icon_link'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Add Logos buttons to tooltip'),
      '#default_value' => $config->get('logos_icon_link'),
      '#description' => $this->t('Insert an icon linking to the verse in Logos Bible Software (if available).'),
    );
    $dark_icon = '<img src="http://www.logos.com/images/Corporate/LibronixLink_dark.png" />';
    $light_icon = '<img src="http://www.logos.com/images/Corporate/LibronixLink_light.png" />';
    $form['logos']['logos_icon_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Logos link icons'),
      '#options' => array(
        'light' => $light_icon . $this->t(' Light (for sites with dark backgrounds)'),
        'dark' => $dark_icon . $this->t(' Dark (for sites with light backgrounds)'),
      ),
      '#default_value' => $config->get('logos_icon_type'),
      '#states' => array(
        'visible' => array(
          ':input[name="logos_icon_link"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['logos']['logos_icon_add'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Reftagger on existing Biblia links'),
      '#default_value' => $config->get('logos_icon_add'),
    );

    $form['exclusions'] = array(
      '#type' => 'details',
      '#title' => $this->t('Exclusions'),
    );
    $form['exclusions']['exclude_tags'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Exclude tags'),
      '#default_value' => $config->get('exclude_tags'),
      '#options' => reftagger_tags(),
      '#description' => $this->t('Do not search these HTML tags.'),
    );
    $form['exclusions']['exclude_classes'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Exclude classes'),
      '#default_value' => $config->get('exclude_classes'),
      '#description' => $this->t('A comma-separated list of HTML tag classes that should exclude RefTagger functionality'),
    );

    $form['styles_general'] = array(
      '#type' => 'details',
      '#title' => $this->t('General styling'),
      '#open' => TRUE,
    );
    $form['styles_general']['drop_shadow'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Drop shadow'),
      '#default_value' => $config->get('drop_shadow'),
    );
    $form['styles_general']['rounded_corners'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Rounded corners'),
      '#default_value' => $config->get('rounded_corners'),
    );
    $form['styles_general']['background_color'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Body background theme'),
      '#options' => array(
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ),
      '#default_value' => $config->get('background_color'),
    );

    $styles = $config->get('styles_custom');
    $form['styles_custom'] = array(
      '#type' => 'details',
      '#title' => $this->t('Custom styling'),
      '#tree' => TRUE,
    );
    $form['styles_custom']['heading'] = array(
      '#type' => 'details',
      '#title' => $this->t('Heading'),
      '#open' => TRUE,
    );
    $form['styles_custom']['heading']['color'] = array(
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => $this->t('Color'),
      '#default_value' => $styles['heading']['color'],
      '#description' => $this->t('Use CSS HEX value (e.g. #ff0000 for red)'),
      '#element_validate' => array(
        array($this, 'validateColor'),
      ),
    );
    $form['styles_custom']['heading']['fontFamily'] = array(
      '#type' => 'select',
      '#title' => $this->t('Font'),
      '#default_value' => $styles['heading']['fontFamily'],
      '#options' => array(
        "Arial, 'Helvetica Neue', Helvetica, sans-serif" => 'Arial',
        "'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace" => 'Courier New',
        "Georgia, Times, 'Times New Roman', serif" => 'Georgia',
        "Palatino, 'Palatino Linotype', 'Palatino LT STD', 'Book Antiqua', Georgia, serif" => 'Palantino',
        "Tahoma, Verdana, Segoe, sans-serif" => 'Tahoma',
        "TimesNewRoman, 'Times New Roman', Times, Baskerville, Georgia, serif" => 'Times New Roman',
        "Verdana, Geneva, sans-serif" => 'Verdana',
      ),
    );
    $font_sizes = array('12px', '14px', '16px', '18px');
    $form['styles_custom']['heading']['fontSize'] = array(
      '#type' => 'select',
      '#title' => $this->t('Font size'),
      '#default_value' => $styles['heading']['fontSize'],
      '#options' => array_combine($font_sizes, $font_sizes),
    );
    $form['styles_custom']['heading']['backgroundColor'] = array(
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => $this->t('Background color'),
      '#default_value' => $styles['heading']['backgroundColor'],
      '#description' => $this->t('Background color for heading. Use CSS HEX value (e.g. #ff0000 for red)'),
      '#element_validate' => array(
        array($this, 'validateColor'),
      ),
    );
    $form['styles_custom']['body'] = array(
      '#type' => 'details',
      '#title' => $this->t('Body'),
      '#open' => TRUE,
    );
    // The body color field re-uses everything except default value from the
    // heading color field.
    $form['styles_custom']['body']['color'] = $form['styles_custom']['heading']['color'];
    $form['styles_custom']['body']['color']['#default_value'] = $styles['body']['color'];

    // The body font family field re-uses everything except default value from the
    // heading font family field.
    $form['styles_custom']['body']['fontFamily'] = $form['styles_custom']['heading']['fontFamily'];
    $form['styles_custom']['body']['fontFamily']['#default_value'] = $styles['body']['fontFamily'];

    // The body font size field re-uses everything except default value from the
    // heading font size field.
    $form['styles_custom']['body']['fontSize'] = $form['styles_custom']['heading']['fontSize'];
    $form['styles_custom']['body']['fontSize']['#default_value'] = $styles['body']['fontSize'];

    // The link color field re-uses everything from heading color field, except
    // the default value and title.
    $form['styles_custom']['body']['moreLink']['color'] = $form['styles_custom']['heading']['color'];
    $form['styles_custom']['body']['moreLink']['color']['#title'] = $this->t('Link color');
    $form['styles_custom']['body']['moreLink']['color']['#default_value'] = $styles['body']['moreLink']['color'];

    return parent::buildForm($form, $form_state);
  }

  public function validateColor($element, FormStateInterface $form_state) {
    // The regex pattern shamelessly stolen from Drupal 7's color_valid_hexadecimal_string().
    if (!preg_match('/^#([a-f0-9]{3}){1,2}$/iD', $element['#value'])) {
      $form_state->setError($element, $this->t('This is not a valid HEX value. It must be in format <em>#ccc000</em> or <em>#ccc</em>.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('reftagger.settings')
      ->set('version', $form_state->getValue('version'))
      ->set('reader', $form_state->getValue('reader'))
      ->set('case_insensitive', $form_state->getValue('case_insensitive'))
      ->set('social_sharing', $form_state->getValue('social_sharing'))
      ->set('chapter_tagging', $form_state->getValue('chapter_tagging'))
      ->set('link_target', $form_state->getValue('link_target'))
      ->set('tooltip_enable', $form_state->getValue('tooltip_enable'))
      ->set('logos_icon_link', $form_state->getValue('logos_icon_link'))
      ->set('logos_icon_type', $form_state->getValue('logos_icon_type'))
      ->set('logos_icon_add', $form_state->getValue('logos_icon_add'))
      ->set('exclude_tags', $form_state->getValue('exclude_tags'))
      ->set('exclude_classes', $form_state->getValue('exclude_classes'))
      ->set('drop_shadow', $form_state->getValue('drop_shadow'))
      ->set('rounded_corners', $form_state->getValue('rounded_corners'))
      ->set('background_color', $form_state->getValue('background_color'))
      ->set('styles_custom', $form_state->getValue('styles_custom'))
      ->save();
  }

}
