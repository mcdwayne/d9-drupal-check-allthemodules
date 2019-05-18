(function ($, Drupal, drupalSettings) {
    'use strict';

    Drupal.behaviors.qbankdamSetup = {
        attach: function (context, setting) {

            jQuery('.is-entity-browser-submit').hide();

            var setupWindowSize = function () {
                jQuery('#' + drupalSettings.qbank_dam.html_id).css({
                    'height': jQuery(window).height() - 125 + 'px'
                });
            };

            var mediaSelected = function (media, image) {
                jQuery("input[name='qbank_url']").val(image[0].url);
                jQuery("input[name='qbank_extension']").val(image[0].extension);
                jQuery("input[name='qbank_title']").val(media.name);
                jQuery("input[name='qbank_media_id']").val(media.mediaId);
                jQuery("input#edit-submit").click();
                jQuery('<div role="contentinfo" aria-label="Status message" class="messages messages--status"><div role="alert"><h2 class="visually-hidden">Status message</h2>Downloading media from QBank DAM</div></div>').insertAfter('input#edit-submit');
            };

            var protocol = drupalSettings.qbank_dam.protocol === 'HTTPS' ? 'https' : 'http';

            var qbcConfig = {
                deploymentSite: drupalSettings.qbank_dam.deployment_site,
                api: {
                    host: drupalSettings.qbank_dam.url,
                    access_token: drupalSettings.qbank_dam.token,
                    protocol: protocol //drupalSettings.qbank_dam.protocol
                },
                gui: {
                    basehref: protocol + '://' + drupalSettings.qbank_dam.url + '/connector/'
                }
            };

            var QBC = new QBankConnector(qbcConfig);

            var mediaPicker = new QBC.mediaPicker({
                container: '#' + drupalSettings.qbank_dam.html_id,
                onSelect: mediaSelected,
                onReady: setupWindowSize,
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

})(jQuery, Drupal, drupalSettings);