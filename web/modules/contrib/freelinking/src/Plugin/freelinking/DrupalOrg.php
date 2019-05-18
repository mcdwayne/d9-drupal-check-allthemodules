<?php

namespace Drupal\freelinking\Plugin\freelinking;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use GuzzleHttp\Exception\RequestException;

/**
 * Freelinking drupal.org and drupal.org project plugin.
 *
 * Allows for a link like [[drupalorg:12345]] to be expanded to
 * https://drupal.org/node/12345.
 *
 * @Freelinking(
 *   id = "drupalorg",
 *   title = @Translation("Drupal.org External Link"),
 *   weight = 0,
 *   hidden = false,
 *   settings = {
 *      "scrape" = "1",
 *      "project" = "1",
 *      "node" = "1",
 *   }
 * )
 */
class DrupalOrg extends External {

  /**
   * {@inheritdoc}
   */
  public function getIndicator() {
    $settings = $this->getConfiguration()['settings'];

    if (!$settings['node'] && !$settings['project']) {
      return '/^NONE$/';
    }

    $pattern = '/^d(rupal)?(';

    if ($settings['node']) {
      $pattern .= 'o(rg)?';

      if ($settings['project']) {
        $pattern .= '|';
      }
    }

    if ($settings['project']) {
      $pattern .= 'p(roject)?)$/';
    }
    else {
      $pattern .= ')$/';
    }

    return $pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function getTip() {
    // Note that this is a change from Drupal 7 dev plugin behavior.
    return $this->t('Click to view on drupal.org.');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['settings']['node'] = TRUE;
    $configuration['settings']['project'] = TRUE;
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $settings = $this->getConfiguration()['settings'];

    $element['node'] = [
      '#type' => 'select',
      '#title' => $this->t('drupal.org nodes'),
      '#description' => $this->t('Should freelinking allow links to any drupal.org node?'),
      '#options' => ['0' => $this->t('No'), '1' => $this->t('Yes')],
      '#default_value' => isset($settings['node']) ? $settings['node'] : '1',
    ];
    $element['project'] = [
      '#type' => 'select',
      '#title' => $this->t('drupal.org projects'),
      '#description' => $this->t('Should freelinking allow links to drupal.org projects?'),
      '#options' => ['0' => $this->t('No'), '1' => $this->t('Yes')],
      '#default_value' => isset($settings['project']) ? $settings['project'] : '1',
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLink(array $target) {
    $scrape = $this->getConfiguration()['settings']['scrape'];
    $path = preg_match('/o(rg)?$/', $target['indicator']) ? 'node' : 'project';

    $url = 'https://drupal.org/' . $path . '/' . $target['dest'];

    $link = [
      '#type' => 'link',
      '#url' => Url::fromUri($url, ['absolute' => TRUE, 'language' => $target['language']]),
      '#attributes' => [
        'title' => $this->getTip(),
      ],
    ];

    // Get the page title from the external URL or use the target text.
    if (!$target['text'] && $scrape) {
      try {
        $page_title = $this->getPageTitle($url);
        if ($page_title) {
          $link['#title'] = $this->t('Drupal.org: “@title”', ['@title' => $page_title]);
        }
        else {
          $prefix = is_numeric($target['dest']) ? '#' : '';
          $link['#title'] = $prefix . $target['dest'];
        }
      }
      catch (RequestException $e) {
        $link = [
          '#theme' => 'freelink_error',
          '#plugin' => 'external',
        ];

        if ($e->getResponse()->getStatusCode() >= 400) {
          $link['#message'] = $this->t('External target “@url” not found', ['@url' => $url]);
        }
      }
    }
    else {
      $link['#title'] = $target['text'] ? $target['text'] : ucwords($path) . ' ' . $target['dest'];
    }

    return $link;
  }

}
