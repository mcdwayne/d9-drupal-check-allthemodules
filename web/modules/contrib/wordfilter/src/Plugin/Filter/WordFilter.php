<?php

namespace Drupal\wordfilter\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "wordfilter",
 *   title = @Translation("Apply filtering of words"),
 *   description = @Translation("Filter out words by given Wordfilter configurations (choose below)."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class WordFilter extends FilterBase {

  /**
   * A list of all active Wordfilter configurations.
   *
   * @see ::activeWordfilterConfigs()
   *
   * @var \Drupal\wordfilter\Entity\WordfilterConfigurationInterface[]
   */
  protected $active_configs = NULL;

  /**
   * Get a list of active Wordfilter configurations for this filter.
   *
   * @return \Drupal\wordfilter\Entity\WordfilterConfigurationInterface[]
   */
  public function activeWordfilterConfigs() {
    if (!isset($this->active_configs)) {
      $settings_active = !empty($this->settings['active_wordfilter_configs']) ?
        $this->settings['active_wordfilter_configs'] : [];

      if (!empty($settings_active)) {
        $storage = \Drupal::entityTypeManager()
          ->getStorage('wordfilter_configuration');

        $this->active_configs = $storage->loadMultiple($settings_active);
      }
      else {
        $this->active_configs = [];
      }
    }

    return $this->active_configs;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $active_configs = $this->activeWordfilterConfigs();

    if (!empty($active_configs)) {
      foreach ($active_configs as $wordfilter_config) {
        $process = $wordfilter_config->getProcess();
        $text = $process->filterWords($text, $wordfilter_config, $langcode);
      }
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $storage = \Drupal::entityTypeManager()->getStorage('wordfilter_configuration');
    $wordfilter_configs = $storage->loadMultiple();

    foreach ($wordfilter_configs as $key => $config) {
      $wordfilter_configs[$key] = $config->label();
    }

    $descriptions = [
      $this->t('Choose the available Wordfilter configurations as active filters.'),
    ];
    if (\Drupal::currentUser()->hasPermission('access wordfilter configurations page')) {
      $descriptions[] = $this->t('You can create and manage Wordfilter configurations at the <a target="_blank" href=":url">Wordfilter configuration page</a>.', [':url' => '/admin/config/wordfilter_configuration']);
    }
    $form['active_wordfilter_configs'] = array(
      '#type' => 'select',
      '#title' => $this->t('Active Wordfilter configurations'),
      '#options' => $wordfilter_configs,
      '#default_value' => !empty($this->settings['active_wordfilter_configs']) ?
        $this->settings['active_wordfilter_configs'] : [],
      '#description' =>  \Drupal::theme()->render('item_list', ['items' => $descriptions]),
      '#multiple' => TRUE,
    );

    return $form;
  }
}
