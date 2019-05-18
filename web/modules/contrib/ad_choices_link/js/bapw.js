var BAPW = (function (i) {
  var f,
    g,
    p = {
    },
    m = 560,
    k = 720,
    l,
    r,
    c,
    a,
    t,
    q = (document.location.protocol == 'https:'),
    d = (q ? 'https' : 'http'),
    v = (q ? 'https://info.evidon.com/c/betrad/pub/' : 'http://cdn.betrad.com/pub/');
  function w(z, x) {
    var B = i(x.of),
      y = B.offset(),
      A;
    if (x.at[0] === 'right') {
      y.left += B.outerWidth()
    }
    if (x.at[1] === 'bottom') {
      y.top += B.outerHeight()
    }
    y.left += x.offset[0];
    y.top += x.offset[1];
    A = i.extend({
    }, y);
    if (x.my[0] === 'right') {
      A.left -= z.outerWidth()
    }
    if (x.my[1] === 'bottom') {
      A.top -= z.outerHeight()
    }
    A.left = Math.round(A.left);
    A.top = Math.round(A.top);
    z.offset(i.extend(A, {
      using: x.using
    }))
  }
  function s(y, x, z) {
    var B,
      A;
    if (!z) {
      A = document.createElement('img');
      A.src = d + '://l.betrad.com/pub/p.gif?pid=' + x.pid + '&ocid=' + x.ocid + '&ic=1&r=' + Math.random();
      A.height = '1';
      A.width = '1';
      document.body.appendChild(A)
    }
    f = y;
    g = i(f);
    i.each(['left',
      'right',
      'top',
      'bottom'], function (C, D) {
      p[D] = parseInt(g.css('padding-' + D), 10)
    });
    l = document.createElement('div');
    r = i(l);
    r.css({
      background: 'url(' + v + 's1.gif) no-repeat center',
      backgroundColor: '#fff',
      border: '2px solid #d7d7d7',
      borderRadius: '8px',
      MozBorderRadius: '8px',
      height: m,
      width: k,
      position: 'absolute',
      visibility: 'visible',
      zIndex: '2147483647'
    });
    l.appendChild(document.createElement('div'));
    B = document.createElement('iframe');
    B.id = '_ev_iframe';
    B.scrolling = 'no';
    B.seamless = 'seamless';
    B.frameBorder = '0';
    B.src = (z ? '' : d + '://info.evidon.com') + '/pub_info/' + x.pid + '?v=1' + (z ? '&preview=true' : '') + '&iptvn=0';
    i(B).css({
      background: 'transparent',
      display: 'block',
      visibility: 'hidden',
      position: 'static',
      height: m - 5,
      width: k
    }).on('load', function () {
      r.css({
        background: '',
        backgroundColor: '#fff'
      });
      this.style.visibility = 'visible'
    });
    l.appendChild(B);
    l.appendChild(document.createElement('div'));
    document.body.appendChild(l);
    o();
    g.click(function (C) {
      BAPW.s();
      C.preventDefault()
    })
  }
  function u() {
    var D = g.offset(),
      E = i(window),
      C = (D.top + p.top - E.scrollTop()),
      A = (D.left + p.left - E.scrollLeft()),
      z = (C >= m),
      y = ((E.height() - C) >= m),
      x = ((A + g.width()) >= k),
      B = ((E.width() - A) >= k);
    t = {
    };
    if (z && !y) {
      a = 'top'
    } else {
      a = 'bottom';
      if (!y) {
        t.scrollTop = D.top
      }
    }
    if (x && !B) {
      c = 'left'
    } else {
      c = 'right';
      if (!B) {
        t.scrollLeft = D.left + p.left
      }
    }
    j()
  }
  function j() {
    var z = 'left',
      C = 'top',
      B = p.left,
      A = - p.bottom;
    if (c == 'left') {
      z = 'right';
      B = - p.right
    }
    if (a == 'top') {
      C = 'bottom';
      A = p.top
    }
    w(r, {
      my: [
        z,
        C
      ],
      at: [
        z,
        a
      ],
      of: g,
      offset: [
        B,
        A
      ]
    })
  }
  function n(z) {
    var x = document.createElement('a'),
      y = document.createElement('img'),
      s = function (t) {
        t.origin.match(/\.evidon\.com$/) && t.data === 'close' && i(x).click()
      };
    x.href = '#';
    i(x).click(function (A) {
      BAPW.s();
      A.preventDefault()
    }).css({
      border: '0',
      'float': 'right',
      padding: '0',
      margin: '0',
      position: 'relative',
      height: '0px',
      left: '-5px',
      top: (z ? '5px' : '-24px')
    });
    y.src = v + 'close1.png';
    y.width = '19';
    y.height = '19';
    i(y).css({
      border: '0',
      display: 'inline',
      position: 'relative',
      top: '5px',
      verticalAlign: 'baseline'
    });
    x.appendChild(y);
    r.children('div').empty();
    r.children((z ? 'div:first' : 'iframe+div')).append(x),
      window.addEventListener ? window.addEventListener('message', s, !1)  : window.attachEvent && window.attachEvent('onmessage', s)
  }
  function b(x) {
    if ((x.type == 'click' && x.target != f && x.target.parentNode != f && x.target != l) || x.which == 27) {
      h()
    }
  }
  function h() {
    i(document).unbind('click keyup', b);
    i(window).unbind('resize', j);
    r.hide()
  }
  function o() {
    r.css('display', 'block');
    u();
    n(a == 'top');
    j();
    if (t.scrollTop || t.scrollLeft) {
      if (!i.browser.msie) {
        i('html,body').animate(t)
      }
    }
    i(document).bind('click keyup', b);
    i(window).bind('resize', j)
  }
  function e() {
    if (l.style.display == 'none') {
      o()
    } else {
      h()
    }
  }
  return {
    i: s,
    s: e
  }
}(jQuery));
