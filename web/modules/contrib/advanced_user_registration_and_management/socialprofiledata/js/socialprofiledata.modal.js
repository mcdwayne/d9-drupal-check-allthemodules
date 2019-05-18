/*
 @file
 */
(function($, Drupal,settings)
{
    Drupal.behaviors.socialprofiledata = {
        attach: function(context)
        {
            // Create a container div for the modal if one isn't there already
            if ($("#socialprofiledata-container").length == 0)
            {
                // Add a container to the end of the body tag to hold the dialog
                $('body').append('<div id="socialprofiledata-container" style="display:none;"></div>');
                try
                {
                    $("#socialprofiledata-container", context).dialog({
                        autoOpen: false,
                        modal: true,
                        close: function(event, ui)
                        {
                            // Clear the dialog on close. Not necessary for your average use
                            // case, butis useful if you had a video that was playing in the
                            // dialog so that it clears when it closes
                            $('#socialprofiledata-container').html('');
                        }
                    });
                    var defaultOptions = Drupal.socialprofiledata.explodeOptions(settings.socialprofiledata.defaults);
                    $('#socialprofiledata-container').dialog('option', defaultOptions);
                }
                catch (err)
                {
                    // Catch any errors and report
                    Drupal.socialprofiledata.log('[error] socialprofiledata Dialog: ' + err);
                }
            }
            
        }
    }
    Drupal.socialprofiledata = {};

    // Convert the options to an object
    Drupal.socialprofiledata.explodeOptions = function(opts)
    {
        var options = opts.split(';');
        var explodedOptions = {};
        for (var i in options)
        {
            if (options[i])
            {
                // Parse and Clean the option
                var option = Drupal.socialprofiledata.cleanOption(options[i].split(':'));
                explodedOptions[option[0]] = option[1];
            }
        }
        return explodedOptions;
    }

    // Function to clean up the option.
    Drupal.socialprofiledata.cleanOption = function(option)
    {
        // If it's a position option, we may need to parse an array
        if (option[0] == 'position' && option[1].match(/\[.*,.*\]/))
        {
            option[1] = option[1].match(/\[(.*)\]/)[1].split(',');
            // Check if positions need be converted to int
            if (!isNaN(parseInt(option[1][0])))
            {
                option[1][0] = parseInt(option[1][0]);
            }
            if (!isNaN(parseInt(option[1][1])))
            {
                option[1][1] = parseInt(option[1][1]);
            }
        }
        // Convert text boolean representation to boolean
        if (option[1] === 'true')
        {
            option[1] = true;
        }
        else
        {
            if (option[1] === 'false')
            {
                option[1] = false;
            }
        }
        return option;
    }

    Drupal.socialprofiledata.log = function(msg)
    {
        if (window.console)
        {
            window.console.log(msg);
        }

    }
//    function twig_json_decode($json)
//{
//    return json_decode($json, true);
//}

})(jQuery, Drupal, drupalSettings);
