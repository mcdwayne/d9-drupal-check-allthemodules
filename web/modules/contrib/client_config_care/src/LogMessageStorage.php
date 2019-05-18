<?php

namespace Drupal\client_config_care;


class LogMessageStorage {

  private static $messages;

  public static function addMessage(array $message): void {
    self::$messages = $message;
  }

  public static function getMessages(): ?array {
    return self::$messages;
  }

  public static function removeMessage(string $key): void {
    unset(self::$messages[$key]);
  }

  public static function hasMessage(): bool {
    if (\count(self::$messages) > 0) {
      return TRUE;
    }

    return FALSE;
  }

}
