(function ($) {
    var dialog;
    var picker = null;
    var pickedAssets = [];
    assetPickerDialog ();
    Drupal.behaviors.celum_connect_widget = {
        attach: function (context) {
            $ ('#asset-picker-button').bind ('click', function (event) {
                event.preventDefault ();
                celumConnectFieldSetter ();
            });
        }
    };

    function assetPickerDialog () {
        var btns = {};
        btns[Drupal.t ('Cancel')] = function () {
            $ (this).dialog ("close");
        };
        var dialogHTML = '';
        dialogHTML += '<div id="celum_asset_picker_dialog">';
        dialogHTML += '<div id="celum_asset_picker_dialog"><div id="picker-control"> ' +
            '<label class="radio-inline"> ' +
            '<input type="radio" checked name="optradio" value="download">Load assets to Drupal 8 ' +
            '</label> ' +
            '<label class="radio-inline"> ' +
            '<input type="radio" name="optradio" value="link">Link assets from Celum ' +
            '</label> ' +
            '</div>';
        dialogHTML += '<div id="picker-wrap"></div>';
        dialogHTML += '</div>';


        $ ('body').append (dialogHTML);

        dialog = $ ('#celum_asset_picker_dialog').dialog ({
            modal: true,
            autoOpen: false,
            width: 1320,
            height: 800,
            closeOnEscape: true,
            resizable: false,
            draggable: false,
            dialogClass: 'celum_asset_picker_dialog',
            buttons: btns
        });
    }

    function celumConnectFieldSetter () {
        dialog.dialog ('open');
        if (picker === null) {
            createAssetPicker (dialog);
        }
        return false;
    }

    function createAssetPicker (dialog) {
        picker = Celum.AssetPicker.create ({
            container: 'picker-wrap',
            basePath: '../',
            jsConfigPath: '../config.js?'+(new Date()).getTime(),
            listeners: {
                transfer: function (id, selections) {
                    console.log(selections);
                    var assets = [];
                    selections.forEach (function (asset) {
                        var selectedDownloads = asset.selectedDownloads;
                        selectedDownloads.forEach (function (selectedDownload) {
                            assets.push (
                                {
                                    url: asset['@odata.mediaReadLink'],
                                    id: asset['id'],
                                    version: asset['versionInformation']['versionId'],
                                    downloadFormat: selectedDownload,
                                    fileExtension: asset['fileInformation']['fileExtension'],
                                    title: asset['name'],
                                    fileCategory: asset['fileCategory'],
                                    preview: asset['previewInformation']['previewUrl'],
                                    thumb: asset['previewInformation']['thumbUrl']
                                });
                        });
                    });
                    addAssets (assets, jQuery("input[name='optradio']:checked").val());
                    assets = [];
                    dialog.dialog ("close");
                }
            }
        });
    }

    function addAssets (assets, type) {
        console.log(type);
        $ ('input[name="assetsNum"]').prop ('value', assets.length).attr ('value', assets.length);
        pickedAssets = assets;
        for (var i = 0; i < assets.length; i++) {
            $ ('#added_assets_table').append (
                '<tr> ' +
                '<td>' +
                '<input data-id-delta="' + i + '" type="hidden" name="added_asset[' + i + '][id]" value=""> ' +
                '<input data-downloadformat-delta="' + i + '"type="hidden" name="added_asset[' + i + '][downloadFormat]" value=""> ' +
                '<input data-version-delta="' + i + '" type="hidden" name="added_asset[' + i + '][version]" value=""> ' +
                '<input data-fileextension-delta="' + i + '" type="hidden" name="added_asset[' + i + '][fileExtension]" value=""> ' +
                '<input data-filecategory-delta="' + i + '" type="hidden" name="added_asset[' + i + '][fileCategory]" value=""> ' +
                '<input data-title-delta="' + i + '" type="hidden" name="added_asset[' + i + '][title]" value=""> ' +
                '<input data-thumb-delta="' + i + '" type="hidden" name="added_asset[' + i + '][thumb]" value=""> ' +
                '<input data-uri-delta="' + i + '" type="hidden" name="added_asset[' + i + '][uri]" value=""> ' +
                '<input data-download-delta="' + i + '" type="hidden" name="added_asset[' + i + '][download]" value=""> ' +
                '<input data-type-delta="' + i + '" type="hidden" name="added_asset[' + i + '][type]" value=""> ' +
                '</td> ' +
                '<td > ' +
                '</td> ' +
                '</tr>');
            $ ('input[data-id-delta="' + i + '"]').prop ('value', pickedAssets[i].id).attr ('value', pickedAssets[i].id);
            $ ('input[data-version-delta="' + i + '"]').prop ('value', pickedAssets[i].version).attr ('value', pickedAssets[i].version);
            $ ('input[data-downloadFormat-delta="' + i + '"]').prop ('value', pickedAssets[i].downloadFormat).attr ('value', pickedAssets[i].downloadFormat);
            $ ('input[data-fileExtension-delta="' + i + '"]').prop ('value', pickedAssets[i].fileExtension).attr ('value', pickedAssets[i].fileExtension);
            $ ('input[data-title-delta="' + i + '"]').prop ('value', pickedAssets[i].title).attr ('value', pickedAssets[i].title);
            $ ('input[data-fileCategory-delta="' + i + '"]').prop ('value', pickedAssets[i].fileCategory).attr ('value', pickedAssets[i].fileCategory);
            $ ('input[data-thumb-delta="' + i + '"]').prop ('value', pickedAssets[i].thumb).attr ('value', pickedAssets[i].thumb);
            $ ('input[data-download-delta="' + i + '"]').prop ('value', pickedAssets[i].url).attr ('value', pickedAssets[i].url);
            $ ('input[data-type-delta="' + i + '"]').prop ('value', pickedAssets[i].type).attr ('value', type);
        }
        $ ('input[data-id="add_assets_celum_connect"]').mousedown ();

    }

}) (jQuery);