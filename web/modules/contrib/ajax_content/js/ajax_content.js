/**
 * JS for the handling of the ajax content.
 */

'use strict';

(function ($, Drupal, window, document, undefined) {

  var componentsLoaded = false;

  // To understand behaviors, see https://drupal.org/node/756722#behaviors
  Drupal.behaviors.ajaxContent = {
    attach: function(context, settings) { // jshint ignore:line

      if (typeof document.ajaxContentAttached === 'undefined') {

        // When page is ready create an iframe to load ajax response into
        // and then we can get what we want from it. Avoids server from
        // rendering both full markup and individual node
        $( document ).ready(function() {
          $(function(){
            $('<iframe style="display:none; position:absolute; width:100%;height:100%;" id="ajax-content-iframe"/>').appendTo('body');
          });
        });

        if (!componentsLoaded) {

          // If ajax error caused by this module, load link like a normal link.
          $(document).ajaxError(function(event, jqXHR, settings, exception) {
            if (typeof settings.url !== "undefined" && settings.url == '/ajax-content/load') {
              window.location.href = event.currentTarget.activeElement.attributes.href.value;
            }
          });

          function sendPostRequest(postData, internalLinks) {
            $('.region.region-content').css('opacity',1).animate({opacity:0}, 800, function() {
              $.post( '/ajax-content/load', postData, function(data) {

                if (postData.requestUrl.indexOf('/') !== 0) {
                  postData.requestUrl = '/' + postData.requestUrl;
                }

                history.pushState({
                  data: postData,
                },'', postData.requestUrl);
  
                $(internalLinks).each(function() {
                  $(this).unbind( "click" );
                });
  
                var ajaxContentIframe = document.getElementById('ajax-content-iframe').contentWindow.document;
                ajaxContentIframe.write(data.content);
                ajaxContentIframe.close();
  
                for (var i = 0; i < data.css.length; i++) {
  
                  // Remove old css tags that will be replaced
                  var regex = /<link rel="stylesheet".*?href="(.*?)"/gmi;
                  var cssSrc = regex.exec(data.css[i]);
                  if (cssSrc != null) {
                    $('head link').each(function() {
                       if(cssSrc[1].indexOf($(this).attr("href")) !== -1) {
                         $(this).remove();
                       }
                     }); 
                  }
                  $('head').append(data.css[i]);
                }

                // Set browser tab title
                document.title = data.title;

                $('html, body').animate({scrollTop:0}, 'slow');

                // Replace Header
                $('header#header').replaceWith($('#ajax-content-iframe').contents().find('header#header')[0].outerHTML);
                // Replace Content
                $(drupalSettings.ajax_content.ajax_content.ajax_content_load_container_selector).html($('#ajax-content-iframe').contents().find('article')[0].outerHTML);

                for (var i = 0; i < data.js.length; i++) {

                  // Remove old script tags that will be replaced
                  var regex = /<script.*?src="(.*?)"/gmi;
                  var scriptSrc = regex.exec(data.js[i]);
                  if (scriptSrc != null) {
                    $('body script').each(function() {
                       if(scriptSrc[1].indexOf($(this).attr("src")) !== -1) {
                         $(this).remove();
                       }
                     }); 
                  }

                  $('body').append(data.js[i]);
                }

                // Update body attributes
                var responseBody = $($('#ajax-content-iframe').contents().find('body')[0]);
                var responseBodyClasses = responseBody.attr("class").split(' ');
                $('body').attr('class', '');
                for (var i = 0; i < responseBodyClasses.length; i++) {
                  var ignoredClasses = 'page toolbar-loading path--awards';
                  if (ignoredClasses.indexOf(responseBodyClasses[i]) === -1) {
                    $('body').addClass(responseBodyClasses[i]);
                  }
                }

                $('.region.region-content').css('opacity',0).animate({opacity:1}, 1000, function() {
                  prepareLinks();
                });

              }, 'json');
            });
          }

          function getInternalLinks() {
            var internal_links = [];

            // Strip the host name down, removing ports, subdomains, or www.
            var pattern = /^(([^\/:]+?\.)*)([^\.:]{1,})((\.[a-z0-9]{1,253})*)(:[0-9]{1,5})?$/;
            var host = window.location.host.replace(pattern, '$3$4');
            var subdomain = window.location.host.replace(pattern, '$1');

            // Determine what subdomains are considered internal.
            var subdomains;

            if (typeof drupalSettings.data !== "undefined" && drupalSettings.data.extlink.extSubdomains) {
              subdomains = '([^/]*\\.)?';
            }
            else if (subdomain === 'www.' || subdomain === '') {
              subdomains = '(www\\.)?';
            }
            else {
              subdomains = subdomain.replace('.', '\\.');
            }
            var internal_link = new RegExp('^https?://' + subdomains + host, 'i');

            $( "a" ).each(function( index ) {
              try {
                var url = '';
                if (typeof $(this).attr('href') == 'string') {
                  url = $(this).attr('href').toLowerCase();
                }
                // Handle SVG links (xlink:href).
                else if (typeof $(this).attr('href') == 'object') {
                  url = $(this).attr('href').baseVal;
                }
                if (url.indexOf('http') !== 0 || url.match(internal_link)) {
                  internal_links.push($(this));
                }
              }
              // IE7 throws errors often when dealing with irregular links, such as:
              // <a href="node/10"></a> Empty tags.
              // <a href="http://user:pass@example.com">example</a> User:pass syntax.
              catch (error) {
              }
            });

            return internal_links;
          }

          function prepareLinks() {
            var internal_links = getInternalLinks();
  
            // Apply the target attribute to all links.
            $(internal_links).each(function() {

              // Prevent default click behavior
              $(this).click(function( event ) {
                if (
                  $(this).attr('href').indexOf("/devel") === 0
                ) {
                  return;
                }
                if ( $(this).attr('href').indexOf("admin") === -1 ) {

                  event.preventDefault();
      
                  var postData = {
                    requestUrl: $(this).attr('href')
                  };

                  sendPostRequest(postData, internal_links);
                }
              });
              $(this).attr("data-ajax-loaded-by-module", "ajax-content");
            });
          }

          // History back button handler
          window.addEventListener('popstate', function(event) {
            if (typeof event.state !== "undefined") {
              var postData = {
                requestUrl: (location.pathname+location.search)
              };
              var internal_links = getInternalLinks();
              sendPostRequest(postData, internal_links);
            }
          });

          $( document ).ready(function() {
            // Prepare internal links
            prepareLinks();
            // Push initial state
            var urlSuffix = '';
            if (window.location.href.indexOf('?') != -1) {
              urlSuffix = '?' + window.location.href.substr(window.location.href.indexOf("?") + 1);
            }
            history.pushState({},'', window.location.pathname + urlSuffix);
          });

          componentsLoaded = true;
        }
        document.ajaxContentAttached = true;
      }
    }
  };

})(jQuery, Drupal, this, this.document); // jshint ignore:line
