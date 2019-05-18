/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

(function ($) {
    $(document).ready( function (){
        $("#feedmine-block-contents").hide();

        $(".feedmine-block-contents h2").click( function (){
            $("#feedmine-block-contents").slideToggle();
        });

        $(".feedmine-block-contents .form-submit").after("<button id=hide-me>Cancel</button>");

        $(".feedmine-block-contents button#hide-me").click( function (){
            $("#feedmine-block-contents").hide();
            return false;
        });
    });

})(jQuery);