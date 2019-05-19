# Twitter API Block

This lightweight module adds a simple Block plugin to embed tweets in your Drupal website.

It is based on this API: [https://github.com/J7mbo/twitter-api-php](https://github.com/J7mbo/twitter-api-php).

It loads the necessary [Javascript script](https://platform.twitter.com/widgets.js) from Twitter for you.

## How to install

You need to install dependencies in you Composer file, as follow:

```
composer require j7mbo/twitter-api-php
```

Enable the module

```
drush en twitter_api_block -y
```

Go to **Admin > Block layout** and place the block where you want.

Enjoy!

--- 

Still have question? [Contact me](https://matthieuscarset.com/)
