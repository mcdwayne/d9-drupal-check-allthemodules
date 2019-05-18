(function ($, Drupal) {

    $('a[data-imagelightbox="g"]').imageLightbox({
        animationSpeed: 150,                     // integer;
        activity:       false,                   // bool;            show activity indicator
        arrows:         true,                   // bool;            show left/right arrows
        button:         true,                   // bool;            show close button
        caption:        true,                   // bool;            show captions
        enableKeyboard: true,                    // bool;            enable keyboard shortcuts (arrows Left/Right and Esc)
        fullscreen:     true,                     // bool;            enable fullscreen (enter/return key)
        gutter:         10,                      // integer;         window height less height of image as a percentage
        offsetY:        0,                       // integer;         vertical offset in terms of gutter
        navigation:     false,                   // bool;            show navigation
        overlay:        true,                   // bool;            display the lightbox as an overlay
        preloadNext:    true,                    // bool;            silently preload the next image
        quitOnEnd:      true,                   // bool;            quit after viewing the last image
        quitOnImgClick: false,                   // bool;            quit when the viewed image is clicked
        quitOnDocClick: true,                    // bool;            quit when anything but the viewed image is clicked
        quitOnEscKey:   true                     // bool;            quit when Esc key is pressed


    });
    
    
    $('a[data-imagelightbox="events"]').imageLightbox();
    $(document)
        .on("start.ilb2", function () {
            console.log("start.ilb2");
        })
        .on("quit.ilb2", function () {
            console.log("quit.ilb2");
        })
        .on("loaded.ilb2", function () {
            console.log("loaded.ilb2");
        })
        .on("previous.ilb2", function () {
            console.log("next.ilb2");
        })
        .on("next.ilb2", function () {
            console.log("previous.ilb2");
        });
})(jQuery, Drupal);