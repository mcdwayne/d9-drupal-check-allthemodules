<?php

namespace Drupal\gutenberg_cloud\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BlockManagerForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return [
      'gutenberg_cloud.blocks',
    ];
  }

  protected $messengerInterface;

  protected $httpClient;

  protected $blocksConfig;

  public function __construct(EntityManagerInterface $entity_manager, MessengerInterface $messenger) {
    $this->messenger = $messenger;
    $this->httpClient = new \GuzzleHttp\Client();
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'), $container->get('messenger'));
  }

  public function getFormId() {
    return 'gutenberg_cloud_block_manager_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      $blocks_json = $this->httpClient->get('https://api.gutenbergcloud.org/blocks');
    } catch (\Exception $e) {
      $blocks = NULL;
    }

    if ($blocks_json) {
      $blocks = json_decode($blocks_json->getBody())->rows;

      $header = [
        'name' => $this
          ->t('Block name'),
        'version' => $this
          ->t('Version'),
        'description' => $this
          ->t('Description'),
      ];

      $blocks_array = [];
      $blocks_defaults = [];

      $config = $this->config('gutenberg_cloud.blocks');
      $blocks_enabled = array_keys($config->get());

      foreach ($blocks as $block) {
        $blocks_array[$block->name] = [
          'name' => $block->name,
          'description' => $block->package->description,
          'version' => $block->version,
          'js' => $block->config->js,
          'edit_css' => (isset($block->config->editor)) ? $block->config->editor : NULL,
          'view_css' => (isset($block->config->css)) ? $block->config->css : NULL,
        ];

        $blocks_defaults[$block->name] = (in_array($block->name, $blocks_enabled)) ? ['name' => $block->name] : NULL;
      }

      $form['cloud_blocks'] = [
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $blocks_array,
        '#default_value' => $blocks_defaults,
      ];

      $form['actions'] = [
        '#type' => 'actions',
      ];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Apply configuration'),
        '#button_type' => 'primary',
      ];

      return $form;
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $http_client = new \GuzzleHttp\Client();
    try {
      $blocks_json = $http_client->get('https://api.gutenbergcloud.org/blocks');
    } catch (\Exception $e) {
      $blocks = NULL;
    }

    $blocks = json_decode($blocks_json->getBody())->rows;

    $blocks_array = [];

    foreach ($blocks as $block) {
      $blocks_array[$block->name] = [
        'name' => $block->name,
        'description' => $block->package->description,
        'version' => $block->version,
        'js' => $block->config->js,
        'edit_css' => (isset($block->config->editor)) ? $block->config->editor : NULL,
        'view_css' => (isset($block->config->css)) ? $block->config->css : NULL,
      ];
    }

    $this->messenger->addStatus($this->t('Gutenberg Cloud configuration has been updated'));
    $blocks_enabled = $form_state->getValue('cloud_blocks');
    $block_config = [];

    foreach ($blocks_enabled as $block) {
      if ($block) {
        unset($blocks_array[$block]['description']);
        $block_config[$block] = $blocks_array[$block];
      }
    }

    $this->config('gutenberg_cloud.blocks')
      ->setData($block_config)
      ->save();

    drupal_flush_all_caches();
  }
}
