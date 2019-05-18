(function ($) {

// UX: Extend click from rocketship-node container to the link inside.
// I know this looks ugly. It is a quick stop-gap, tips welcome.
jQuery(document).ready(function($) {
    $('.rocketship-node').click(function(e) {
        if (e.which == 1) {
            var link = $(this).find('a');
            if (link.length) {
                window.location = link.attr('href');
            }
        }
    });
});

});
