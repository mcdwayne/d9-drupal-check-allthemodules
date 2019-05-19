/**
 * @file
 * This file include reciving data from drupal and call twitterFetcher function.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.simple_tweets = {
    attach: function (context, settings) {

      function customCallbackFunction(tweets) {
        var len = tweets.length;
        var html = '<ul>';
        for (var i = 0; i < len; i++) {
          html += '<li>' + tweets[i] + '</li>';
        }
        html += '</ul>';
        if (len === 0) {
          html += '<p class="error">' + Drupal.t('Failed to get tweets. Check widget ID') + '</p>';
        }
        document.getElementById('block-simpletweets').innerHTML = html;
      }

      var config = {
        id: settings.simple_tweets.id,
        domId: 'block-simpletweets',
        maxTweets: settings.simple_tweets.maximum,
        enableLinks: settings.simple_tweets.hyperlink,
        showUser: settings.simple_tweets.user,
        showTime: settings.simple_tweets.post_time,
        showRetweet: settings.simple_tweets.retweet,
        showInteraction: settings.simple_tweets.interact,
        showImages: settings.simple_tweets.img,
        lang: settings.simple_tweets.lang,
        linksInNewWindow: settings.simple_tweets.wind,
        customCallback: customCallbackFunction
      };

      twitterFetcher.fetch(config);
      document.getElementById('block-simpletweets').classList.add('processed-simple-tweets');
    }
  };
})();
