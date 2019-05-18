# MIME Info

Adds Symfony MIME type guessers to container and allow to have `isSupported()` method for every guesser.

## Usage

```php
  \Drupal::service('file.mime_type.guesser')->guess($uri);
```
