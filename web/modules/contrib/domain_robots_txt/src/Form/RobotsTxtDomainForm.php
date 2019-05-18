<?php

namespace Drupal\domain_robots_txt\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Robots.txt form for domains.
 */
class RobotsTxtDomainForm extends ConfigFormBase {

  /**
   * Domain ID of config.
   *
   * @var string
   */
  protected $domainId;

  /**
   * Constructs Drupal\domain_robots_txt\Form\RobotsTxtDomainForm
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Route match.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteMatchInterface $routeMatch, MessengerInterface $messenger) {
    $this->setConfigFactory($config_factory);
    $domain = $routeMatch->getParameter('domain');
    $this->domainId = $domain->id();
    $this->setMessenger($messenger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('current_route_match'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_robots_txt_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::getConfigNameByDomainId($this->domainId)];
  }

  /**
   * Get config name by Domain ID.
   *
   * @param string $domain_id
   *   Domain ID.
   *
   * @return string
   *   Config name.
   */
  public static function getConfigNameByDomainId($domain_id) {
    return 'domain.config.' . $domain_id . '.robots_txt.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['domain_id'] = [
      '#type' => 'value',
      '#value' => $this->domainId,
    ];
    $robots_txt = $this->config(self::getConfigNameByDomainId($this->domainId))
      ->get('robots_txt');
    $form['robots_txt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Robots.txt file for "@type" domain', ['@type' => $this->domainId]),
      '#default_value' => isset($robots_txt) ? $robots_txt : '',
      '#cols' => 60,
      '#rows' => 20,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $domain_id = $form_state->getValue('domain_id');
    $config = $this->config(self::getConfigNameByDomainId($domain_id));
    $values = $form_state->getValues();
    $config->set('robots_txt', $values['robots_txt']);
    $config->save();
    $this->messenger->addMessage($this->t('"Robots.txt file for "@type" domain was updated.', ['@type' => $domain_id]));
  }

}
