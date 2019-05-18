(function ($) {
    var oWindow = $(window);

    // на больших разрешениях убирает затемнение
    function hideBlackout() {
        var windowWidth = gInnerWidth();
        var $loginPanel = $('.login-panel');
        if (!$loginPanel.length) {
            return false;
        }
        var $blackout = $loginPanel.find('.login-panel__blackout');
        var $wrap = $('.login-panel__wrap');
        var $container = $('.container').eq(0);
        var containerWidth = $container.outerWidth();
        var wrapWidth = $wrap.outerWidth();
        if ( windowWidth > (containerWidth + wrapWidth * 2) ) {
            $blackout.addClass('login-panel__blackout--bg-transparent');
        } else {
            $blackout.removeClass('login-panel__blackout--bg-transparent');
        }
        $wrap.css({
            'min-height': gInnerHeight()
        });
    }

    function openLoginPanel() {
        //closeMainMenu();
        var $loginPanel = $('.login-panel');
        if (!$loginPanel.length) {
            return false;
        }
        var $html = $('html');
        var $gHeaderMobf = $('.g-header_mob-fixed');
        var $wrap = $('.login-panel__wrap');
        var scrollWidth = widthScrollBar();
        var body = $('body');
        body.css({
            'padding-right': ''
        });
        $html.css({
            'padding-right': scrollWidth,
            'overflow': 'hidden'
        });
        $gHeaderMobf.css({
            'padding-right': scrollWidth
        });
        $loginPanel.addClass('login-panel--scrollable').fadeIn(300);
        $wrap.addClass('login-panel__wrap--shown');
        hideBlackout();
    }

    function closeLoginPanel() {
        var $loginPanel = $('.login-panel');
        if (!$loginPanel.length) {
            return false;
        }
        var $html = $('html');
        var $gHeaderMobf = $('.g-header_mob-fixed');
        var $wrap = $('.login-panel__wrap');
        $html.css({
            'padding-right': '',
            'overflow': ''
        });
        $gHeaderMobf.css({
            'padding-right': ''
        });
        $wrap.removeClass('login-panel__wrap--shown');
        $loginPanel.fadeOut(300).removeClass('login-panel--scrollable');
    }

    function showLoginPanel() {

        var $loginHeadBtn = $('.login-head__btn');
        var $loginPanel = $('.login-panel');
        var $close = $('.login-panel__close');
        if (!$loginHeadBtn.length || !$loginPanel.length || !$close.length) {
            return false;
        }
        var $wrap = $('.login-panel__wrap');
        $wrap.css({
            'min-height': gInnerHeight()
        });
        var $loginContainer = $('.login-panel__container');
        $loginContainer.off('click.showLoginPanel').on('click.showLoginPanel', function(e) {
            e.stopPropagation();
        });
        $loginPanel.off('click.showLoginPanel').on('click.showLoginPanel', function(e) {
            e.preventDefault();
            closeLoginPanel();
        });
        $loginHeadBtn.off('click.showLoginPanel').on('click.showLoginPanel', function (e) {
            e.preventDefault();
            openLoginPanel();
        });
        $close.off('click.showLoginPanel').on('click.showLoginPanel', function (e) {
            e.preventDefault();
            closeLoginPanel();
        });
    }

    function gInnerHeight() {
        var iOS = /Safari|iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream && !/Chrome/.test(navigator.userAgent);
        var ih = (iOS) ? $(window).innerHeight() : window.innerHeight;
        return ih;
    }

//ширина скролла
    function widthScrollBar() {
        var div = document.createElement('div');
        div.style.overflowY = 'scroll';
        div.style.width = '50px';
        div.style.height = '50px';
        div.style.visibility = 'hidden';
        document.body.appendChild(div);
        var scrollWidth = div.offsetWidth - div.clientWidth;
        document.body.removeChild(div);
        return scrollWidth;
    }

    $(function () {
        showLoginPanel();
    });

    oWindow.on('load.showLoginPanel', function () {
        showLoginPanel();
    });

    oWindow.on('resize.showLoginPanel', function () {
        hideBlackout();
    });
})(jQuery);
