(function ($) {

    'use strict';

    function generateVerifoneKeys(url, type, id) {
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                ajax: true,
                keys_type: type,
                id: id
            },
            success: function (response) {
                console.log(response);
                var message = response.messages.join('\\n');
                alert(message);

                if (response.success === false) {
                    console.log(res);
                }

            },
            error: function (xhr, ajaxOptions, thrownError) {
                console.log(xhr, ajaxOptions, thrownError);
            }
        });
    }

    function changeVisibility() {
        var current = $('[name^="configuration"][name$="[key_handling_mode]"]').val();

        if (current === '0') {
            jQuery('.depends-key_handling_mode-0').closest('div.form-item').show();
            jQuery('.depends-key_handling_mode-1').closest('div.form-item').hide();
        } else {
            jQuery('.depends-key_handling_mode-0').closest('div.form-item').hide();
            jQuery('.depends-key_handling_mode-1').closest('div.form-item').show();
        }
    }

    function initModal() {
        // Get the modal
        var modal = document.getElementById("verifone-summary-modal");

        // Get the button that opens the modal
        var btn = document.getElementById("verifone-summary-modal-trigger");

        // Get the <span> element that closes the modal
        var span = modal.getElementsByClassName("close")[0];

        // When the user clicks the button, open the modal
        btn.onclick = function () {
            modal.style.display = "block";
        };

        // When the user clicks on <span> (x), close the modal
        span.onclick = function () {
            modal.style.display = "none";
        };

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    }

    $(document).ready(function () {

        $('button[name^="refresh_verifone_keys_"]').on('click', function (e) {
            e.preventDefault();
            var $this = $(this);
            if (confirm($this.attr('data-confirm_msg'))) {
                generateVerifoneKeys($this.attr('data-url'), $this.attr('data-type'), $('#edit-id').val());
            }
        });

        $('[name^="configuration"][name$="[key_handling_mode]"]').on('change', function () {
            changeVisibility();
        });

        changeVisibility();
        initModal();
    });

})(jQuery);
