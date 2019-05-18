<?php

namespace Drupal\http_client_manager;
use GuzzleHttp\Command\Exception\CommandException;
use GuzzleHttp\Command\Guzzle\Operation;
use GuzzleHttp\Command\ResultInterface;

/**
 * Interface HttpClientInterface
 *
 * @package Drupal\http_client_manager
 */
interface HttpClientInterface {

  /**
   * Get Http Service Api data.
   *
   * @return array
   *   An array containing service api data.
   */
  public function getApi();

  /**
   * Get service api commands.
   *
   * @return mixed
   */
  public function getCommands();

  /**
   * Get single service api command by name.
   *
   * @param string $commandName
   *   The command name.
   *
   * @return Operation
   */
  public function getCommand($commandName);

  /**
   * Execute command call.
   *
   * @param string $commandName
   *  The Guzzle command name.
   * @param array $params
   *  The Guzzle command parameters array.
   *
   * @return ResultInterface
   *   The result of the executed command
   * @throws CommandException
   */
  public function call($commandName, array $params = []);

}
