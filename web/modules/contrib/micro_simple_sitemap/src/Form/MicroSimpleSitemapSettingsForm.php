<?php

namespace Drupal\micro_simple_sitemap\Form;

use Drupal\micro_site\Entity\Site;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\simple_sitemap\Form\FormHelper;
use Drupal\simple_sitemap\Form\SimplesitemapFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\Core\Path\PathValidator;

/**
 * Class MicroSimpleSitemapSettingsForm.
 *
 * @package Drupal\simple_sitemap\Form
 */
class MicroSimpleSitemapSettingsForm extends SimplesitemapFormBase {

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * MicroSimpleSitemapSettingsForm constructor.
   *
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   *   The simple sitemap generator.
   * @param \Drupal\simple_sitemap\Form\FormHelper $form_helper
   *   The form helper utility.
   * @param \Drupal\Core\Path\PathValidator $path_validator
   *   The path validator service.
   */
  public function __construct(Simplesitemap $generator, FormHelper $form_helper, PathValidator $path_validator) {
    parent::__construct($generator, $form_helper);
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.form_helper'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'micro_simple_sitemap_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SiteInterface $site = NULL) {
    if (!$site instanceof SiteInterface) {
      $form = [
        '#type' => 'markup',
        '#markup' => $this->t('XML Sitemap settings is only available in a micro site context.'),
      ];
      return $form;
    }

    $form['site_id'] = [
      '#type' => 'value',
      '#value' => $site->id(),
    ];

    $form['micro_simple_sitemap_custom'] = [
      '#title' => $this->t('Custom links'),
      '#type' => 'fieldset',
      '#markup' => '<div class="description">' . $this->t('Add custom internal drupal paths to the XML sitemap.') . '</div>',
    ];

    $custom_links = $site->getData('micro_simple_sitemap_custom_links') ?: [];
    $form['micro_simple_sitemap_custom']['custom_links'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Relative Drupal paths'),
      '#default_value' => $this->customLinksToString($custom_links),
      '#description' => $this->t("Please specify drupal internal (relative) paths, one per line. Do not forget to prepend the paths with a '/'.<br>Optionally link priority <em>(0.0 - 1.0)</em> can be added by appending it after a space.<br> Optionally link change frequency <em>(always / hourly / daily / weekly / monthly / yearly / never)</em> can be added by appending it after a space.<br/<br><strong>Examples:</strong><br><em>/ 1.0 daily</em> -> home page with the highest priority and daily change frequency<br><em>/contact</em> -> contact page with the default priority and no change frequency information"),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $site_id = $form_state->getValue('site_id');
    $site = Site::load($site_id);
    if (!$site instanceof SiteInterface) {
      $form_state->setError($form, $this->t('An error occurs. Impossible to find the site entity.'));
    }

    $custom_links = $form_state->getValue('custom_links');
    $custom_links_array = $this->stringToCustomLinks($custom_links);
    foreach ($custom_links_array as $i => $link_config) {
      $placeholders = [
        '@line' => ++$i,
        '@path' => $link_config['path'],
        '@priority' => isset($link_config['priority']) ? $link_config['priority'] : '',
        '@changefreq' => isset($link_config['changefreq']) ? $link_config['changefreq'] : '',
        '@changefreq_options' => implode(', ', FormHelper::getChangefreqOptions()),
      ];

      // Checking if internal path exists.
      if (!(bool) $this->pathValidator->getUrlIfValidWithoutAccessCheck($link_config['path'])
      // Path validator does not see a double slash as an error. Catching this
      // to prevent breaking path generation.
       || strpos($link_config['path'], '//') !== FALSE) {
        $form_state->setErrorByName('', $this->t('<strong>Line @line</strong>: The path <em>@path</em> does not exist.', $placeholders));
      }

      // Making sure the paths start with a slash.
      if ($link_config['path'][0] !== '/') {
        $form_state->setErrorByName('', $this->t("<strong>Line @line</strong>: The path <em>@path</em> needs to start with a '/'.", $placeholders));
      }

      // Making sure the priority is formatted correctly.
      if (isset($link_config['priority']) && !FormHelper::isValidPriority($link_config['priority'])) {
        $form_state->setErrorByName('', $this->t('<strong>Line @line</strong>: The priority setting <em>@priority</em> for path <em>@path</em> is incorrect. Set the priority from 0.0 to 1.0.', $placeholders));
      }

      // Making sure changefreq is formatted correctly.
      if (isset($link_config['changefreq']) && !FormHelper::isValidChangefreq($link_config['changefreq'])) {
        $form_state->setErrorByName('', $this->t('<strong>Line @line</strong>: The changefreq setting <em>@changefreq</em> for path <em>@path</em> is incorrect. The following are the correct values: <em>@changefreq_options</em>.', $placeholders));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $site_id = $form_state->getValue('site_id');
    $site = Site::load($site_id);
    if (!$site instanceof SiteInterface) {
      return;
    }

    $custom_links_string = $form_state->getValue('custom_links');
    $custom_links = $this->stringToCustomLinks($custom_links_string);
    foreach ($custom_links as $i => $link_settings) {
      $path = $link_settings['path'];
      if (!(bool) $this->pathValidator->getUrlIfValidWithoutAccessCheck($path)) {
        unset($custom_links[$i]);
      }
      if ($path[0] !== '/') {
        unset($custom_links[$i]);
      }

      Simplesitemap::supplementDefaultSettings('custom', $link_settings);
      $custom_links[$i] = $link_settings;
    }

    $site->setData('micro_simple_sitemap_custom_links', $custom_links);
    $site->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Convert links string to an array of links.
   *
   * @param string $custom_links_string
   *   The string to convert.
   *
   * @return array
   *   An array of custom links.
   */
  protected function stringToCustomLinks($custom_links_string) {

    // Unify newline characters and explode into array.
    $custom_links_string_lines = explode("\n", str_replace("\r\n", "\n", $custom_links_string));

    // Remove empty values and whitespaces from array.
    $custom_links_string_lines = array_filter(array_map('trim', $custom_links_string_lines));

    $custom_links = [];
    foreach ($custom_links_string_lines as $i => &$line) {
      $link_settings = explode(' ', $line);
      $custom_links[$i]['path'] = $link_settings[0];

      // If two arguments are provided for a link, assume the first to be
      // priority, the second to be changefreq.
      if (!empty($link_settings[1]) && !empty($link_settings[2])) {
        $custom_links[$i]['priority'] = $link_settings[1];
        $custom_links[$i]['changefreq'] = $link_settings[2];
      }
      else {
        // If one argument is provided for a link, guess if it is priority or
        // changefreq.
        if (!empty($link_settings[1])) {
          if (is_numeric($link_settings[1])) {
            $custom_links[$i]['priority'] = $link_settings[1];
          }
          else {
            $custom_links[$i]['changefreq'] = $link_settings[1];
          }
        }
      }
    }
    return $custom_links;
  }

  /**
   * Convert an array of custom links to a string.
   *
   * @param array $links
   *   An array of custom links.
   *
   * @return string
   *   A string of links.
   */
  protected function customLinksToString(array $links) {
    $setting_string = '';
    foreach ($links as $custom_link) {
      $setting_string .= $custom_link['path'];
      $setting_string .= isset($custom_link['priority'])
        ? ' ' . $this->formHelper->formatPriority($custom_link['priority'])
        : '';
      $setting_string .= isset($custom_link['changefreq'])
        ? ' ' . $custom_link['changefreq']
        : '';
      $setting_string .= "\r\n";
    }
    return $setting_string;
  }

}
