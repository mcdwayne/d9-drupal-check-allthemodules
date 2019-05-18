window.onload = function () {
    document.getElementById('block-seven-page-title').innerHTML = '<h1>GetResponse</h1>';

    var hints = document.getElementsByClassName('gr-hint');

    for (var i = 0; i < hints.length; i++) {
        hints[i].onclick = function () {
            document.getElementById(this.getAttribute('href').substring(1, 30)).classList.toggle('hidden');

            return false;
        }
    }

    if (document.getElementById('gr-disconnect-btn')) {
        document.getElementById('gr-disconnect-btn').onclick = function () {
            document.getElementById('gr-disconnect-modal').classList.toggle('hidden');
            return false;
        };

        document.getElementById('gr-disconnect-confirm').onclick = function () {
            window.location = document.getElementById('gr-disconnect-btn').href;
            return false;
        };
    }

    document.getElementById('gr-stay-connected').onclick = function () {
        document.getElementById('gr-disconnect-modal').classList.toggle('hidden');
        return false;
    };

    if (document.getElementById('gr-connect')) {
        document.getElementById('gr-connect').setAttribute('href', 'javascript:document.getElementById(\'getresponse-settings\').submit();');
        document.getElementById('gr-connect').onclick = function (e) {
            e.preventDefault();

            if (this.className.match(/\bgr-btn-disable\b/)) {
                return false;
            }

            document.getElementById('getresponse-settings').submit();
        };
    }

    function checkEditApiKey(button) {

        if (button.value === '') {
            if (document.getElementById('gr-connect')) {
                document.getElementById('gr-connect').classList.add('gr-btn-disable');
            }

            var buttons = document.getElementsByClassName('gr-btn-disable');
            for (var x = 0, length = buttons.length; x < length; x++) {
                buttons[x].onclick = function () {
                    if (this.className.match(/\bgr-btn-disable\b/)) {
                        return false;
                    }
                }
            }
        } else {
            if (document.getElementById('gr-connect')) {
                document.getElementById('gr-connect').classList.remove('gr-btn-disable');
            }
        }
    }

    document.getElementById('edit-api-key').onkeyup = function () {
        checkEditApiKey(this);
    };

    if (document.getElementById('edit-api-key').value !== '' && document.getElementById('edit-api-key').className !== 'form-text error') {
        document.getElementById('edit-submit').className = 'button button--primary js-form-submit form-submit gr-btn-display';
    }

    document.getElementById('is-enterprise').onclick = function() {
        enableEnterpriseFormInputs(this);
    }

    function enableEnterpriseFormInputs(checkbox) {
        var i;
        var inputs = document.getElementsByClassName('enterprise-type');

        for (i = 0; i < inputs.length; i++) {
            if (checkbox.checked) {
                inputs[i].style.display = "block";
            } else {
                inputs[i].style.display = "none";
            }
        }
    }
    enableEnterpriseFormInputs(document.getElementById('is-enterprise'));
};
