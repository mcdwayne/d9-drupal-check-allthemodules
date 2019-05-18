<?php

/**
 * @file
 * Font definition based on figlet font "alphabet" (http://www.figlet.org/)
 */

/**
 * As distributed by pyfiglet (http://sourceforge.net/projects/pyfiglet/)
 */
function ascii_art_captcha_font_alphabet() {
  $font = [];
  $font['height'] = 7;
  $font['name'] = 'alphabet';
  $font['comment'] = 'Alphabet by Wendell Hicken 11/93 (whicken@parasoft.com)Explanation of first line:flf2 - "magic number" for file identificationa    - should always be `a\', for now$    - the "hardblank" -- prints as a blank, but can\'t be smushed6    - height of a character5    - height of a character, not including descenders20   - max line length (excluding comment lines) + a fudge factor15   - default smushmode for this font (like "-m 15" on command line)12   - number of comment lines';
  $font['a'] = [
    '    ',
    '    ',
    ' aa ',
    'a a ',
    'aaa ',
    '    ',
    '    ',
  ];
  $font['b'] = [
    'b    ',
    'b    ',
    'bbb  ',
    'b  b ',
    'bbb  ',
    '     ',
    '     ',
  ];
  $font['c'] = [
    '     ',
    '     ',
    ' ccc ',
    'c    ',
    ' ccc ',
    '     ',
    '     ',
  ];
  $font['d'] = [
    '   d ',
    '   d ',
    ' ddd ',
    'd  d ',
    ' ddd ',
    '     ',
    '     ',
  ];
  $font['e'] = [
    '    ',
    '    ',
    'eee ',
    'e e ',
    'ee  ',
    '    ',
    '    ',
  ];
  $font['f'] = [
    ' ff ',
    ' f  ',
    'fff ',
    ' f  ',
    ' f  ',
    '    ',
    '    ',
  ];
  $font['g'] = [
    '    ',
    '    ',
    'ggg ',
    'g g ',
    'ggg ',
    '  g ',
    'ggg ',
  ];
  $font['h'] = [
    'h    ',
    'h    ',
    'hhh  ',
    'h  h ',
    'h  h ',
    '     ',
    '     ',
  ];
  $font['i'] = [
    '   ',
    'ii ',
    '   ',
    'ii ',
    'ii ',
    '   ',
    '   ',
  ];
  $font['j'] = [
    '     ',
    '   j ',
    '     ',
    '   j ',
    '   j ',
    'j  j ',
    ' jj  ',
  ];
  $font['k'] = [
    'k    ',
    'k k  ',
    'kk   ',
    'k k  ',
    'k  k ',
    '     ',
    '     ',
  ];
  $font['l'] = [
    'l ',
    'l ',
    'l ',
    'l ',
    'l ',
    '  ',
    '  ',
  ];
  $font['m'] = [
    '      ',
    '      ',
    'mmmm  ',
    'm m m ',
    'm m m ',
    '      ',
    '      ',
  ];
  $font['n'] = [
    '     ',
    '     ',
    'nnn  ',
    'n  n ',
    'n  n ',
    '     ',
    '     ',
  ];
  $font['o'] = [
    '    ',
    '    ',
    'ooo ',
    'o o ',
    'ooo ',
    '    ',
    '    ',
  ];
  $font['p'] = [
    '     ',
    '     ',
    'ppp  ',
    'p  p ',
    'ppp  ',
    'p    ',
    'p    ',
  ];
  $font['q'] = [
    '      ',
    '      ',
    ' qqq  ',
    'q  q  ',
    ' qqq  ',
    '   q  ',
    '   qq ',
  ];
  $font['r'] = [
    '    ',
    '    ',
    'rrr ',
    'r   ',
    'r   ',
    '    ',
    '    ',
  ];
  $font['s'] = [
    '    ',
    '    ',
    ' ss ',
    ' s  ',
    'ss  ',
    '    ',
    '    ',
  ];
  $font['t'] = [
    ' t  ',
    ' t  ',
    'ttt ',
    ' t  ',
    ' tt ',
    '    ',
    '    ',
  ];
  $font['u'] = [
    '     ',
    '     ',
    'u  u ',
    'u  u ',
    ' uuu ',
    '     ',
    '     ',
  ];
  $font['v'] = [
    '    ',
    '    ',
    'v v ',
    'v v ',
    ' v  ',
    '    ',
    '    ',
  ];
  $font['w'] = [
    '      ',
    '      ',
    'w   w ',
    'w w w ',
    ' w w  ',
    '      ',
    '      ',
  ];
  $font['x'] = [
    '    ',
    '    ',
    'x x ',
    ' x  ',
    'x x ',
    '    ',
    '    ',
  ];
  $font['y'] = [
    '     ',
    '     ',
    'y  y ',
    'y  y ',
    ' yyy ',
    '   y ',
    'yyy  ',
  ];
  $font['z'] = [
    '    ',
    '    ',
    'zz  ',
    ' z  ',
    ' zz ',
    '    ',
    '    ',
  ];
  $font['A'] = [
    ' AA  ',
    'A  A ',
    'AAAA ',
    'A  A ',
    'A  A ',
    '     ',
    '     ',
  ];
  $font['B'] = [
    'BBBB  ',
    'B   B ',
    'BBBB  ',
    'B   B ',
    'BBBB  ',
    '      ',
    '      ',
  ];
  $font['C'] = [
    ' CCC ',
    'C    ',
    'C    ',
    'C    ',
    ' CCC ',
    '     ',
    '     ',
  ];
  $font['D'] = [
    'DDD  ',
    'D  D ',
    'D  D ',
    'D  D ',
    'DDD  ',
    '     ',
    '     ',
  ];
  $font['E'] = [
    'EEEE ',
    'E    ',
    'EEE  ',
    'E    ',
    'EEEE ',
    '     ',
    '     ',
  ];
  $font['F'] = [
    'FFFF ',
    'F    ',
    'FFF  ',
    'F    ',
    'F    ',
    '     ',
    '     ',
  ];
  $font['G'] = [
    ' GGG  ',
    'G     ',
    'G  GG ',
    'G   G ',
    ' GGG  ',
    '      ',
    '      ',
  ];
  $font['H'] = [
    'H  H ',
    'H  H ',
    'HHHH ',
    'H  H ',
    'H  H ',
    '     ',
    '     ',
  ];
  $font['I'] = [
    'III ',
    ' I  ',
    ' I  ',
    ' I  ',
    'III ',
    '    ',
    '    ',
  ];
  $font['J'] = [
    '    J ',
    '    J ',
    '    J ',
    'J   J ',
    ' JJJ  ',
    '      ',
    '      ',
  ];
  $font['K'] = [
    'K  K ',
    'K K  ',
    'KK   ',
    'K K  ',
    'K  K ',
    '     ',
    '     ',
  ];
  $font['L'] = [
    'L    ',
    'L    ',
    'L    ',
    'L    ',
    'LLLL ',
    '     ',
    '     ',
  ];
  $font['M'] = [
    'M   M ',
    'MM MM ',
    'M M M ',
    'M   M ',
    'M   M ',
    '      ',
    '      ',
  ];
  $font['N'] = [
    'N   N ',
    'NN  N ',
    'N N N ',
    'N  NN ',
    'N   N ',
    '      ',
    '      ',
  ];
  $font['O'] = [
    ' OOO  ',
    'O   O ',
    'O   O ',
    'O   O ',
    ' OOO  ',
    '      ',
    '      ',
  ];
  $font['P'] = [
    'PPPP  ',
    'P   P ',
    'PPPP  ',
    'P     ',
    'P     ',
    '      ',
    '      ',
  ];
  $font['Q'] = [
    ' QQQ   ',
    'Q   Q  ',
    'Q   Q  ',
    'Q  QQ  ',
    ' QQQQ  ',
    '     Q ',
    '       ',
  ];
  $font['R'] = [
    'RRRR  ',
    'R   R ',
    'RRRR  ',
    'R R   ',
    'R  RR ',
    '      ',
    '      ',
  ];
  $font['S'] = [
    ' SSS  ',
    'S     ',
    ' SSS  ',
    '    S ',
    'SSSS  ',
    '      ',
    '      ',
  ];
  $font['T'] = [
    'TTTTTT ',
    '  TT   ',
    '  TT   ',
    '  TT   ',
    '  TT   ',
    '       ',
    '       ',
  ];
  $font['U'] = [
    'U   U ',
    'U   U ',
    'U   U ',
    'U   U ',
    ' UUU  ',
    '      ',
    '      ',
  ];
  $font['V'] = [
    'V     V ',
    'V     V ',
    ' V   V  ',
    '  V V   ',
    '   V    ',
    '        ',
    '        ',
  ];
  $font['W'] = [
    'W     W ',
    'W     W ',
    'W  W  W ',
    ' W W W  ',
    '  W W   ',
    '        ',
    '        ',
  ];
  $font['X'] = [
    'X   X ',
    ' X X  ',
    '  X   ',
    ' X X  ',
    'X   X ',
    '      ',
    '      ',
  ];
  $font['Y'] = [
    'Y   Y ',
    ' Y Y  ',
    '  Y   ',
    '  Y   ',
    '  Y   ',
    '      ',
    '      ',
  ];
  $font['Z'] = [
    'ZZZZZ ',
    '   Z  ',
    '  Z   ',
    ' Z    ',
    'ZZZZZ ',
    '      ',
    '      ',
  ];
  $font['0'] = [
    ' 000  ',
    '0  00 ',
    '0 0 0 ',
    '00  0 ',
    ' 000  ',
    '      ',
    '      ',
  ];
  $font['1'] = [
    ' 11  ',
    '111  ',
    ' 11  ',
    ' 11  ',
    '11l1 ',
    '     ',
    '     ',
  ];
  $font['2'] = [
    ' 22  ',
    '2  2 ',
    '  2  ',
    ' 2   ',
    '2222 ',
    '     ',
    '     ',
  ];
  $font['3'] = [
    '333  ',
    '   3 ',
    ' 33  ',
    '   3 ',
    '333  ',
    '     ',
    '     ',
  ];
  $font['4'] = [
    '4  4 ',
    '4  4 ',
    '4444 ',
    '   4 ',
    '   4 ',
    '     ',
    '     ',
  ];
  $font['5'] = [
    '5555 ',
    '5    ',
    '555  ',
    '   5 ',
    '555  ',
    '     ',
    '     ',
  ];
  $font['6'] = [
    '  6   ',
    ' 6    ',
    '6666  ',
    '6   6 ',
    ' 666  ',
    '      ',
    '      ',
  ];
  $font['7'] = [
    '77777 ',
    '   7  ',
    '  7   ',
    '  7   ',
    '  7   ',
    '      ',
    '      ',
  ];
  $font['8'] = [
    ' 888  ',
    '8   8 ',
    ' 888  ',
    '8   8 ',
    ' 888  ',
    '      ',
    '      ',
  ];
  $font['9'] = [
    ' 9999 ',
    '9   9 ',
    ' 9999 ',
    '   9  ',
    '  9   ',
    '      ',
    '      ',
  ];

  return $font;
}
