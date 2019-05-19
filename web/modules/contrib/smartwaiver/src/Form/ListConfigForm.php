<?php

namespace Drupal\smartwaiver\Form;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\smartwaiver\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ListConfigForm extends ConfigFormBase {

  /**
   * The smartwaiver client.
   *
   * @var \Drupal\smartwaiver\ClientInterface
   */
  protected $client;

  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $smartwaiver_client) {
    $this->setConfigFactory($config_factory);
    $this->client = $smartwaiver_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('smartwaiver.client')
    );
  }

  public function getFormId() {
    return 'smartwaiver_waiver_list_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfig();

    $form['#cache'] = [
      'max-age' => 0,
    ];

    $api_key = $config->get('webhook_key');
    if (!isset($api_key) || empty($api_key)) {
      // Configuration has not been completed, display message.
      $form['no_config'] = array(
        '#markup' => '<p>' . $this->t('Your API Key is empty or has not been set. Please visit the <a href=":settings_page">settings page</a> to complete configuration.', [
          ':settings_page' => Url::fromRoute('smartwaiver.settings')->toString(),
        ]),
      );
      return parent::buildForm($form, $form_state);
    }

    $form['enabled_waivers'] = $this->getWaiverTable($this->getWaivers());

    return parent::buildForm($form, $form_state);
  }

  protected function getWaiverTable($waivers) {
    return array_reduce($waivers, function ($table, $waiver) {
      $table['#options'][$waiver['guid']] = $waiver;
      return $table;
    }, [
      '#type' => 'tableselect',
      '#header' => [
        'title' => $this->t('Waiver Name'),
        'guid' => $this->t('GUID'),
        'web_url' => $this->t('Web URL'),
        'kiosk_url' => $this->t('Kiosk URL'),
        'published_on' => $this->t('Published On'),
      ],
      '#empty' => $this->t('No waivers found.'),
      '#default_value' => $this->getConfig()->get('enabled_waivers'),
    ]);
  }

  protected function getWaivers() {
    $items = [];
    if ($result = $this->client->templates()) {
      foreach ($result['templates'] as $template) {
        $template = (object) $template;
        $items[] = [
          'guid' => (string) $template->templateId,
          'title' => (string) $template->title,
          'web_url' => $template->webUrl,
          'web_url' => Link::fromTextAndUrl('Web link', Url::fromUri($template->webUrl)),
          'kiosk_url' => Link::fromTextAndUrl('Kiosk link', Url::fromUri($template->kioskUrl)),
          'published_on' => $template->publishedOn,
        ];
      }
    }
    return $items;
  }

}
