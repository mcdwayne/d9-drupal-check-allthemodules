Medium Posts
=========

Introduction
----

A Drupal module used for publishing posts from Drupal to Medium.com by using
Medium API.

This module is based on[Medium's API](https://github.com/Medium/medium-api-docs)
and [Medium SDK for PHP]
(https://github.com/jonathantorres/medium-sdk-php/blob/master/tests/MediumTest.php).

Using the module
----

As a post author, go to your profile setting page `/user/{uid}/edit`, and find
the "MEDIUM PUBLISH SETTINGS" and add your "integration token". You can generate
an access token from the [Settings](https://medium.com/me/settings) page of your
Medium account.

As an Administrator, go to module settings page in path
`/admin/config/medium_posts`.

### Content Type
Select a "content type" to be used as Medium post. By default it will use the
Drupal build in `Article` content type.
But you are able to use any content type if you like. Medium publish uses
`title`, `body` and `field_tags` three fields so please make sure the content
type have those fields.

### Publish status on Medium.com
By default the "publish status" is "public", that means you post will be public
immediately after be pushed to medium.com. But you can change the
"publish status" to "draft" if you'd like to review it on medium.com.

### Push on content publish
By default, post will be created on Medium.com on the event of your node
content's publishing. But you can disable it and use 'medium_posts.manager'
service in your code to push the post on any event.

### Workbench Settings
Diable above "Push on content publish" and enable this if you are using
Workbench Moderation moudle to publish node. It will push your post when
contnent is changed to your moderation publish status.


Drupal Event
----

This module dispatch an event every time a node content is successfully pushed
to medium.com.
So you can subscribe `medium_posts.post_pushed` event in any other module to do
further stuff, e.g share the medium post url on social media.

Todo
----

- Add test.
- Put project on drupal.org.
- Use [encrypt](https://drupal.org/project/encrypt)
  module to encrypt token storage.
