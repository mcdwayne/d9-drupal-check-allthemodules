<?php

namespace Drupal\drd\Plugin\views\field;

use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\drd\Entity\DomainInterface;
use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\ResultRow;

/**
 * A handler to display the domain name.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("drd_domain_name")
 */
class DomainName extends Standard {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->realField = 'name';
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $domain = $values->_entity;
    $extra = '';
    if ($domain instanceof DomainInterface) {
      $name = $domain->getName(FALSE);
      if (empty($name)) {
        $redirect = $domain->getRemoteSetupRedirect(TRUE);
        $token = $domain->getRemoteSetupToken($redirect->toString());
        $extra = ' allow-wrap';
        $output = $this->t('Make sure you are %login at %domain. Copy the @widget to the clipboard, then %enable and paste the token to authorize DRD access.', [
          '%login' => $domain->getRemoteLoginLink($this->t('logged in')),
          '%domain' => $domain->getDomainName(),
          '%enable' => $domain->getRemoteSetupLink($this->t('click here'), TRUE),
          '@widget' => Markup::create('<div class="token-widget" token="' . $token . '">' . $this->t('token') . '<span class="message">' . t('Click to copy the token to the clipboard.') . '</span></div>'),
        ]);
      }
      else {
        $name = \Drupal::linkGenerator()
          ->generate($name, new Url('entity.drd_domain.canonical', ['drd_domain' => $domain->id()]));
        $link = \Drupal::linkGenerator()
          ->generate($this->t('Link'), $domain->buildUrl());
        $session = \Drupal::linkGenerator()
          ->generate($this->t('Session'), new Url('entity.drd_domain.session', ['domain' => $domain->id()]));
        $output =
          '<div class="drd-icon link">' . $link .
          '</div><div class="drd-icon session">' . $session .
          '</div><div class="name">' . $name .
          '</div><div class="domain">' . $domain->getDomainName() .
          '</div>' .
          $domain->renderPingStatus();
      }
    }
    else {
      $output = $this->getValue($values);
    }
    return Markup::create('<div class="drd-domain-name' . $extra . '">' . $output . '</div>');
  }

}
