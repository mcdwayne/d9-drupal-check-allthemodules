/**
 * @file
 * A JavaScript file for the theme.
 */

(function ($, Drupal, window, document, undefined) {


// To understand behaviors, see https://drupal.org/node/756722#behaviors
Drupal.behaviors.facebook_feed = {
  attach: function(context, settings) {
    // console.log('settings', settings);

    var posts = getPosts(context, settings);
    console.log('posts: ', posts);
  }
};



function getPosts(context, settings) {
  var settingsJSON = $('.facebook_feed pre', context).html();
  console.log('settingsJSON: ', settingsJSON);
  var settings = JSON.parse(settingsJSON);
  console.log('settings: ', settings);

  var url = "https://graph.facebook.com/"
    + settings.page_id + "/"
    + settings.feedType
    + "?summary=true"
    + "&limit=" + settings.limit
    + "&access_token=" + settings.access_token
    + '&fields=' + settings.fields
  ;
  console.log('url: ', url);

  var jqxhr = $.get(url)

  jqxhr.done(function(data) {
    console.log('data: ', data);
    $(document).trigger('posts loaded');
  })

  jqxhr.fail(function(data) {
    console.log('data: ', data);
    alter('Posts fetch failure: See log');fik
  })

  var posts = [];

  return posts;
}


})(jQuery, Drupal, this, this.document);
