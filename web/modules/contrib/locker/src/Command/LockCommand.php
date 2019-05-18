<?php

namespace Drupal\locker\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Annotations\DrupalCommand;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Class LockCommand.
 *
 * @DrupalCommand (
 *     extension="locker",
 *     extensionType="module"
 * )
 */
class LockCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
        ->setName('locker:lock')
        ->addOption(
            'username',
            null,
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.locker.lock.messages.enter_username'),
            null
        )
        ->addOption(
            'password',
            null,
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.locker.lock.messages.enter_username'),
            null
        )
        ->addOption(
            'choice',
            null,
            InputOption::VALUE_OPTIONAL,
            null,
            null
        )
        ->setDescription($this->trans('commands.locker.lock.description'));
  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
      $io = $this->getIo();
      $question = $this->trans('commands.locker.lock.messages.choose_options');
      $option = $io->askChoiceQuestion(new ChoiceQuestion($question, ['yes', 'no'], 0));
      $username = $input->getOption('username');
      $password = $input->getOption('password');
      if($option == 'yes') {
          while(!$username) {
              $username = $io->askEmpty(
                  $this->trans('commands.locker.lock.messages.enter_username'),
                  null
              );
          }
          $input->setOption('username', $username);

          while(!$password) {
              $password = $io->askEmpty(
                  $this->trans('commands.locker.lock.messages.enter_password'),
                  null
              );
          }
          $input->setOption('password', $password);
          $input->setOption('choice', 0);
      } else {
          while(!$password) {
              $password = $io->askEmpty(
                  $this->trans('commands.locker.lock.messages.enter_password'),
                  null
              );
          }
          $input->setOption('password', $password);
          $input->setOption('choice', 1);
      }
  }

    /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
      $username = $input->getOption('username');
      $password = $input->getOption('password');
      $choice = $input->getOption('choice');

      $config = \Drupal::service('config.factory')->getEditable('locker.settings');
      $passmd5 = md5($password);
      if($choice == 0) {
          $this->getIo()->info($this->trans('commands.locker.lock.messages.your_username') . $username);
          $locker_access_options = 'user_pass';
          $config->set('locker_user', $username)->set('locker_password', $passmd5);
      } else {
          $locker_access_options = 'passphrase';
          $config->set('locker_passphrase', $passmd5);
      }
      $config->set('locker_access_options', $locker_access_options);
      $config->set('locker_custom_url', 'unlock.html');
      $config->set('locker_site_locked', 'yes')->save();
      $this->getIo()->info($this->trans('commands.locker.lock.messages.your_password') . $password);
      $this->getIo()->info($this->trans('commands.locker.lock.messages.success'));

      $query = \Drupal::database()->delete('sessions');
      $query->execute();
      drupal_flush_all_caches();
  }
}
