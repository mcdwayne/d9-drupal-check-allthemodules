/**
 * @file facebook_album.js
 * The plugin that retrieves the information from the facebook album module.
 *
 * Drupal Core: 8.x
 *
 */
(function ($, Drupal, drupalSettings) {

  /**
   * Attach our blocks to the plugin within context
   * @type {{attach: Drupal.behaviors.facebook_album.attach}}
   */
  Drupal.behaviors.facebook_album = {
    attach: function (context, settings) {

      $('.facebook-album-container', context).once('facebook-album-init').each(function(){
        var delta = $(this).attr('id');
        $(this).facebookAlbum(settings.facebook_album[delta]);
      });

    }
  };

  // Enable Strict Mode
  'use strict';

  /**
   * Unchanging vars
   */
  var path_prefix = drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix + 'facebook-album/';
  var isWebkit = false;
  if(navigator.userAgent.indexOf('AppleWebKit') != -1) {
    isWebkit = true;
  }

  $.fn.facebookAlbum = function(options) {
    var $this = $(this);

    var cbOptions = {};
    var paging = {};
    var firstBatch = true;
    var albumInitialized = false;
    var loading = false;
    var cbProps = [];
    var blockID = '';

    /**
     * Elements
     */
    var $blockContainer = $this.closest('.block.block-facebook-album');
    var $albumContainer = $('.facebook-album-images-container', $this);
    var $albumHeader = $('.fb-album-header', $this);
    var $albumLoader = $('.fb-loading-icon', $this);

    // Get the block id
    // @TODO: Clean this up
    blockID = $blockContainer.attr('id').replace('block-', '').replace(/-/g, '_');

    /*
     * Parse the color box settings into an array
     */
    if(options.colorbox != "") {
      cbProps = ("rel:'fbGal', " + options.colorbox).split(',');
    }
    else {
      cbProps = ("rel:'fbGal', maxWidth:'95%', maxHeight:'95%'").split(',');
    }
    for(var i = 0; i < cbProps.length; i++){
      var prop = cbProps[i].split(':');
      cbOptions[prop[0].replace(/ /g,'')] = prop[1].replace(/\"|\'/g, "");
    }

    /*=========================================
     =            HELPER FUNCTIONS            =
     =========================================*/

    // Scroll to top of an album
    var scrollToAlbum = function () {
      $(isWebkit ? 'body' : 'html').animate({
        scrollTop: $this.offset().top - 50
      }, 1000);
    };

    // Display/Hide throbber
    var loadingIcon = function (show) {
      if (show) {
        var icon = '<div class="spinner">';
        icon += '<div class="rect1"></div>';
        icon += '<div class="rect2"></div>';
        icon += '<div class="rect3"></div>';
        icon += '<div class="rect4"></div>';
        icon += '<div class="rect5"></div>';
        icon += '</div>';

        $albumLoader.html(icon);
      }
      else {
        $albumLoader.html('');
      }
    };

    // Fade out the albums
    function fadeContainerOut() {
      $albumContainer.fadeOut(500);
    }

    // Set variables for paging
    var handlePaging = function (response) {
      if (response.data.after != null ) {
        paging['after'] = response.data.after;
      }
      else {
        paging['after'] = null;
        firstBatch = true;
      }
    };

    // Apply an image to a tile
    var setImage = function (response, image) {
      if (response.data != null && response.data.url != null) {
        image.css('background-image', 'url(' + response.data.url + ')');
        image.removeClass('unloaded');
        image.parent().parent('a').attr('href', response.data.url);
      }
    };

    // Apply a title to a tile
    var setName = function (response, image) {
      if (response.data != null && response.data.name != null) {
        image.attr('title', response.data.name);
        image.parent().parent('a').attr('title', response.data.name);
      }
    };


    /*=========================================
    =            PRIVATE FUNCTIONS            =
    =========================================*/

    /**
     * Load albums and set up tiles
     * @param after
     */
    var loadAlbums = function(after) {
      var suffix = '';

      if (after != null) {
        suffix = '/next/' + after;
      }
      $.ajax({
        url: path_prefix + blockID + '/albums' + suffix,
        dataType: 'json',
        success: function(response) {

          if (response.data != null && response.data.content != null) {

            if (firstBatch && albumInitialized) {
              scrollToAlbum();
              albumInitialized = true;
            }

            firstBatch = false;

            if ($albumContainer.hasClass('album-covers')) {
              $albumContainer.append(response.data.content);
              $albumContainer.fadeIn(500);
            }

            handlePaging(response);
            loading = false;
            loadingIcon(false);
          }
        }
      });
    };

    /**
     * Load a photo from id and set it to a tile
     * @param image
     */
    var loadPhotoUrl = function (image) {
      var pid = image.attr('data-photo-id');

      $.ajax({
        url: path_prefix + 'photo/' + pid,
        dataType: 'json',
        success: function(response) {
          setImage(response, image);
          setName(response, image);
        }
      });
    };

    /**
     * Recursively load all photos within the album
     * @param albumLink
     * @param after
     */
    var loadAlbumPhotos = function(albumLink, after) {

      var url = blockID + '/';
      url += after != null ? albumLink + '/next/' + after : albumLink;

      $.ajax({
        url: path_prefix + url,
        dataType: 'json',
        success: function(response) {

          if (response.data != null && response.data.content != null) {

            if ($albumContainer.hasClass('album-photos')) {
              $albumContainer.append(response.data.content);
              $albumContainer.fadeIn(500);

              $('.photo-thumb-wrapper i').each(function(i){
                loadPhotoUrl($(this));
              });
            }

            if (firstBatch) {
              scrollToAlbum();
              firstBatch = false;
            }

            if ($.fn.colorbox) {
              $('a.photo-thumb').colorbox( cbOptions );
            }

            handlePaging(response);
            loadingIcon(false);
            loading = false;
          }
        }
      });
    };

    /**
     * Initialize the albums
     */
    var initAlbum = function() {
      loading = false;
      firstBatch = true;

      $albumContainer.html('');
      $albumContainer.removeClass('album-photos');
      $albumContainer.addClass('album-covers');
      $albumHeader.html('');

      loadingIcon(true);
      loadAlbums(null);
    };


    /*=========================================
    =              EVENT HANDLERS            =
    =========================================*/

    /*
     * Handle click events on a given album cover photo.
     * Reset container content and loadPhotos for the respective album
     */
    $this.on('click', '.album-wrapper', function (e) {
      e.preventDefault();
      fadeContainerOut();

      var albumLink = $(this).children('.fb-link').data('album-link');
      var albumDescription = $(this).data('description');
      var albumName = $(this).data('name');
      var albumLocation = $(this).data('location');
      paging['link'] = albumLink;

      var header = '<span class="fb-back-link fb-link" href="#">&laquo ' + Drupal.t('Back to Albums') + '</span> - <span>';
      header += '<span>' + albumName + '</span>';

      if (albumLocation.length) {
        header += '<p>' + Drupal.t('Taken in:') + albumLocation + '</p>';
      }

      header += '<p>' + albumDescription + '</p>';

      $albumHeader.append(header);
      $albumContainer.html('');
      $albumContainer.removeClass('album-covers');
      $albumContainer.addClass('album-photos');

      loadingIcon(true);
      loadAlbumPhotos(albumLink, null);
    });

    /*
     * Handle the back button click action
     */
    $this.on('click', '.fb-back-link', function(e) {
      e.preventDefault();
      initAlbum();
    });

    /*
     * Handle the infinity scrolling functionality on the photos
     * When a scroll down past the currently loaded images
     * is detected, load the next set of photos if there are any
     */
    $(window).scroll(function(e){

      var scroll_y_pos = window.pageYOffset + window.innerHeight;
      var containerPosition = $albumContainer.offset().top + $albumContainer.outerHeight(true);

      if ((scroll_y_pos > containerPosition || scroll_y_pos == containerPosition)) {

        if (paging['after'] != null && !loading && !firstBatch) {

          loadingIcon(true);
          loading = true;

          if ($albumContainer.hasClass('album-photos')) {
            loadAlbumPhotos(paging['link'], paging['after']);
          }
          else if ($albumContainer.hasClass('album-covers')) {
            loadAlbums(paging['after']);
          }
        }
      }
    });

    /**
     * Start it up!
     */
    initAlbum();

  };

})(jQuery, Drupal, drupalSettings);
