<?php
/**
 * @file
 * Contains Drupal\writeup\Plugin\Filter\FilterWriteup
 */

namespace Drupal\writeup\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filter to process text using the Writeup markup language
 * See example in /core/modules/filter/src/Plugin/Filter/FilterHtml.php
 *
 * @Filter(
 *   id = "filter_writeup",
 *   title = @Translation("Writeup Filter"),
 *   description = @Translation("Process text using the Writeup markup language."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "writeup_format" = "",
 *     "writeup_incname" = "writeup_inc.txt",
 *     "writeup_settings" = "",
 *     "writeup_sanitize" = TRUE,
 *     "writeup_help" = ""
 *   },
 * )
 */
class FilterWriteup extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $tmpfile = file_directory_temp() . '/writeupif.txt';
    file_unmanaged_save_data($text, $tmpfile, FILE_EXISTS_REPLACE);
    $formatname = $this->settings['writeup_format'];
    //debug code: $formatname = $this->getPluginId() . ', ' . $this->getBaseId() . ', ' . $this->getDerivativeId() . ', ' . $this->getLabel() . ', ' . $this->provider . ', ' . print_r($this->settings, True) . ', ' . print_r($this->getConfiguration(), True);
    $format_incname = $this->settings['writeup_incname'];
    $format_settings = $this->settings['writeup_settings'];
    $page = _writeup_process($tmpfile, $formatname, $format_incname, $format_settings);
    if ($this->settings['writeup_sanitize']) $page = writeup_filter_xss_admin($page);
    return new FilterProcessResult($page);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['writeup_format'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name of text format'),
      '#description' => $this->t('Name of text format that this filter is part of (Drupal 8 can\'t do this automatically.). Eg. enhanced'),
      '#default_value' => $this->settings['writeup_format'],
      '#size' => 40,
    );
    $form['writeup_incname'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name of default definitions include file (or empty if none)'),
      '#description' => $this->t('The name of the file that is included in writeup processing for this format, e.g. writeup_inc.txt.'),
      '#default_value' => $this->settings['writeup_incname'],
      '#after_build' => array('\Drupal\writeup\Plugin\Filter\FilterWriteup::check_file_or_empty'),
      '#size' => 40,
    );
    $form['writeup_settings'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Variable settings passed on to Writeup'),
      '#description' => $this->t('Settings in the form: <code>-s var1=value -s var2="another value"</code> for this format.'),
      '#default_value' => escapeshellcmd($this->settings['writeup_settings']),
      '#size' => 100,
    );
    $form['writeup_sanitize'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Sanitize page'),
      '#description' => $this->t('Run page through HTML filter, allowing all HTML 5 tags except &lt;script&gt; and those belonging to headers or forms.'),
      '#default_value' => $this->settings['writeup_sanitize'],
    );
    $form['writeup_help'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Special help message for this format'),
      '#description' => $this->t('Limited HTML may be used. Leave empty to display default.'),
      '#default_value' => $this->settings['writeup_help'], /* default to "" */
      '#cols' => 60,
      '#rows' => 5,
    );
    return $form;
  }

  /**
  * If a file is specified in $form_element, check it exists.
  * If validation fails, the form element is flagged.
  *
  * @param $form_element
  *   The form element containing the name of the file to check.
  * @param \Drupal\Core\Form\FormStateInterface $form_state
  *   The current state of the form.
  */
  function check_file_or_empty($form_element, FormStateInterface $form_state) {
    if ($form_element['#value'] == '') return $form_element;
    $directory = rtrim(\Drupal::config('writeup.settings')->get('writeup_incdir'), '/\\');
    if (!is_dir($directory)) {   // Check if directory exists.
      $form_state->setErrorByName($form_element['#parents'][0], t('The Writeup directory %directory set on the administration page does not exist.', array('%directory' => $directory)));
    }
    else {
      $path = $directory . '/' . $form_element['#value'];
      if (!is_file($path)) {   // Check if file exists.
        $form_state->setErrorByName($form_element['#parents'][0], t('The file %path does not exist.', array('%path' => $path)));
      }
    }
    return $form_element;
  }

  /**
   * {@inheritdoc}
   */
  function tips($long = FALSE) {
    if ($this->settings['writeup_help'] != '') {
      return \Drupal\Component\Utility\Xss::filter($this->settings['writeup_help']);
    }
    else {
      if ($long) {
        return $this->t('Quick Tips:<ul>
          <li>_underscores_ => <em>Emphasis</em></li>
          <li>*asterisks* => <strong>Strong</strong></li>
          <li><strong>-</strong> at start of line => unordered lists</li>
          <li><strong>1.</strong> at start of line => ordered lists (or <strong>A. a. I.</strong> etc.)</li>
          <li><strong>--</strong> => &mdash; (em dash)</li>
          <li><strong>[[imageref.jpg</strong> <em>optional alt words</em><strong>]]</strong> => &lt;img src="<strong>imageref.jpg</strong>" alt="<em>optional alt words</em>"&gt;</li>
          <li><strong>[[http://gw.ca</strong> <em>optional linking text</em><strong>]]</strong> => &lt;a href="<strong>http://gw.ca</strong>"&gt;<em>optional linking text</em>&lt;/a&gt;</li>
          <li><strong>..</strong>Heading level 2</li>
          <li><strong>....</strong>Heading level 4 etc.</li>
          </ul>For complete details on the Writeup syntax, see the <a href="http://writeup.org/quickref">Writeup documentation</a>');
      }
      else {
        return $this->t('You can use <a href="@filter_tips">Writeup syntax</a> to format and style the text.<br />
                  e.g. *bold*, _italics_, --emdash, "-" at start of line for lists, .heading1 ..heading2 etc.',
                  array('@filter_tips' => \Drupal\Core\Url::fromRoute('filter.tips_all')));
      }
    }
  }
}
