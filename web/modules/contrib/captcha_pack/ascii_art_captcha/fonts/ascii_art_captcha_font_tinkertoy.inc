<?php

/**
 * @file
 * Font definition based on figlet font "tinker-toy" (http://www.figlet.org/)
 */

/**
 * As distributed by pyfiglet (http://sourceforge.net/projects/pyfiglet/)
 */
function ascii_art_captcha_font_tinkertoy() {
  $font = [];
  $font['height'] = 7;
  $font['name'] = 'tinker-toy';
  $font['comment'] = 'Tinker-toy by Wendell Hicken 11/93 (whicken@parasoft.com)Explanation of first line:flf2 - "magic number" for file identificationa    - should always be `a\', for now$    - the "hardblank" -- prints as a blank, but can\'t be smushed7    - height of a character5    - height of a character, not including descenders5    - max line length (excluding comment lines) + a fudge factor15   - default smushmode for this font (like "-m 15" on command line)12   - number of comment lines';
  $font['a'] = [
    '    ',
    '    ',
    ' oo ',
    '| | ',
    'o-o-',
    '    ',
    '    ',
  ];
  $font['b'] = [
    'o    ',
    '|    ',
    'O-o  ',
    '|  | ',
    'o-o  ',
    '     ',
    '     ',
  ];
  $font['c'] = [
    '     ',
    '     ',
    ' o-o ',
    '|    ',
    ' o-o ',
    '     ',
    '     ',
  ];
  $font['d'] = [
    '   o ',
    '   | ',
    ' o-O ',
    '|  | ',
    ' o-o ',
    '     ',
    '     ',
  ];
  $font['e'] = [
    '    ',
    '    ',
    'o-o ',
    '|-\' ',
    'o-o ',
    '    ',
    '    ',
  ];
  $font['f'] = [
    ' o-o',
    ' |  ',
    '-O- ',
    ' |  ',
    ' o  ',
    '    ',
    '    ',
  ];
  $font['g'] = [
    '     ',
    '     ',
    'o--o ',
    '|  | ',
    'o--O ',
    '   | ',
    'o--o ',
  ];
  $font['h'] = [
    'o    ',
    '|    ',
    'O--o ',
    '|  | ',
    'o  o ',
    '     ',
    '     ',
  ];
  $font['i'] = [
    '  ',
    'o ',
    '  ',
    '| ',
    '| ',
    '  ',
    '  ',
  ];
  $font['j'] = [
    '      ',
    '    o ',
    '      ',
    '    o ',
    '    | ',
    'o   o ',
    ' o-o  ',
  ];
  $font['k'] = [
    'o    ',
    '| /  ',
    'OO   ',
    '| \\  ',
    'o  o ',
    '     ',
    '     ',
  ];
  $font['l'] = [
    'o ',
    '| ',
    '| ',
    '| ',
    'o ',
    '  ',
    '  ',
  ];
  $font['m'] = [
    '      ',
    '      ',
    'o-O-o ',
    '| | | ',
    'o o o ',
    '      ',
    '      ',
  ];
  $font['n'] = [
    '     ',
    '     ',
    'o-o  ',
    '|  | ',
    'o  o ',
    '     ',
    '     ',
  ];
  $font['o'] = [
    '    ',
    '    ',
    'o-o ',
    '| | ',
    'o-o ',
    '    ',
    '    ',
  ];
  $font['p'] = [
    '     ',
    '     ',
    'o-o  ',
    '|  | ',
    'O-o  ',
    '|    ',
    'o    ',
  ];
  $font['q'] = [
    '     ',
    '     ',
    ' o-o ',
    '|  | ',
    ' o-O ',
    '   | ',
    '   o ',
  ];
  $font['r'] = [
    '    ',
    '    ',
    'o-o ',
    '|   ',
    'o   ',
    '    ',
    '    ',
  ];
  $font['s'] = [
    '    ',
    '    ',
    'o-o ',
    ' \\  ',
    'o-o ',
    '    ',
    '    ',
  ];
  $font['t'] = [
    ' o  ',
    ' |  ',
    '-o- ',
    ' |  ',
    ' o  ',
    '    ',
    '    ',
  ];
  $font['u'] = [
    '     ',
    '     ',
    'o  o ',
    '|  | ',
    'o--o ',
    '     ',
    '     ',
  ];
  $font['v'] = [
    '      ',
    '      ',
    'o   o ',
    ' \\ /  ',
    '  o   ',
    '      ',
    '      ',
  ];
  $font['w'] = [
    '         ',
    '         ',
    'o   o   o',
    ' \\ / \\ / ',
    '  o   o  ',
    '         ',
    '         ',
  ];
  $font['x'] = [
    '    ',
    '    ',
    '\\ / ',
    ' o  ',
    '/ \\ ',
    '    ',
    '    ',
  ];
  $font['y'] = [
    '     ',
    '     ',
    'o  o ',
    '|  | ',
    'o--O ',
    '   | ',
    'o--o ',
  ];
  $font['z'] = [
    '    ',
    '    ',
    'o-o ',
    ' /  ',
    'o-o ',
    '    ',
    '    ',
  ];
  $font['A'] = [
    '  O  ',
    ' / \\ ',
    'o---o',
    '|   |',
    'o   o',
    '     ',
    '     ',
  ];
  $font['B'] = [
    'o--o  ',
    '|   | ',
    'O--o  ',
    '|   | ',
    'o--o  ',
    '      ',
    '      ',
  ];
  $font['C'] = [
    '  o-o ',
    ' /    ',
    'O     ',
    ' \\    ',
    '  o-o ',
    '      ',
    '      ',
  ];
  $font['D'] = [
    'o-o   ',
    '|  \\  ',
    '|   O ',
    '|  /  ',
    'o-o   ',
    '      ',
    '      ',
  ];
  $font['E'] = [
    'o--o ',
    '|    ',
    'O-o  ',
    '|    ',
    'o--o ',
    '     ',
    '     ',
  ];
  $font['F'] = [
    'o--o ',
    '|    ',
    'O-o  ',
    '|    ',
    'o    ',
    '     ',
    '     ',
  ];
  $font['G'] = [
    ' o-o  ',
    'o     ',
    '|  -o ',
    'o   | ',
    ' o-o  ',
    '      ',
    '      ',
  ];
  $font['H'] = [
    'o  o ',
    '|  | ',
    'O--O ',
    '|  | ',
    'o  o ',
    '     ',
    '     ',
  ];
  $font['I'] = [
    'o-O-o ',
    '  |   ',
    '  |   ',
    '  |   ',
    'o-O-o ',
    '      ',
    '      ',
  ];
  $font['J'] = [
    '    o ',
    '    | ',
    '    | ',
    '\\   o ',
    ' o-o  ',
    '      ',
    '      ',
  ];
  $font['K'] = [
    'o  o ',
    '| /  ',
    'OO   ',
    '| \\  ',
    'o  o ',
    '     ',
    '     ',
  ];
  $font['L'] = [
    'o    ',
    '|    ',
    '|    ',
    '|    ',
    'O---o',
    '     ',
    '     ',
  ];
  $font['M'] = [
    'o   o ',
    '|\\ /| ',
    '| O | ',
    '|   | ',
    'o   o ',
    '      ',
    '      ',
  ];
  $font['N'] = [
    'o   o ',
    '|\\  | ',
    '| \\ | ',
    '|  \\| ',
    'o   o ',
    '      ',
    '      ',
  ];
  $font['O'] = [
    ' o-o  ',
    'o   o ',
    '|   | ',
    'o   o ',
    ' o-o  ',
    '      ',
    '      ',
  ];
  $font['P'] = [
    'o--o  ',
    '|   | ',
    'O--o  ',
    '|     ',
    'o     ',
    '      ',
    '      ',
  ];
  $font['Q'] = [
    ' o-o  ',
    'o   o ',
    '|   | ',
    'o   O ',
    ' o-O\\ ',
    '      ',
    '      ',
  ];
  $font['R'] = [
    'o--o  ',
    '|   | ',
    'O-Oo  ',
    '|  \\  ',
    'o   o ',
    '      ',
    '      ',
  ];
  $font['S'] = [
    ' o-o  ',
    '|     ',
    ' o-o  ',
    '    | ',
    'o--o  ',
    '      ',
    '      ',
  ];
  $font['T'] = [
    'o-O-o ',
    '  |   ',
    '  |   ',
    '  |   ',
    '  o   ',
    '      ',
    '      ',
  ];
  $font['U'] = [
    'o   o ',
    '|   | ',
    '|   | ',
    '|   | ',
    ' o-o  ',
    '      ',
    '      ',
  ];
  $font['V'] = [
    'o   o ',
    '|   | ',
    'o   o ',
    ' \\ /  ',
    '  o   ',
    '      ',
    '      ',
  ];
  $font['W'] = [
    'o       o ',
    '|       | ',
    'o   o   o ',
    ' \\ / \\ /  ',
    '  o   o   ',
    '          ',
    '          ',
  ];
  $font['X'] = [
    'o   o ',
    ' \\ /  ',
    '  O   ',
    ' / \\  ',
    'o   o ',
    '      ',
    '      ',
  ];
  $font['Y'] = [
    'o   o ',
    ' \\ /  ',
    '  O   ',
    '  |   ',
    '  o   ',
    '      ',
    '      ',
  ];
  $font['Z'] = [
    'o---o ',
    '   /  ',
    ' -O-  ',
    ' /    ',
    'o---o ',
    '      ',
    '      ',
  ];
  $font['0'] = [
    ' o-o  ',
    'o  /o ',
    '| / | ',
    'o/  o ',
    ' o-o  ',
    '      ',
    '      ',
  ];
  $font['1'] = [
    '  0   ',
    ' /|   ',
    'o |   ',
    '  |   ',
    'o-o-o ',
    '      ',
    '      ',
  ];
  $font['2'] = [
    ' --  ',
    'o  o ',
    '  /  ',
    ' /   ',
    'o--o ',
    '     ',
    '     ',
  ];
  $font['3'] = [
    'o-o  ',
    '   | ',
    ' oo  ',
    '   | ',
    'o-o  ',
    '     ',
    '     ',
  ];
  $font['4'] = [
    'o  o ',
    '|  | ',
    'o--O ',
    '   | ',
    '   o ',
    '     ',
    '     ',
  ];
  $font['5'] = [
    'o--o ',
    '|    ',
    'o-o  ',
    '   | ',
    'o-o  ',
    '     ',
    '     ',
  ];
  $font['6'] = [
    '  o   ',
    ' /    ',
    'O--o  ',
    'o   | ',
    ' o-o  ',
    '      ',
    '      ',
  ];
  $font['7'] = [
    'o---o ',
    '   /  ',
    '  o   ',
    '  |   ',
    '  o   ',
    '      ',
    '      ',
  ];
  $font['8'] = [
    ' o-o  ',
    '|   | ',
    ' o-o  ',
    '|   | ',
    ' o-o  ',
    '      ',
    '      ',
  ];
  $font['9'] = [
    ' o-o  ',
    '|   o ',
    ' o--O ',
    '   /  ',
    '  o   ',
    '      ',
    '      ',
  ];

  return $font;
}
