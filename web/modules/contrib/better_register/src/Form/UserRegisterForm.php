<?php
declare(strict_types=1);

namespace Drupal\better_register\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\user\RegisterForm;

class UserRegisterForm extends RegisterForm {
  const EMAIL_CONFIRMED_ROLE = 'email_confirmed';

  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['account']['name']['#value']  = md5(date('c'));
    $form['account']['name']['#access'] = FALSE;

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state) {
    /* @var $account User */
    $account = $this->entity;
    $account->setUsername($this->entity->getEmail());
    
    parent::save($form, $form_state);
  }

}
