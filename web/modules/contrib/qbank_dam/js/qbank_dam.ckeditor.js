(function ($, Drupal, drupalSettings, CKEDITOR) {
    'use strict';

    Drupal.behaviors.qbankdamSetup = {
        attach: function (context, setting){
            var setupWindowSize = function () {
                var mainWindow = jQuery('#' + drupalSettings.qbank_dam.html_id).parent().parent();
                mainWindow.css({
                    'top': '100px',
                    'left': '100px',
                    'width': jQuery(window).width() - 200 + 'px',
                    'max-width' : '100%'
                });

                jQuery('#' + drupalSettings.qbank_dam.html_id).css(
                    {
                        'height': jQuery(window).height() - 275 +'px'
                    }
                );
            };
            var mediaSelected = function (media, image) {
                 jQuery("input[name='qbank_url']").val(image[0].url);
                 jQuery("input[name='qbank_extension']").val(image[0].extension);
                 jQuery("input[name='qbank_title']").val(media.name);
                 jQuery("input[name='qbank_media_id']").val(media.mediaId);
                 jQuery('<div role="contentinfo" aria-label="Status message" class="messages messages--status"><div role="alert"><h2 class="visually-hidden">Status message</h2>Downloading media from QBank DAM</div></div>').insertBefore('#' + drupalSettings.qbank_dam.html_id);
                 jQuery("button.js-form-submit").trigger("click");
            };

            var qbcConfig = {
                deploymentSite: drupalSettings.qbank_dam.deployment_site,
                api: {
                    host: drupalSettings.qbank_dam.url,
                    access_token: drupalSettings.qbank_dam.token,
                    protocol: drupalSettings.qbank_dam.protocol
                },
                gui: {
                    basehref: drupalSettings.qbank_dam.protocol + '://' + drupalSettings.qbank_dam.url + '/connector/'
                }
            };

            var QBC = new QBankConnector(qbcConfig);

            var mediaPicker = new QBC.mediaPicker({
                container: '#' + drupalSettings.qbank_dam.html_id,
                onSelect: mediaSelected,
                onReady:  setupWindowSize,
                modules: {
                    folders: true,
                    categories: true,
                    moodboards: true,
                    settings: true,
                    content: {
                        header: false,
                        toolbar: true,
                        details: true
                    },
                    imageTool: {
                        crop: true
                    },
                    detail: {
                        showUseButton: true
                    },
                    searchResult: {
                        showUseButton: false
                    }
                }
            });
        }
    };

})(jQuery, Drupal, drupalSettings, CKEDITOR);