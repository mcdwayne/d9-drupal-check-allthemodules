Introduction
------------
Twitter Entity features:

- Twitter Entity module allows to pull Tweets from user timeline 
and store them as entities.
- Tweets can be pulled from multiple users.
- Provides basic views block with list of five latest Tweets. 
This block can be easily cloned and adjusted to user needs.

Requirements
------------
Module uses https://github.com/abraham/twitteroauth library to interact with Twitter API.
If you will use composer to install this module it will be automatically pulled.

### Twitter Application
In order to interact with Twitter API you need to create Twitter Application.

- Login to your Twitter account and go here https://apps.twitter.com/ 
- Click "Create new app" and provide Application Details (name, description and website)
- After application is created click on "Keys and Access Tokens" tab then click on 
"Create my access token" button
- If you went trough above steps you should have all necessary Keys and Access Tokens 
that needs to be filled on module administration page /admin/content/tweet/settings
- I also recommend changing Application permissions to "Read Only"

Installation
------------

First add additional repository to composer.json

```json
 "repositories": [
      {
          "type": "git",
          "url": "https://git.drupal.org/sandbox/Entaro/2849209.git"
      }
  ],
```
Then inside your project root run:
```bash
composer require drupal/twitter_entity --prefer-source
```

Configuration
------------
Twitter api keys, and twitter account can be set it here 
/admin/content/tweet/settings

### Pulling tweets
After you finished module setup and you pull Tweets for first time just run CRON. 
By default it pulls five latest Tweets so if you want to pull more 
for the first time adjust this number adjust the number on settings page

You can see a list of pulled Tweets on this page /admin/content/tweet

### Theming
Individual Tweets can be themed by overriding twitter_entity.html.twig

MAINTAINERS
-----------

Current maintainers:
 * Andrzej K. - https://www.drupal.org/u/entaro
