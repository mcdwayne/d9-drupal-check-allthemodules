<?php

namespace Drupal\rst\Plugin\Filter;

use Drupal;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Url;
use Drupal\rst\RSTErrorManager;
use Exception;
use Gregwar\RST\Parser;

/**
 * Provides a filter for ReStructuredText.
 *
 * @Filter(
 *   id = "rst",
 *   module = "rst",
 *   title = @Translation("ReStructuredText"),
 *   description = @Translation("Allows content to be submitted using ReStructuredText, a plain-text syntax that is filtered into valid HTML."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class ReStructuredText extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $parsing_class_not_found = FALSE;
    if (!class_exists('Gregwar\RST\Parser')) {
      $parsing_class_not_found = TRUE;
    }

    if ($parsing_class_not_found) {
      $form['class_not_found_info'] = [
        '#type' => 'item',
        '#title' => t('Class not found!'),
        '#markup' => t('The required ReStructuredText parser class "Gregwar\RST\Parser" could not be found! Be sure the required library "gregwar/rst" has been properly installed with composer.'),
      ];
    }
    else {
      $form['item_error_handling'] = [
        '#type' => 'item',
        '#title' => $this->t('Error handling'),
      ];

      $form['raise_exception'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Raise an exception if parsing fails <strong>(not recommended)</strong>'),
        '#default_value' => $this->settings['raise_exception'],
        '#description' => $this->t('Abruptly stops the parsing process by raising an exception if anything goes wrong. <strong>Use with caution or for debugging purposes as it may break your site</strong>.'),
      ];
      $form['raise_warnings'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Raise warnings when errors occur during parsing'),
        '#default_value' => $this->settings['raise_warnings'],
        '#description' => $this->t('Raises warning messages on screen indicating what went wrong while parsing the input text.'),
        '#states' => [
          'visible' => [':input[name="filters[rst][settings][raise_exception]"]' => ['checked' => FALSE]],
        ],
      ];
      $form['log_warnings'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Log warnings in the watchdog when errors occur during parsing'),
        '#default_value' => $this->settings['log_warnings'],
        '#description' => $this->t('Logs warning messages in the Drupal watchdog indicating what went wrong while parsing the input text.'),
      ];

      $form['item_rst_options'] = [
        '#type' => 'item',
        '#title' => $this->t('RST library options'),
      ];

      $form['auto_relative_urls'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Try converting URLs to relative URLs.'),
        '#default_value' => $this->settings['auto_relative_urls'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (!empty($text)) {
      if (class_exists('Gregwar\RST\Parser')) {
        // Load RST parser.
        $parser = new Parser();

        // Collect filter settings.
        $raise_exception = isset($this->settings['raise_exception']) ? (bool) $this->settings['raise_exception'] : FALSE;
        $raise_warnings = isset($this->settings['raise_warnings']) ? (bool) $this->settings['raise_warnings'] : FALSE;
        $log_warnings = isset($this->settings['log_warnings']) ? (bool) $this->settings['log_warnings'] : FALSE;
        $rst_auto_relative_urls = isset($this->settings['auto_relative_urls']) ? (bool) $this->settings['auto_relative_urls'] : FALSE;

        // Configure how to handle parsing errors with our custom class.
        $rstErrorManager = new RSTErrorManager();
        $rstErrorManager->logErrors($log_warnings);
        $rstErrorManager->abortOnError($raise_exception);
        $rstErrorManager->raiseWarningsOnError($raise_warnings);

        $parser->getEnvironment()->setErrorManager($rstErrorManager);

        // Configure how the RST library should parse.
        $parser_environment = $parser->getEnvironment();
        $parser_environment->setUseRelativeUrls($rst_auto_relative_urls);

        // Parse and render the input text.
        $parsed_text = $parser->parse($text);
        $text = $parsed_text->renderDocument();
      }
      else {
        Drupal::logger('rst')->error('Error while trying to apply the RST filter: Class "Gregwar\RST\Parser" was not found.');
      }
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $ref_link = Link::fromTextAndUrl(
      'docutils.sourceforge.net/docs/ref/rst/restructuredtext.html',
      Url::fromUri('http://docutils.sourceforge.net/docs/ref/rst/restructuredtext.html')
    );
    $try_link = Link::fromTextAndUrl(
      'rst.ninjs.org',
      Url::fromUri('http://rst.ninjs.org/')
    );
    return $this->t(
      'Read how to use the ReStructuredText format at @ref_link. You may also try it online at @try_link.',
      [
        '@ref_link' => $ref_link->toString(),
        '@try_link' => $try_link->toString(),
      ]
    );
  }

}
