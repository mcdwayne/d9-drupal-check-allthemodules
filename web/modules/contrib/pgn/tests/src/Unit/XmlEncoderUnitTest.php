<?php

/**
 * @file
 * Contains \Drupal\Tests\pgn\Unit\XmlEncoderUnitTest.
 */

namespace Drupal\Tests\pgn\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\pgn\Serializer\Encoder\XmlEncoder;

/**
 * Tests that XmlEncoder encodes and decodes XML.
 *
 * @group pgn
 */
class XmlEncoderUnitTest extends UnitTestCase {

  protected $encoder;

  public function provider() {
    $data = array();
    $data[0][0] = array(
  'game' =>
  array (
    0 =>
    array (
      'info' =>
      array (
        'Event' => 'Salzburg',
        'Site' => '001: Salzburg',
        'Date' =>
        array (
          '@Year' => '1942',
          '@Month' => '06',
          '@Day' => '06',
          '#' => '',
        ),
        'Round' => '3',
        'White' => 'Alekhine, Alexander',
        'Black' => 'Junge, Klaus',
        'Result' => '0-1',
        'EventDate' =>
        array (
          '@Year' => '1942',
          '@Month' => '06',
          '@Day' => '06',
          '#' => '',
        ),
        'ECO' => 'D31',
        'PlyCount' => '138',
      ),
      'moves' =>
      array (
        'move' =>
        array (
          0 =>
          array (
            '@n' => '0',
            '@piece' => 'P',
            '@from' => 'd2',
            '@to' => 'd4',
            '@display' => 'd4',
            '#' => '',
          ),
          1 =>
          array (
            '@n' => '1',
            '@piece' => 'p',
            '@from' => 'd7',
            '@to' => 'd5',
            '@display' => 'd5',
            '#' => '',
          ),
          2 =>
          array (
            '@n' => '2',
            '@piece' => 'P',
            '@from' => 'c2',
            '@to' => 'c4',
            '@display' => 'c4',
            '#' => '',
          ),
          3 =>
          array (
            '@n' => '3',
            '@piece' => 'p',
            '@from' => 'e7',
            '@to' => 'e6',
            '@display' => 'e6',
            '#' => '',
          ),
          4 =>
          array (
            '@n' => '4',
            '@piece' => 'N',
            '@from' => 'b1',
            '@to' => 'c3',
            '@display' => 'Nc3',
            '#' => '',
          ),
          5 =>
          array (
            '@n' => '5',
            '@piece' => 'p',
            '@from' => 'c7',
            '@to' => 'c6',
            '@display' => 'c6',
            '#' => '',
          ),
          6 =>
          array (
            '@n' => '6',
            '@piece' => 'P',
            '@from' => 'e2',
            '@to' => 'e4',
            '@display' => 'e4',
            '#' => '',
          ),
          7 =>
          array (
            '@n' => '7',
            '@piece' => 'p',
            '@from' => 'd5',
            '@to' => 'e4',
            '@display' => 'dxe4',
            '#' => '',
          ),
          8 =>
          array (
            '@n' => '8',
            '@piece' => 'N',
            '@from' => 'c3',
            '@to' => 'e4',
            '@display' => 'Nxe4',
            '#' => '',
          ),
          9 =>
          array (
            '@n' => '9',
            '@piece' => 'b',
            '@from' => 'f8',
            '@to' => 'b4',
            '@state' => 'check',
            '@display' => 'Bb4+',
            '#' => '',
          ),
          10 =>
          array (
            '@n' => '10',
            '@piece' => 'N',
            '@from' => 'e4',
            '@to' => 'c3',
            '@display' => 'Nc3',
            '#' => '',
          ),
          11 =>
          array (
            '@n' => '11',
            '@piece' => 'p',
            '@from' => 'c6',
            '@to' => 'c5',
            '@display' => 'c5',
            '#' => '',
          ),
          12 =>
          array (
            '@n' => '12',
            '@piece' => 'B',
            '@from' => 'c1',
            '@to' => 'e3',
            '@display' => 'Be3',
            '#' => '',
          ),
          13 =>
          array (
            '@n' => '13',
            '@piece' => 'q',
            '@from' => 'd8',
            '@to' => 'a5',
            '@display' => 'Qa5',
            '#' => '',
          ),
          14 =>
          array (
            '@n' => '14',
            '@piece' => 'N',
            '@from' => 'g1',
            '@to' => 'e2',
            '@display' => 'Ne2',
            '#' => '',
          ),
          15 =>
          array (
            '@n' => '15',
            '@piece' => 'p',
            '@from' => 'c5',
            '@to' => 'd4',
            '@display' => 'cxd4',
            '#' => '',
          ),
          16 =>
          array (
            '@n' => '16',
            '@piece' => 'B',
            '@from' => 'e3',
            '@to' => 'd4',
            '@display' => 'Bxd4',
            '#' => '',
          ),
          17 =>
          array (
            '@n' => '17',
            '@piece' => 'n',
            '@from' => 'g8',
            '@to' => 'f6',
            '@display' => 'Nf6',
            '#' => '',
          ),
          18 =>
          array (
            '@n' => '18',
            '@piece' => 'P',
            '@from' => 'a2',
            '@to' => 'a3',
            '@display' => 'a3',
            '#' => '',
          ),
          19 =>
          array (
            '@n' => '19',
            '@piece' => 'b',
            '@from' => 'b4',
            '@to' => 'e7',
            '@display' => 'Be7',
            '#' => '',
          ),
          20 =>
          array (
            '@n' => '20',
            '@piece' => 'N',
            '@from' => 'e2',
            '@to' => 'g3',
            '@display' => 'Ng3',
            '#' => '',
          ),
          21 =>
          array (
            '@n' => '21',
            '@piece' => 'n',
            '@from' => 'b8',
            '@to' => 'c6',
            '@display' => 'Nc6',
            '#' => '',
          ),
          22 =>
          array (
            '@n' => '22',
            '@piece' => 'P',
            '@from' => 'b2',
            '@to' => 'b4',
            '@display' => 'b4',
            '#' => '',
          ),
          23 =>
          array (
            '@n' => '23',
            '@piece' => 'q',
            '@from' => 'a5',
            '@to' => 'c7',
            '@display' => 'Qc7',
            '#' => '',
          ),
          24 =>
          array (
            '@n' => '24',
            '@piece' => 'B',
            '@from' => 'd4',
            '@to' => 'e3',
            '@display' => 'Be3',
            '#' => '',
          ),
          25 =>
          array (
            '@n' => '25',
            '@piece' => 'k',
            '@from' => 'e8',
            '@to' => 'g8',
            '@display' => 'O-O',
            '#' => '',
          ),
          26 =>
          array (
            '@n' => '26',
            '@piece' => 'B',
            '@from' => 'f1',
            '@to' => 'e2',
            '@display' => 'Be2',
            '#' => '',
          ),
          27 =>
          array (
            '@n' => '27',
            '@piece' => 'p',
            '@from' => 'b7',
            '@to' => 'b6',
            '@display' => 'b6',
            '#' => '',
          ),
          28 =>
          array (
            '@n' => '28',
            '@piece' => 'K',
            '@from' => 'e1',
            '@to' => 'g1',
            '@display' => 'O-O',
            '#' => '',
          ),
          29 =>
          array (
            '@n' => '29',
            '@piece' => 'b',
            '@from' => 'c8',
            '@to' => 'b7',
            '@display' => 'Bb7',
            '#' => '',
          ),
          30 =>
          array (
            '@n' => '30',
            '@piece' => 'N',
            '@from' => 'c3',
            '@to' => 'b5',
            '@display' => 'Nb5',
            '#' => '',
          ),
          31 =>
          array (
            '@n' => '31',
            '@piece' => 'q',
            '@from' => 'c7',
            '@to' => 'b8',
            '@display' => 'Qb8',
            '#' => '',
          ),
          32 =>
          array (
            '@n' => '32',
            '@piece' => 'Q',
            '@from' => 'd1',
            '@to' => 'c1',
            '@display' => 'Qc1',
            '#' => '',
          ),
          33 =>
          array (
            '@n' => '33',
            '@piece' => 'p',
            '@from' => 'a7',
            '@to' => 'a6',
            '@display' => 'a6',
            '#' => '',
          ),
          34 =>
          array (
            '@n' => '34',
            '@piece' => 'N',
            '@from' => 'b5',
            '@to' => 'c3',
            '@display' => 'Nc3',
            '#' => '',
          ),
          35 =>
          array (
            '@n' => '35',
            '@piece' => 'q',
            '@from' => 'b8',
            '@to' => 'c7',
            '@display' => 'Qc7',
            '#' => '',
          ),
          36 =>
          array (
            '@n' => '36',
            '@piece' => 'N',
            '@from' => 'c3',
            '@to' => 'a4',
            '@display' => 'Na4',
            '#' => '',
          ),
          37 =>
          array (
            '@n' => '37',
            '@piece' => 'n',
            '@from' => 'f6',
            '@to' => 'd7',
            '@display' => 'Nd7',
            '#' => '',
          ),
          38 =>
          array (
            '@n' => '38',
            '@piece' => 'R',
            '@from' => 'f1',
            '@to' => 'd1',
            '@display' => 'Rd1',
            '#' => '',
          ),
          39 =>
          array (
            '@n' => '39',
            '@piece' => 'n',
            '@from' => 'c6',
            '@to' => 'e5',
            '@display' => 'Nce5',
            '#' => '',
          ),
          40 =>
          array (
            '@n' => '40',
            '@piece' => 'P',
            '@from' => 'f2',
            '@to' => 'f3',
            '@display' => 'f3',
            '#' => '',
          ),
          41 =>
          array (
            '@n' => '41',
            '@piece' => 'p',
            '@from' => 'a6',
            '@to' => 'a5',
            '@display' => 'a5',
            '#' => '',
          ),
          42 =>
          array (
            '@n' => '42',
            '@piece' => 'Q',
            '@from' => 'c1',
            '@to' => 'b2',
            '@display' => 'Qb2',
            '#' => '',
          ),
          43 =>
          array (
            '@n' => '43',
            '@piece' => 'p',
            '@from' => 'a5',
            '@to' => 'b4',
            '@display' => 'axb4',
            '#' => '',
          ),
          44 =>
          array (
            '@n' => '44',
            '@piece' => 'P',
            '@from' => 'a3',
            '@to' => 'b4',
            '@display' => 'axb4',
            '#' => '',
          ),
          45 =>
          array (
            '@n' => '45',
            '@piece' => 'b',
            '@from' => 'e7',
            '@to' => 'f6',
            '@display' => 'Bf6',
            '#' => '',
          ),
          46 =>
          array (
            '@n' => '46',
            '@piece' => 'Q',
            '@from' => 'b2',
            '@to' => 'b3',
            '@display' => 'Qb3',
            '#' => '',
          ),
          47 =>
          array (
            '@n' => '47',
            '@piece' => 'p',
            '@from' => 'b6',
            '@to' => 'b5',
            '@display' => 'b5',
            '#' => '',
          ),
          48 =>
          array (
            '@n' => '48',
            '@piece' => 'P',
            '@from' => 'c4',
            '@to' => 'b5',
            '@display' => 'cxb5',
            '#' => '',
          ),
          49 =>
          array (
            '@n' => '49',
            '@piece' => 'b',
            '@from' => 'b7',
            '@to' => 'd5',
            '@display' => 'Bd5',
            '#' => '',
          ),
          50 =>
          array (
            '@n' => '50',
            '@piece' => 'R',
            '@from' => 'd1',
            '@to' => 'd5',
            '@display' => 'Rxd5',
            '#' => '',
          ),
          51 =>
          array (
            '@n' => '51',
            '@piece' => 'p',
            '@from' => 'e6',
            '@to' => 'd5',
            '@display' => 'exd5',
            '#' => '',
          ),
          52 =>
          array (
            '@n' => '52',
            '@piece' => 'R',
            '@from' => 'a1',
            '@to' => 'c1',
            '@display' => 'Rc1',
            '#' => '',
          ),
          53 =>
          array (
            '@n' => '53',
            '@piece' => 'n',
            '@from' => 'e5',
            '@to' => 'c4',
            '@display' => 'Nc4',
            '#' => '',
          ),
          54 =>
          array (
            '@n' => '54',
            '@piece' => 'B',
            '@from' => 'e2',
            '@to' => 'c4',
            '@display' => 'Bxc4',
            '#' => '',
          ),
          55 =>
          array (
            '@n' => '55',
            '@piece' => 'p',
            '@from' => 'd5',
            '@to' => 'c4',
            '@display' => 'dxc4',
            '#' => '',
          ),
          56 =>
          array (
            '@n' => '56',
            '@piece' => 'R',
            '@from' => 'c1',
            '@to' => 'c4',
            '@display' => 'Rxc4',
            '#' => '',
          ),
          57 =>
          array (
            '@n' => '57',
            '@piece' => 'q',
            '@from' => 'c7',
            '@to' => 'e5',
            '@display' => 'Qe5',
            '#' => '',
          ),
          58 =>
          array (
            '@n' => '58',
            '@piece' => 'N',
            '@from' => 'a4',
            '@to' => 'c5',
            '@display' => 'Nc5',
            '#' => '',
          ),
          59 =>
          array (
            '@n' => '59',
            '@piece' => 'n',
            '@from' => 'd7',
            '@to' => 'b6',
            '@display' => 'Nb6',
            '#' => '',
          ),
          60 =>
          array (
            '@n' => '60',
            '@piece' => 'R',
            '@from' => 'c4',
            '@to' => 'c1',
            '@display' => 'Rc1',
            '#' => '',
          ),
          61 =>
          array (
            '@n' => '61',
            '@piece' => 'n',
            '@from' => 'b6',
            '@to' => 'd5',
            '@display' => 'Nd5',
            '#' => '',
          ),
          62 =>
          array (
            '@n' => '62',
            '@piece' => 'N',
            '@from' => 'g3',
            '@to' => 'e4',
            '@display' => 'Nge4',
            '#' => '',
          ),
          63 =>
          array (
            '@n' => '63',
            '@piece' => 'n',
            '@from' => 'd5',
            '@to' => 'e3',
            '@display' => 'Nxe3',
            '#' => '',
          ),
          64 =>
          array (
            '@n' => '64',
            '@piece' => 'Q',
            '@from' => 'b3',
            '@to' => 'e3',
            '@display' => 'Qxe3',
            '#' => '',
          ),
          65 =>
          array (
            '@n' => '65',
            '@piece' => 'r',
            '@from' => 'a8',
            '@to' => 'a1',
            '@display' => 'Ra1',
            '#' => '',
          ),
          66 =>
          array (
            '@n' => '66',
            '@piece' => 'R',
            '@from' => 'c1',
            '@to' => 'f1',
            '@display' => 'Rf1',
            '#' => '',
          ),
          67 =>
          array (
            '@n' => '67',
            '@piece' => 'r',
            '@from' => 'f8',
            '@to' => 'd8',
            '@display' => 'Rd8',
            '#' => '',
          ),
          68 =>
          array (
            '@n' => '68',
            '@piece' => 'N',
            '@from' => 'e4',
            '@to' => 'f6',
            '@state' => 'check',
            '@display' => 'Nxf6+',
            '#' => '',
          ),
          69 =>
          array (
            '@n' => '69',
            '@piece' => 'q',
            '@from' => 'e5',
            '@to' => 'f6',
            '@display' => 'Qxf6',
            '#' => '',
          ),
          70 =>
          array (
            '@n' => '70',
            '@piece' => 'P',
            '@from' => 'b5',
            '@to' => 'b6',
            '@display' => 'b6',
            '#' => '',
          ),
          71 =>
          array (
            '@n' => '71',
            '@piece' => 'r',
            '@from' => 'a1',
            '@to' => 'f1',
            '@state' => 'check',
            '@display' => 'Rxf1+',
            '#' => '',
          ),
          72 =>
          array (
            '@n' => '72',
            '@piece' => 'K',
            '@from' => 'g1',
            '@to' => 'f1',
            '@display' => 'Kxf1',
            '#' => '',
          ),
          73 =>
          array (
            '@n' => '73',
            '@piece' => 'q',
            '@from' => 'f6',
            '@to' => 'b6',
            '@display' => 'Qxb6',
            '#' => '',
          ),
          74 =>
          array (
            '@n' => '74',
            '@piece' => 'Q',
            '@from' => 'e3',
            '@to' => 'e4',
            '@display' => 'Qe4',
            '#' => '',
          ),
          75 =>
          array (
            '@n' => '75',
            '@piece' => 'q',
            '@from' => 'b6',
            '@to' => 'b5',
            '@state' => 'check',
            '@display' => 'Qb5+',
            '#' => '',
          ),
          76 =>
          array (
            '@n' => '76',
            '@piece' => 'K',
            '@from' => 'f1',
            '@to' => 'f2',
            '@display' => 'Kf2',
            '#' => '',
          ),
          77 =>
          array (
            '@n' => '77',
            '@piece' => 'r',
            '@from' => 'd8',
            '@to' => 'e8',
            '@display' => 'Re8',
            '#' => '',
          ),
          78 =>
          array (
            '@n' => '78',
            '@piece' => 'Q',
            '@from' => 'e4',
            '@to' => 'd4',
            '@display' => 'Qd4',
            '#' => '',
          ),
          79 =>
          array (
            '@n' => '79',
            '@piece' => 'q',
            '@from' => 'b5',
            '@to' => 'b6',
            '@display' => 'Qb6',
            '#' => '',
          ),
          80 =>
          array (
            '@n' => '80',
            '@piece' => 'N',
            '@from' => 'c5',
            '@to' => 'b3',
            '@display' => 'Nb3',
            '#' => '',
          ),
          81 =>
          array (
            '@n' => '81',
            '@piece' => 'r',
            '@from' => 'e8',
            '@to' => 'b8',
            '@display' => 'Rb8',
            '#' => '',
          ),
          82 =>
          array (
            '@n' => '82',
            '@piece' => 'Q',
            '@from' => 'd4',
            '@to' => 'b6',
            '@display' => 'Qxb6',
            '#' => '',
          ),
          83 =>
          array (
            '@n' => '83',
            '@piece' => 'r',
            '@from' => 'b8',
            '@to' => 'b6',
            '@display' => 'Rxb6',
            '#' => '',
          ),
          84 =>
          array (
            '@n' => '84',
            '@piece' => 'P',
            '@from' => 'g2',
            '@to' => 'g4',
            '@display' => 'g4',
            '#' => '',
          ),
          85 =>
          array (
            '@n' => '85',
            '@piece' => 'r',
            '@from' => 'b6',
            '@to' => 'b4',
            '@display' => 'Rxb4',
            '#' => '',
          ),
          86 =>
          array (
            '@n' => '86',
            '@piece' => 'N',
            '@from' => 'b3',
            '@to' => 'c5',
            '@display' => 'Nc5',
            '#' => '',
          ),
          87 =>
          array (
            '@n' => '87',
            '@piece' => 'p',
            '@from' => 'f7',
            '@to' => 'f6',
            '@display' => 'f6',
            '#' => '',
          ),
          88 =>
          array (
            '@n' => '88',
            '@piece' => 'K',
            '@from' => 'f2',
            '@to' => 'g3',
            '@display' => 'Kg3',
            '#' => '',
          ),
          89 =>
          array (
            '@n' => '89',
            '@piece' => 'k',
            '@from' => 'g8',
            '@to' => 'f7',
            '@display' => 'Kf7',
            '#' => '',
          ),
          90 =>
          array (
            '@n' => '90',
            '@piece' => 'N',
            '@from' => 'c5',
            '@to' => 'd3',
            '@display' => 'Nd3',
            '#' => '',
          ),
          91 =>
          array (
            '@n' => '91',
            '@piece' => 'r',
            '@from' => 'b4',
            '@to' => 'd4',
            '@display' => 'Rd4',
            '#' => '',
          ),
          92 =>
          array (
            '@n' => '92',
            '@piece' => 'N',
            '@from' => 'd3',
            '@to' => 'f4',
            '@display' => 'Nf4',
            '#' => '',
          ),
          93 =>
          array (
            '@n' => '93',
            '@piece' => 'r',
            '@from' => 'd4',
            '@to' => 'c4',
            '@display' => 'Rc4',
            '#' => '',
          ),
          94 =>
          array (
            '@n' => '94',
            '@piece' => 'P',
            '@from' => 'h2',
            '@to' => 'h4',
            '@display' => 'h4',
            '#' => '',
          ),
          95 =>
          array (
            '@n' => '95',
            '@piece' => 'r',
            '@from' => 'c4',
            '@to' => 'c5',
            '@display' => 'Rc5',
            '#' => '',
          ),
          96 =>
          array (
            '@n' => '96',
            '@piece' => 'N',
            '@from' => 'f4',
            '@to' => 'h5',
            '@display' => 'Nh5',
            '#' => '',
          ),
          97 =>
          array (
            '@n' => '97',
            '@piece' => 'p',
            '@from' => 'g7',
            '@to' => 'g6',
            '@display' => 'g6',
            '#' => '',
          ),
          98 =>
          array (
            '@n' => '98',
            '@piece' => 'N',
            '@from' => 'h5',
            '@to' => 'f4',
            '@display' => 'Nf4',
            '#' => '',
          ),
          99 =>
          array (
            '@n' => '99',
            '@piece' => 'k',
            '@from' => 'f7',
            '@to' => 'e7',
            '@display' => 'Ke7',
            '#' => '',
          ),
          100 =>
          array (
            '@n' => '100',
            '@piece' => 'P',
            '@from' => 'h4',
            '@to' => 'h5',
            '@display' => 'h5',
            '#' => '',
          ),
          101 =>
          array (
            '@n' => '101',
            '@piece' => 'p',
            '@from' => 'g6',
            '@to' => 'g5',
            '@display' => 'g5',
            '#' => '',
          ),
          102 =>
          array (
            '@n' => '102',
            '@piece' => 'N',
            '@from' => 'f4',
            '@to' => 'e2',
            '@display' => 'Ne2',
            '#' => '',
          ),
          103 =>
          array (
            '@n' => '103',
            '@piece' => 'r',
            '@from' => 'c5',
            '@to' => 'c4',
            '@display' => 'Rc4',
            '#' => '',
          ),
          104 =>
          array (
            '@n' => '104',
            '@piece' => 'K',
            '@from' => 'g3',
            '@to' => 'f2',
            '@display' => 'Kf2',
            '#' => '',
          ),
          105 =>
          array (
            '@n' => '105',
            '@piece' => 'k',
            '@from' => 'e7',
            '@to' => 'e6',
            '@display' => 'Ke6',
            '#' => '',
          ),
          106 =>
          array (
            '@n' => '106',
            '@piece' => 'N',
            '@from' => 'e2',
            '@to' => 'g3',
            '@display' => 'Ng3',
            '#' => '',
          ),
          107 =>
          array (
            '@n' => '107',
            '@piece' => 'k',
            '@from' => 'e6',
            '@to' => 'e5',
            '@display' => 'Ke5',
            '#' => '',
          ),
          108 =>
          array (
            '@n' => '108',
            '@piece' => 'N',
            '@from' => 'g3',
            '@to' => 'f5',
            '@display' => 'Nf5',
            '#' => '',
          ),
          109 =>
          array (
            '@n' => '109',
            '@piece' => 'k',
            '@from' => 'e5',
            '@to' => 'f4',
            '@display' => 'Kf4',
            '#' => '',
          ),
          110 =>
          array (
            '@n' => '110',
            '@piece' => 'N',
            '@from' => 'f5',
            '@to' => 'e3',
            '@display' => 'Ne3',
            '#' => '',
          ),
          111 =>
          array (
            '@n' => '111',
            '@piece' => 'r',
            '@from' => 'c4',
            '@to' => 'c5',
            '@display' => 'Rc5',
            '#' => '',
          ),
          112 =>
          array (
            '@n' => '112',
            '@piece' => 'N',
            '@from' => 'e3',
            '@to' => 'g2',
            '@state' => 'check',
            '@display' => 'Ng2+',
            '#' => '',
          ),
          113 =>
          array (
            '@n' => '113',
            '@piece' => 'k',
            '@from' => 'f4',
            '@to' => 'e5',
            '@display' => 'Ke5',
            '#' => '',
          ),
          114 =>
          array (
            '@n' => '114',
            '@piece' => 'N',
            '@from' => 'g2',
            '@to' => 'e3',
            '@display' => 'Ne3',
            '#' => '',
          ),
          115 =>
          array (
            '@n' => '115',
            '@piece' => 'k',
            '@from' => 'e5',
            '@to' => 'd4',
            '@display' => 'Kd4',
            '#' => '',
          ),
          116 =>
          array (
            '@n' => '116',
            '@piece' => 'N',
            '@from' => 'e3',
            '@to' => 'd1',
            '@display' => 'Nd1',
            '#' => '',
          ),
          117 =>
          array (
            '@n' => '117',
            '@piece' => 'r',
            '@from' => 'c5',
            '@to' => 'c1',
            '@display' => 'Rc1',
            '#' => '',
          ),
          118 =>
          array (
            '@n' => '118',
            '@piece' => 'N',
            '@from' => 'd1',
            '@to' => 'e3',
            '@display' => 'Ne3',
            '#' => '',
          ),
          119 =>
          array (
            '@n' => '119',
            '@piece' => 'r',
            '@from' => 'c1',
            '@to' => 'c5',
            '@display' => 'Rc5',
            '#' => '',
          ),
          120 =>
          array (
            '@n' => '120',
            '@piece' => 'N',
            '@from' => 'e3',
            '@to' => 'd1',
            '@display' => 'Nd1',
            '#' => '',
          ),
          121 =>
          array (
            '@n' => '121',
            '@piece' => 'k',
            '@from' => 'd4',
            '@to' => 'd3',
            '@display' => 'Kd3',
            '#' => '',
          ),
          122 =>
          array (
            '@n' => '122',
            '@piece' => 'N',
            '@from' => 'd1',
            '@to' => 'e3',
            '@display' => 'Ne3',
            '#' => '',
          ),
          123 =>
          array (
            '@n' => '123',
            '@piece' => 'r',
            '@from' => 'c5',
            '@to' => 'e5',
            '@display' => 'Re5',
            '#' => '',
          ),
          124 =>
          array (
            '@n' => '124',
            '@piece' => 'N',
            '@from' => 'e3',
            '@to' => 'f1',
            '@display' => 'Nf1',
            '#' => '',
          ),
          125 =>
          array (
            '@n' => '125',
            '@piece' => 'r',
            '@from' => 'e5',
            '@to' => 'e2',
            '@state' => 'check',
            '@display' => 'Re2+',
            '#' => '',
          ),
          126 =>
          array (
            '@n' => '126',
            '@piece' => 'K',
            '@from' => 'f2',
            '@to' => 'g1',
            '@display' => 'Kg1',
            '#' => '',
          ),
          127 =>
          array (
            '@n' => '127',
            '@piece' => 'r',
            '@from' => 'e2',
            '@to' => 'a2',
            '@display' => 'Ra2',
            '#' => '',
          ),
          128 =>
          array (
            '@n' => '128',
            '@piece' => 'P',
            '@from' => 'h5',
            '@to' => 'h6',
            '@display' => 'h6',
            '#' => '',
          ),
          129 =>
          array (
            '@n' => '129',
            '@piece' => 'k',
            '@from' => 'd3',
            '@to' => 'e2',
            '@display' => 'Ke2',
            '#' => '',
          ),
          130 =>
          array (
            '@n' => '130',
            '@piece' => 'K',
            '@from' => 'g1',
            '@to' => 'g2',
            '@display' => 'Kg2',
            '#' => '',
          ),
          131 =>
          array (
            '@n' => '131',
            '@piece' => 'r',
            '@from' => 'a2',
            '@to' => 'b2',
            '@display' => 'Rb2',
            '#' => '',
          ),
          132 =>
          array (
            '@n' => '132',
            '@piece' => 'N',
            '@from' => 'f1',
            '@to' => 'g3',
            '@state' => 'check',
            '@display' => 'Ng3+',
            '#' => '',
          ),
          133 =>
          array (
            '@n' => '133',
            '@piece' => 'k',
            '@from' => 'e2',
            '@to' => 'e3',
            '@state' => 'check',
            '@display' => 'Ke3+',
            '#' => '',
          ),
          134 =>
          array (
            '@n' => '134',
            '@piece' => 'K',
            '@from' => 'g2',
            '@to' => 'h3',
            '@display' => 'Kh3',
            '#' => '',
          ),
          135 =>
          array (
            '@n' => '135',
            '@piece' => 'k',
            '@from' => 'e3',
            '@to' => 'f3',
            '@display' => 'Kxf3',
            '#' => '',
          ),
          136 =>
          array (
            '@n' => '136',
            '@piece' => 'N',
            '@from' => 'g3',
            '@to' => 'h5',
            '@display' => 'Nh5',
            '#' => '',
          ),
          137 =>
          array (
            '@n' => '137',
            '@piece' => 'r',
            '@from' => 'b2',
            '@to' => 'b6',
            '@display' => 'Rb6',
            '#' => '',
          ),
        ),
        'end' => '0-1',
      ),
    ),
    1 =>
    array (
      'info' =>
      array (
        'Site' => '002: Leipzig',
        'Date' =>
        array (
          '@Year' => '1942',
          '#' => '',
        ),
        'White' => 'Mueller, Dr',
        'Black' => 'Junge, Klaus',
        'Result' => '0-1',
        'EventDate' =>
        array (
          '@Year' => '1942',
          '@Month' => '11',
          '@Day' => '11',
          '#' => '',
        ),
        'ECO' => 'B84',
      ),
      'moves' =>
      array (
        'move' =>
        array (
          0 =>
          array (
            '@n' => '0',
            '@piece' => 'P',
            '@from' => 'e2',
            '@to' => 'e4',
            '@display' => 'e4',
            '#' => '',
          ),
          1 =>
          array (
            '@n' => '1',
            '@piece' => 'p',
            '@from' => 'c7',
            '@to' => 'c5',
            '@display' => 'c5',
            '#' => '',
          ),
          2 =>
          array (
            '@n' => '2',
            '@piece' => 'N',
            '@from' => 'g1',
            '@to' => 'f3',
            '@display' => 'Nf3',
            '#' => '',
          ),
          3 =>
          array (
            '@n' => '3',
            '@piece' => 'p',
            '@from' => 'e7',
            '@to' => 'e6',
            '@display' => 'e6',
            '#' => '',
          ),
          4 =>
          array (
            '@n' => '4',
            '@piece' => 'N',
            '@from' => 'b1',
            '@to' => 'c3',
            '@display' => 'Nc3',
            '#' => '',
          ),
          5 =>
          array (
            '@n' => '5',
            '@piece' => 'p',
            '@from' => 'a7',
            '@to' => 'a6',
            '@display' => 'a6',
            '#' => '',
          ),
          6 =>
          array (
            '@n' => '6',
            '@piece' => 'P',
            '@from' => 'd2',
            '@to' => 'd4',
            '@display' => 'd4',
            '#' => '',
          ),
          7 =>
          array (
            '@n' => '7',
            '@piece' => 'p',
            '@from' => 'c5',
            '@to' => 'd4',
            '@display' => 'cxd4',
            '#' => '',
          ),
          8 =>
          array (
            '@n' => '8',
            '@piece' => 'N',
            '@from' => 'f3',
            '@to' => 'd4',
            '@display' => 'Nxd4',
            '#' => '',
          ),
          9 =>
          array (
            '@n' => '9',
            '@piece' => 'p',
            '@from' => 'd7',
            '@to' => 'd6',
            '@display' => 'd6',
            '#' => '',
          ),
          10 =>
          array (
            '@n' => '10',
            '@piece' => 'B',
            '@from' => 'f1',
            '@to' => 'e2',
            '@display' => 'Be2',
            '#' => '',
          ),
          11 =>
          array (
            '@n' => '11',
            '@piece' => 'n',
            '@from' => 'g8',
            '@to' => 'f6',
            '@display' => 'Nf6',
            '#' => '',
          ),
          12 =>
          array (
            '@n' => '12',
            '@piece' => 'B',
            '@from' => 'c1',
            '@to' => 'e3',
            '@display' => 'Be3',
            '#' => '',
          ),
          13 =>
          array (
            '@n' => '13',
            '@piece' => 'n',
            '@from' => 'b8',
            '@to' => 'd7',
            '@display' => 'Nbd7',
            '#' => '',
          ),
          14 =>
          array (
            '@n' => '14',
            '@piece' => 'K',
            '@from' => 'e1',
            '@to' => 'g1',
            '@display' => 'O-O',
            '#' => '',
          ),
          15 =>
          array (
            '@n' => '15',
            '@piece' => 'p',
            '@from' => 'b7',
            '@to' => 'b5',
            '@display' => 'b5',
            '#' => '',
          ),
          16 =>
          array (
            '@n' => '16',
            '@piece' => 'P',
            '@from' => 'a2',
            '@to' => 'a3',
            '@display' => 'a3',
            '#' => '',
          ),
          17 =>
          array (
            '@n' => '17',
            '@piece' => 'b',
            '@from' => 'c8',
            '@to' => 'b7',
            '@display' => 'Bb7',
            '#' => '',
          ),
          18 =>
          array (
            '@n' => '18',
            '@piece' => 'P',
            '@from' => 'f2',
            '@to' => 'f3',
            '@display' => 'f3',
            '#' => '',
          ),
          19 =>
          array (
            '@n' => '19',
            '@piece' => 'n',
            '@from' => 'd7',
            '@to' => 'b6',
            '@display' => 'Nb6',
            '#' => '',
          ),
          20 =>
          array (
            '@n' => '20',
            '@piece' => 'Q',
            '@from' => 'd1',
            '@to' => 'e1',
            '@display' => 'Qe1',
            '#' => '',
          ),
          21 =>
          array (
            '@n' => '21',
            '@piece' => 'b',
            '@from' => 'f8',
            '@to' => 'e7',
            '@display' => 'Be7',
            '#' => '',
          ),
          22 =>
          array (
            '@n' => '22',
            '@piece' => 'Q',
            '@from' => 'e1',
            '@to' => 'f2',
            '@display' => 'Qf2',
            '#' => '',
          ),
          23 =>
          array (
            '@n' => '23',
            '@piece' => 'n',
            '@from' => 'b6',
            '@to' => 'c4',
            '@display' => 'Nc4',
            '#' => '',
          ),
          24 =>
          array (
            '@n' => '24',
            '@piece' => 'B',
            '@from' => 'e2',
            '@to' => 'c4',
            '@display' => 'Bxc4',
            '#' => '',
          ),
          25 =>
          array (
            '@n' => '25',
            '@piece' => 'p',
            '@from' => 'b5',
            '@to' => 'c4',
            '@display' => 'bxc4',
            '#' => '',
          ),
          26 =>
          array (
            '@n' => '26',
            '@piece' => 'R',
            '@from' => 'f1',
            '@to' => 'd1',
            '@display' => 'Rfd1',
            '#' => '',
          ),
          27 =>
          array (
            '@n' => '27',
            '@piece' => 'q',
            '@from' => 'd8',
            '@to' => 'c7',
            '@display' => 'Qc7',
            '#' => '',
          ),
          28 =>
          array (
            '@n' => '28',
            '@piece' => 'R',
            '@from' => 'a1',
            '@to' => 'b1',
            '@display' => 'Rab1',
            '#' => '',
          ),
          29 =>
          array (
            '@n' => '29',
            '@piece' => 'k',
            '@from' => 'e8',
            '@to' => 'g8',
            '@display' => 'O-O',
            '#' => '',
          ),
          30 =>
          array (
            '@n' => '30',
            '@piece' => 'K',
            '@from' => 'g1',
            '@to' => 'h1',
            '@display' => 'Kh1',
            '#' => '',
          ),
          31 =>
          array (
            '@n' => '31',
            '@piece' => 'p',
            '@from' => 'd6',
            '@to' => 'd5',
            '@display' => 'd5',
            '#' => '',
          ),
          32 =>
          array (
            '@n' => '32',
            '@piece' => 'P',
            '@from' => 'e4',
            '@to' => 'd5',
            '@display' => 'exd5',
            '#' => '',
          ),
          33 =>
          array (
            '@n' => '33',
            '@piece' => 'n',
            '@from' => 'f6',
            '@to' => 'd5',
            '@display' => 'Nxd5',
            '#' => '',
          ),
          34 =>
          array (
            '@n' => '34',
            '@piece' => 'N',
            '@from' => 'c3',
            '@to' => 'd5',
            '@display' => 'Nxd5',
            '#' => '',
          ),
          35 =>
          array (
            '@n' => '35',
            '@piece' => 'b',
            '@from' => 'b7',
            '@to' => 'd5',
            '@display' => 'Bxd5',
            '#' => '',
          ),
          36 =>
          array (
            '@n' => '36',
            '@piece' => 'P',
            '@from' => 'c2',
            '@to' => 'c3',
            '@display' => 'c3',
            '#' => '',
          ),
          37 =>
          array (
            '@n' => '37',
            '@piece' => 'r',
            '@from' => 'f8',
            '@to' => 'd8',
            '@display' => 'Rfd8',
            '#' => '',
          ),
          38 =>
          array (
            '@n' => '38',
            '@piece' => 'N',
            '@from' => 'd4',
            '@to' => 'e2',
            '@display' => 'Ne2',
            '#' => '',
          ),
          39 =>
          array (
            '@n' => '39',
            '@piece' => 'r',
            '@from' => 'a8',
            '@to' => 'b8',
            '@display' => 'Rab8',
            '#' => '',
          ),
          40 =>
          array (
            '@n' => '40',
            '@piece' => 'B',
            '@from' => 'e3',
            '@to' => 'f4',
            '@display' => 'Bf4',
            '#' => '',
          ),
          41 =>
          array (
            '@n' => '41',
            '@piece' => 'p',
            '@from' => 'e6',
            '@to' => 'e5',
            '@display' => 'e5',
            '#' => '',
          ),
          42 =>
          array (
            '@n' => '42',
            '@piece' => 'B',
            '@from' => 'f4',
            '@to' => 'e3',
            '@display' => 'Be3',
            '#' => '',
          ),
          43 =>
          array (
            '@n' => '43',
            '@piece' => 'b',
            '@from' => 'd5',
            '@to' => 'e6',
            '@display' => 'Be6',
            '#' => '',
          ),
          44 =>
          array (
            '@n' => '44',
            '@piece' => 'R',
            '@from' => 'd1',
            '@to' => 'd8',
            '@state' => 'check',
            '@display' => 'Rxd8+',
            '#' => '',
          ),
          45 =>
          array (
            '@n' => '45',
            '@piece' => 'q',
            '@from' => 'c7',
            '@to' => 'd8',
            '@display' => 'Qxd8',
            '#' => '',
          ),
          46 =>
          array (
            '@n' => '46',
            '@piece' => 'Q',
            '@from' => 'f2',
            '@to' => 'e1',
            '@display' => 'Qe1',
            '#' => '',
          ),
          47 =>
          array (
            '@n' => '47',
            '@piece' => 'q',
            '@from' => 'd8',
            '@to' => 'd3',
            '@display' => 'Qd3',
            '#' => '',
          ),
          48 =>
          array (
            '@n' => '48',
            '@piece' => 'N',
            '@from' => 'e2',
            '@to' => 'g1',
            '@display' => 'Ng1',
            '#' => '',
          ),
          49 =>
          array (
            '@n' => '49',
            '@piece' => 'q',
            '@from' => 'd3',
            '@to' => 'c2',
            '@display' => 'Qc2',
            '#' => '',
          ),
          50 =>
          array (
            '@n' => '50',
            '@piece' => 'P',
            '@from' => 'g2',
            '@to' => 'g4',
            '@display' => 'g4',
            '#' => '',
          ),
          51 =>
          array (
            '@n' => '51',
            '@piece' => 'b',
            '@from' => 'e7',
            '@to' => 'a3',
            '@display' => 'Bxa3',
            '#' => '',
          ),
          52 =>
          array (
            '@n' => '52',
            '@piece' => 'B',
            '@from' => 'e3',
            '@to' => 'a7',
            '@display' => 'Ba7',
            '#' => '',
          ),
          53 =>
          array (
            '@n' => '53',
            '@piece' => 'r',
            '@from' => 'b8',
            '@to' => 'b7',
            '@display' => 'Rb7',
            '#' => '',
          ),
          54 =>
          array (
            '@n' => '54',
            '@piece' => 'R',
            '@from' => 'b1',
            '@to' => 'd1',
            '@display' => 'Rd1',
            '#' => '',
          ),
          55 =>
          array (
            '@n' => '55',
            '@piece' => 'b',
            '@from' => 'a3',
            '@to' => 'e7',
            '@display' => 'Be7',
            '#' => '',
          ),
          56 =>
          array (
            '@n' => '56',
            '@piece' => 'R',
            '@from' => 'd1',
            '@to' => 'd2',
            '@display' => 'Rd2',
            '#' => '',
          ),
          57 =>
          array (
            '@n' => '57',
            '@piece' => 'q',
            '@from' => 'c2',
            '@to' => 'b3',
            '@display' => 'Qb3',
            '#' => '',
          ),
          58 =>
          array (
            '@n' => '58',
            '@piece' => 'Q',
            '@from' => 'e1',
            '@to' => 'e3',
            '@display' => 'Qe3',
            '#' => '',
          ),
          59 =>
          array (
            '@n' => '59',
            '@piece' => 'q',
            '@from' => 'b3',
            '@to' => 'b5',
            '@display' => 'Qb5',
            '#' => '',
          ),
          60 =>
          array (
            '@n' => '60',
            '@piece' => 'Q',
            '@from' => 'e3',
            '@to' => 'f2',
            '@display' => 'Qf2',
            '#' => '',
          ),
          61 =>
          array (
            '@n' => '61',
            '@piece' => 'q',
            '@from' => 'b5',
            '@to' => 'c6',
            '@display' => 'Qc6',
            '#' => '',
          ),
          62 =>
          array (
            '@n' => '62',
            '@piece' => 'P',
            '@from' => 'h2',
            '@to' => 'h3',
            '@display' => 'h3',
            '#' => '',
          ),
          63 =>
          array (
            '@n' => '63',
            '@piece' => 'p',
            '@from' => 'h7',
            '@to' => 'h5',
            '@display' => 'h5',
            '#' => '',
          ),
          64 =>
          array (
            '@n' => '64',
            '@piece' => 'P',
            '@from' => 'g4',
            '@to' => 'h5',
            '@display' => 'gxh5',
            '#' => '',
          ),
          65 =>
          array (
            '@n' => '65',
            '@piece' => 'p',
            '@from' => 'e5',
            '@to' => 'e4',
            '@display' => 'e4',
            '#' => '',
          ),
          66 =>
          array (
            '@n' => '66',
            '@piece' => 'B',
            '@from' => 'a7',
            '@to' => 'd4',
            '@display' => 'Bd4',
            '#' => '',
          ),
          67 =>
          array (
            '@n' => '67',
            '@piece' => 'r',
            '@from' => 'b7',
            '@to' => 'b5',
            '@display' => 'Rb5',
            '#' => '',
          ),
          68 =>
          array (
            '@n' => '68',
            '@piece' => 'P',
            '@from' => 'h5',
            '@to' => 'h6',
            '@display' => 'h6',
            '#' => '',
          ),
          69 =>
          array (
            '@n' => '69',
            '@piece' => 'p',
            '@from' => 'e4',
            '@to' => 'f3',
            '@display' => 'exf3',
            '#' => '',
          ),
          70 =>
          array (
            '@n' => '70',
            '@piece' => 'P',
            '@from' => 'h6',
            '@to' => 'g7',
            '@display' => 'hxg7',
            '#' => '',
          ),
          71 =>
          array (
            '@n' => '71',
            '@piece' => 'b',
            '@from' => 'e7',
            '@to' => 'h4',
            '@display' => 'Bh4',
            '#' => '',
          ),
          72 =>
          array (
            '@n' => '72',
            '@piece' => 'Q',
            '@from' => 'f2',
            '@to' => 'h4',
            '@display' => 'Qxh4',
            '#' => '',
          ),
          73 =>
          array (
            '@n' => '73',
            '@piece' => 'p',
            '@from' => 'f3',
            '@to' => 'f2',
            '@state' => 'check',
            '@display' => 'f2+',
            '#' => '',
          ),
          74 =>
          array (
            '@n' => '74',
            '@piece' => 'K',
            '@from' => 'h1',
            '@to' => 'h2',
            '@display' => 'Kh2',
            '#' => '',
          ),
          75 =>
          array (
            '@n' => '75',
            '@piece' => 'p',
            '@from' => 'f2',
            '@to' => 'f1',
            '@promote' => 'n',
            '@state' => 'checkmate',
            '@display' => 'f1=N#',
            '#' => '',
          ),
        ),
        'end' => '0-1',
      ),
    ),
    2 =>
    array (
      'info' =>
      array (
        'Event' => 'S0032',
        'Site' => '003: IECG',
        'Date' =>
        array (
          '@Year' => '1997',
          '@Month' => '06',
          '@Day' => '06',
          '#' => '',
        ),
        'Round' => '1',
        'White' =>
        array (
          '@Elo' => '1600',
          '@NA' => 'Manfred_Rosenboom@compuserve.com',
          '@Country' => 'GER',
          '#' => 'Rosenboom,Manfred',
        ),
        'Black' =>
        array (
          '@NA' => 'argus@coac.es',
          '@Country' => 'ESP',
          '#' => 'Ruscalleda,Francesc',
        ),
        'Result' => '1-0',
        'EventDate' =>
        array (
          '@Year' => '1997',
          '@Month' => '05',
          '@Day' => '05',
          '#' => '',
        ),
        'ECO' => 'A09',
        'Termination' => 'normal',
        'Mode' => 'EM',
        'Prologue' => 'IECG Rating Tournament S0032
 Start Date: 15-MAY-1997',
      ),
      'moves' =>
      array (
        'move' =>
        array (
          0 =>
          array (
            '@n' => '0',
            '@piece' => 'N',
            '@from' => 'g1',
            '@to' => 'f3',
            '@display' => 'Nf3',
            'text' => '14-MAY-1997',
          ),
          1 =>
          array (
            '@n' => '1',
            '@piece' => 'p',
            '@from' => 'd7',
            '@to' => 'd5',
            '@display' => 'd5',
            'text' => '14-MAY',
          ),
          2 =>
          array (
            '@n' => '2',
            '@piece' => 'P',
            '@from' => 'c2',
            '@to' => 'c4',
            '@display' => 'c4',
            'text' => '16-MAY',
          ),
          3 =>
          array (
            '@n' => '3',
            '@piece' => 'p',
            '@from' => 'd5',
            '@to' => 'd4',
            '@display' => 'd4',
            'text' => '17-MAY',
          ),
          4 =>
          array (
            '@n' => '4',
            '@piece' => 'P',
            '@from' => 'g2',
            '@to' => 'g3',
            '@display' => 'g3',
            'text' => '17-MAY',
          ),
          5 =>
          array (
            '@n' => '5',
            '@piece' => 'p',
            '@from' => 'c7',
            '@to' => 'c5',
            '@display' => 'c5',
            'text' => '18-MAY',
          ),
          6 =>
          array (
            '@n' => '6',
            '@piece' => 'P',
            '@from' => 'e2',
            '@to' => 'e3',
            '@display' => 'e3',
            'text' => '18-MAY',
          ),
          7 =>
          array (
            '@n' => '7',
            '@piece' => 'n',
            '@from' => 'b8',
            '@to' => 'c6',
            '@display' => 'Nc6',
            'text' => '19-MAY',
          ),
          8 =>
          array (
            '@n' => '8',
            '@piece' => 'P',
            '@from' => 'e3',
            '@to' => 'd4',
            '@display' => 'exd4',
            'text' => '19-MAY',
          ),
          9 =>
          array (
            '@n' => '9',
            '@piece' => 'n',
            '@from' => 'c6',
            '@to' => 'd4',
            '@display' => 'Nxd4',
            'text' => '19-MAY',
          ),
          10 =>
          array (
            '@n' => '10',
            '@piece' => 'N',
            '@from' => 'f3',
            '@to' => 'd4',
            '@display' => 'Nxd4',
            'text' => '19-MAY',
          ),
          11 =>
          array (
            '@n' => '11',
            '@piece' => 'q',
            '@from' => 'd8',
            '@to' => 'd4',
            '@display' => 'Qxd4',
            'text' => '20-MAY',
          ),
          12 =>
          array (
            '@n' => '12',
            '@piece' => 'P',
            '@from' => 'd2',
            '@to' => 'd3',
            '@display' => 'd3',
            'text' => '20-MAY',
          ),
          13 =>
          array (
            '@n' => '13',
            '@piece' => 'b',
            '@from' => 'c8',
            '@to' => 'g4',
            '@display' => 'Bg4',
            'text' => '21-MAY',
          ),
          14 =>
          array (
            '@n' => '14',
            '@piece' => 'P',
            '@from' => 'f2',
            '@to' => 'f3',
            '@display' => 'f3',
            'text' => '21-MAY',
          ),
          15 =>
          array (
            '@n' => '15',
            '@piece' => 'b',
            '@from' => 'g4',
            '@to' => 'f5',
            '@display' => 'Bf5',
            'text' => '22-MAY',
          ),
          16 =>
          array (
            '@n' => '16',
            '@piece' => 'P',
            '@from' => 'g3',
            '@to' => 'g4',
            '@display' => 'g4',
            'text' => '22-MAY',
          ),
          17 =>
          array (
            '@n' => '17',
            '@piece' => 'b',
            '@from' => 'f5',
            '@to' => 'd7',
            '@display' => 'Bd7',
            'text' => '23-MAY',
          ),
          18 =>
          array (
            '@n' => '18',
            '@piece' => 'Q',
            '@from' => 'd1',
            '@to' => 'e2',
            '@display' => 'Qe2',
            'text' => '23-MAY',
          ),
          19 =>
          array (
            '@n' => '19',
            '@piece' => 'p',
            '@from' => 'e7',
            '@to' => 'e6',
            '@display' => 'e6',
            'text' => '24-MAY',
          ),
          20 =>
          array (
            '@n' => '20',
            '@piece' => 'B',
            '@from' => 'c1',
            '@to' => 'e3',
            '@display' => 'Be3',
            'text' => '24-MAY',
          ),
          21 =>
          array (
            '@n' => '21',
            '@piece' => 'q',
            '@from' => 'd4',
            '@to' => 'd6',
            '@display' => 'Qd6',
            'text' => '25-MAY',
          ),
          22 =>
          array (
            '@n' => '22',
            '@piece' => 'N',
            '@from' => 'b1',
            '@to' => 'c3',
            '@display' => 'Nc3',
            'text' => '25-MAY',
          ),
          23 =>
          array (
            '@n' => '23',
            '@piece' => 'n',
            '@from' => 'g8',
            '@to' => 'f6',
            '@display' => 'Nf6',
            'text' => '25-MAY',
          ),
          24 =>
          array (
            '@n' => '24',
            '@piece' => 'P',
            '@from' => 'g4',
            '@to' => 'g5',
            '@display' => 'g5',
            'text' => '25-MAY',
          ),
          25 =>
          array (
            '@n' => '25',
            '@piece' => 'n',
            '@from' => 'f6',
            '@to' => 'h5',
            '@display' => 'Nh5',
            'text' => '26-MAY',
          ),
          26 =>
          array (
            '@n' => '26',
            '@piece' => 'N',
            '@from' => 'c3',
            '@to' => 'e4',
            '@display' => 'Ne4',
            'text' => '27-MAY',
          ),
          27 =>
          array (
            '@n' => '27',
            '@piece' => 'q',
            '@from' => 'd6',
            '@to' => 'c7',
            '@display' => 'Qc7',
            'text' => '27-MAY',
          ),
          28 =>
          array (
            '@n' => '28',
            '@piece' => 'Q',
            '@from' => 'e2',
            '@to' => 'f2',
            '@display' => 'Qf2',
            'text' => '29-MAY',
          ),
          29 =>
          array (
            '@n' => '29',
            '@piece' => 'p',
            '@from' => 'b7',
            '@to' => 'b6',
            '@display' => 'b6',
            'text' => '30-MAY',
          ),
          30 =>
          array (
            '@n' => '30',
            '@piece' => 'P',
            '@from' => 'f3',
            '@to' => 'f4',
            '@display' => 'f4',
            'text' => '30-MAY',
          ),
          31 =>
          array (
            '@n' => '31',
            '@piece' => 'k',
            '@from' => 'e8',
            '@to' => 'c8',
            '@display' => 'O-O-O',
            'text' => '31-MAY',
          ),
          32 =>
          array (
            '@n' => '32',
            '@piece' => 'B',
            '@from' => 'f1',
            '@to' => 'e2',
            '@display' => 'Be2',
            'text' => '01-JUN',
          ),
          33 =>
          array (
            '@n' => '33',
            '@piece' => 'p',
            '@from' => 'f7',
            '@to' => 'f5',
            '@display' => 'f5',
            'text' => '02-JUN',
          ),
          34 =>
          array (
            '@n' => '34',
            '@piece' => 'B',
            '@from' => 'e2',
            '@to' => 'h5',
            '@display' => 'Bxh5',
            'text' => '02-JUN',
          ),
          35 =>
          array (
            '@n' => '35',
            '@piece' => 'p',
            '@from' => 'f5',
            '@to' => 'e4',
            '@display' => 'fxe4',
            'text' => '03-JUN',
          ),
          36 =>
          array (
            '@n' => '36',
            '@piece' => 'P',
            '@from' => 'd3',
            '@to' => 'e4',
            '@display' => 'dxe4',
            'text' => '03-JUN',
          ),
          37 =>
          array (
            '@n' => '37',
            '@piece' => 'b',
            '@from' => 'd7',
            '@to' => 'c6',
            '@display' => 'Bc6',
            'text' => '04-JUN',
          ),
          38 =>
          array (
            '@n' => '38',
            '@piece' => 'Q',
            '@from' => 'f2',
            '@to' => 'c2',
            '@display' => 'Qc2',
            'text' => '04-JUN',
          ),
          39 =>
          array (
            '@n' => '39',
            '@piece' => 'b',
            '@from' => 'f8',
            '@to' => 'd6',
            '@display' => 'Bd6',
            'text' => '05-JUN',
          ),
          40 =>
          array (
            '@n' => '40',
            '@piece' => 'K',
            '@from' => 'e1',
            '@to' => 'g1',
            '@display' => 'O-O',
            'text' => '05-JUN',
          ),
          41 =>
          array (
            '@n' => '41',
            '@piece' => 'r',
            '@from' => 'h8',
            '@to' => 'f8',
            '@display' => 'Rhf8',
            'text' => '06-JUN',
          ),
          42 =>
          array (
            '@n' => '42',
            '@piece' => 'P',
            '@from' => 'e4',
            '@to' => 'e5',
            '@display' => 'e5',
            'text' => '07-JUN',
          ),
          43 =>
          array (
            '@n' => '43',
            '@piece' => 'b',
            '@from' => 'd6',
            '@to' => 'e5',
            '@display' => 'Bxe5',
            'text' => '07-JUN',
          ),
          44 =>
          array (
            '@n' => '44',
            '@piece' => 'P',
            '@from' => 'f4',
            '@to' => 'e5',
            '@display' => 'fxe5',
            'text' => 'cond',
          ),
          45 =>
          array (
            '@n' => '45',
            '@piece' => 'q',
            '@from' => 'c7',
            '@to' => 'e5',
            '@display' => 'Qxe5',
            'text' => 'cond',
          ),
          46 =>
          array (
            '@n' => '46',
            '@piece' => 'R',
            '@from' => 'a1',
            '@to' => 'e1',
            '@display' => 'Rae1',
            'text' => '07-JUN',
          ),
          47 =>
          array (
            '@n' => '47',
            '@piece' => 'r',
            '@from' => 'f8',
            '@to' => 'f1',
            '@state' => 'check',
            '@display' => 'Rxf1+',
            'text' => 'cond',
          ),
          48 =>
          array (
            '@n' => '48',
            '@piece' => 'K',
            '@from' => 'g1',
            '@to' => 'f1',
            '@display' => 'Kxf1',
            'text' => 'cond',
          ),
          49 =>
          array (
            '@n' => '49',
            '@piece' => 'r',
            '@from' => 'd8',
            '@to' => 'f8',
            '@state' => 'check',
            '@display' => 'Rf8+',
            'text' => '08-JUN',
          ),
          50 =>
          array (
            '@n' => '50',
            '@piece' => 'K',
            '@from' => 'f1',
            '@to' => 'g1',
            '@display' => 'Kg1',
            'text' => 'cond',
          ),
          51 =>
          array (
            '@n' => '51',
            '@piece' => 'r',
            '@from' => 'f8',
            '@to' => 'f4',
            '@display' => 'Rf4',
            'text' => 'cond',
          ),
          52 =>
          array (
            '@n' => '52',
            '@piece' => 'R',
            '@from' => 'e1',
            '@to' => 'e2',
            '@display' => 'Re2',
            'text' => '08-JUN',
          ),
          53 =>
          array (
            '@n' => '53',
            '@piece' => 'q',
            '@from' => 'e5',
            '@to' => 'g5',
            '@state' => 'check',
            '@display' => 'Qxg5+',
            'text' => 'cond',
          ),
          54 =>
          array (
            '@n' => '54',
            '@piece' => 'R',
            '@from' => 'e2',
            '@to' => 'g2',
            '@display' => 'Rg2',
            'text' => 'cond',
          ),
          55 =>
          array (
            '@n' => '55',
            '@piece' => 'q',
            '@from' => 'g5',
            '@to' => 'e5',
            '@display' => 'Qe5',
            'text' => '08-JUN',
          ),
          56 =>
          array (
            '@n' => '56',
            '@piece' => 'R',
            '@from' => 'g2',
            '@to' => 'g3',
            '@display' => 'Rg3',
            'text' => '09-JUN',
          ),
          57 =>
          array (
            '@n' => '57',
            '@piece' => 'r',
            '@from' => 'f4',
            '@to' => 'e4',
            '@display' => 'Re4',
            'text' => '10-JUN',
          ),
          58 =>
          array (
            '@n' => '58',
            '@piece' => 'Q',
            '@from' => 'c2',
            '@to' => 'e2',
            '@display' => 'Qe2',
            'text' => '10-JUN',
          ),
          59 =>
          array (
            '@n' => '59',
            '@piece' => 'p',
            '@from' => 'g7',
            '@to' => 'g6',
            '@display' => 'g6',
            'text' => '10-JUN',
          ),
          60 =>
          array (
            '@n' => '60',
            '@piece' => 'B',
            '@from' => 'h5',
            '@to' => 'g4',
            '@display' => 'Bg4',
            'text' => '10-JUN',
          ),
          61 =>
          array (
            '@n' => '61',
            '@piece' => 'p',
            '@from' => 'h7',
            '@to' => 'h5',
            '@display' => 'h5',
            'text' => '11-JUN',
          ),
          62 =>
          array (
            '@n' => '62',
            '@piece' => 'B',
            '@from' => 'g4',
            '@to' => 'h3',
            '@display' => 'Bh3',
            'text' => '11-JUN',
          ),
          63 =>
          array (
            '@n' => '63',
            '@piece' => 'p',
            '@from' => 'h5',
            '@to' => 'h4',
            '@display' => 'h4',
            'text' => '12-JUN',
          ),
          64 =>
          array (
            '@n' => '64',
            '@piece' => 'R',
            '@from' => 'g3',
            '@to' => 'f3',
            '@display' => 'Rf3',
            'text' => '12-JUN',
          ),
          65 =>
          array (
            '@n' => '65',
            '@piece' => 'k',
            '@from' => 'c8',
            '@to' => 'b7',
            '@display' => 'Kb7',
            'text' => '13-JUN',
          ),
          66 =>
          array (
            '@n' => '66',
            '@piece' => 'B',
            '@from' => 'h3',
            '@to' => 'g2',
            '@display' => 'Bg2',
            'text' => '13-JUN',
          ),
          67 =>
          array (
            '@n' => '67',
            '@piece' => 'k',
            '@from' => 'b7',
            '@to' => 'a6',
            '@display' => 'Ka6',
            'text' => '14-JUN',
          ),
          68 =>
          array (
            '@n' => '68',
            '@piece' => 'R',
            '@from' => 'f3',
            '@to' => 'f7',
            '@display' => 'Rf7',
            'text' => '14-JUN',
          ),
          69 =>
          array (
            '@n' => '69',
            '@piece' => 'p',
            '@from' => 'h4',
            '@to' => 'h3',
            '@display' => 'h3',
            'text' => '15-JUN',
          ),
          70 =>
          array (
            '@n' => '70',
            '@piece' => 'B',
            '@from' => 'g2',
            '@to' => 'e4',
            '@display' => 'Bxe4',
            'text' => '15-JUN',
          ),
          71 =>
          array (
            '@n' => '71',
            '@piece' => 'q',
            '@from' => 'e5',
            '@to' => 'e4',
            '@display' => 'Qxe4',
            'text' => 'cond',
          ),
          72 =>
          array (
            '@n' => '72',
            '@piece' => 'K',
            '@from' => 'g1',
            '@to' => 'f2',
            '@display' => 'Kf2',
            'text' => 'cond',
          ),
          73 =>
          array (
            '@n' => '73',
            '@piece' => 'q',
            '@from' => 'e4',
            '@to' => 'b1',
            '@display' => 'Qb1',
            'text' => '15-JUN',
          ),
          74 =>
          array (
            '@n' => '74',
            '@piece' => 'B',
            '@from' => 'e3',
            '@to' => 'f4',
            '@display' => 'Bf4',
            'text' => '15-JUN',
          ),
          75 =>
          array (
            '@n' => '75',
            '@piece' => 'p',
            '@from' => 'g6',
            '@to' => 'g5',
            '@display' => 'g5',
            'text' => '16-JUN',
          ),
          76 =>
          array (
            '@n' => '76',
            '@piece' => 'B',
            '@from' => 'f4',
            '@to' => 'b8',
            '@display' => 'Bb8',
            'text' => '16-JUN',
          ),
          77 =>
          array (
            '@n' => '77',
            '@piece' => 'b',
            '@from' => 'c6',
            '@to' => 'b7',
            '@display' => 'Bb7',
            'text' => '17-JUN',
          ),
          78 =>
          array (
            '@n' => '78',
            '@piece' => 'Q',
            '@from' => 'e2',
            '@to' => 'f3',
            '@display' => 'Qf3',
            'text' => '17-JUN',
          ),
          79 =>
          array (
            '@n' => '79',
            '@piece' => 'q',
            '@from' => 'b1',
            '@to' => 'b2',
            '@state' => 'check',
            '@display' => 'Qxb2+',
            'text' => '18-JUN',
          ),
          80 =>
          array (
            '@n' => '80',
            '@piece' => 'K',
            '@from' => 'f2',
            '@to' => 'g3',
            '@display' => 'Kg3',
            'text' => '18-JUN
   Black resigns at 18-JUN-1997',
          ),
        ),
        'end' => '1-0',
      ),
    ),
    3 =>
    array (
      'info' =>
      array (
        'Event' => 'It',
        'Site' => '004: Linares',
        'Date' =>
        array (
          '@Year' => '1997',
          '#' => '',
        ),
        'Round' => '01',
        'White' => 'Valverde, Andres',
        'Black' => 'Dreev, A.',
        'Result' => '1-0',
      ),
      'moves' =>
      array (
        'move' =>
        array (
          0 =>
          array (
            '@n' => '0',
            '@piece' => 'P',
            '@from' => 'e2',
            '@to' => 'e4',
            '@display' => 'e4',
            '#' => '',
          ),
          1 =>
          array (
            '@n' => '1',
            '@piece' => 'p',
            '@from' => 'c7',
            '@to' => 'c5',
            '@display' => 'c5',
            '#' => '',
          ),
          2 =>
          array (
            '@n' => '2',
            '@piece' => 'N',
            '@from' => 'g1',
            '@to' => 'f3',
            '@display' => 'Nf3',
            '#' => '',
          ),
          3 =>
          array (
            '@n' => '3',
            '@piece' => 'n',
            '@from' => 'b8',
            '@to' => 'c6',
            '@display' => 'Nc6',
            '#' => '',
          ),
          4 =>
          array (
            '@n' => '4',
            '@piece' => 'P',
            '@from' => 'd2',
            '@to' => 'd4',
            '@display' => 'd4',
            '#' => '',
          ),
          5 =>
          array (
            '@n' => '5',
            '@piece' => 'p',
            '@from' => 'c5',
            '@to' => 'd4',
            '@display' => 'cxd4',
            '#' => '',
          ),
          6 =>
          array (
            '@n' => '6',
            '@piece' => 'N',
            '@from' => 'f3',
            '@to' => 'd4',
            '@display' => 'Nxd4',
            '#' => '',
          ),
          7 =>
          array (
            '@n' => '7',
            '@piece' => 'n',
            '@from' => 'g8',
            '@to' => 'f6',
            '@display' => 'Nf6',
            '#' => '',
          ),
          8 =>
          array (
            '@n' => '8',
            '@piece' => 'N',
            '@from' => 'b1',
            '@to' => 'c3',
            '@display' => 'Nc3',
            '#' => '',
          ),
          9 =>
          array (
            '@n' => '9',
            '@piece' => 'p',
            '@from' => 'd7',
            '@to' => 'd6',
            '@display' => 'd6',
            '#' => '',
          ),
          10 =>
          array (
            '@n' => '10',
            '@piece' => 'P',
            '@from' => 'g2',
            '@to' => 'g3',
            '@display' => 'g3',
            '#' => '',
          ),
          11 =>
          array (
            '@n' => '11',
            '@piece' => 'p',
            '@from' => 'g7',
            '@to' => 'g6',
            '@display' => 'g6',
            '#' => '',
          ),
          12 =>
          array (
            '@n' => '12',
            '@piece' => 'B',
            '@from' => 'f1',
            '@to' => 'g2',
            '@display' => 'Bg2',
            '#' => '',
          ),
          13 =>
          array (
            '@n' => '13',
            '@piece' => 'b',
            '@from' => 'f8',
            '@to' => 'g7',
            '@display' => 'Bg7',
            '#' => '',
          ),
          14 =>
          array (
            '@n' => '14',
            '@piece' => 'N',
            '@from' => 'd4',
            '@to' => 'c6',
            '@display' => 'Nxc6',
            '#' => '',
          ),
          15 =>
          array (
            '@n' => '15',
            '@piece' => 'p',
            '@from' => 'b7',
            '@to' => 'c6',
            '@display' => 'bxc6',
            '#' => '',
          ),
          16 =>
          array (
            '@n' => '16',
            '@piece' => 'P',
            '@from' => 'e4',
            '@to' => 'e5',
            '@display' => 'e5',
            '#' => '',
          ),
          17 =>
          array (
            '@n' => '17',
            '@piece' => 'p',
            '@from' => 'd6',
            '@to' => 'e5',
            '@display' => 'dxe5',
            '#' => '',
          ),
          18 =>
          array (
            '@n' => '18',
            '@piece' => 'Q',
            '@from' => 'd1',
            '@to' => 'd8',
            '@state' => 'check',
            '@display' => 'Qxd8+',
            '#' => '',
          ),
          19 =>
          array (
            '@n' => '19',
            '@piece' => 'k',
            '@from' => 'e8',
            '@to' => 'd8',
            '@display' => 'Kxd8',
            '#' => '',
          ),
          20 =>
          array (
            '@n' => '20',
            '@piece' => 'B',
            '@from' => 'g2',
            '@to' => 'c6',
            '@display' => 'Bxc6',
            '#' => '',
          ),
          21 =>
          array (
            '@n' => '21',
            '@piece' => 'r',
            '@from' => 'a8',
            '@to' => 'b8',
            '@display' => 'Rb8',
            '#' => '',
          ),
          22 =>
          array (
            '@n' => '22',
            '@piece' => 'B',
            '@from' => 'c1',
            '@to' => 'e3',
            '@display' => 'Be3',
            '#' => '',
          ),
          23 =>
          array (
            '@n' => '23',
            '@piece' => 'r',
            '@from' => 'b8',
            '@to' => 'b2',
            '@display' => 'Rxb2',
            '#' => '',
          ),
          24 =>
          array (
            '@n' => '24',
            '@piece' => 'K',
            '@from' => 'e1',
            '@to' => 'c1',
            '@state' => 'check',
            '@display' => 'O-O-O+',
            '#' => '',
          ),
        ),
        'end' => '1-0',
      ),
    ),
    4 =>
    array (
      'info' =>
      array (
        'Event' => 'Class2-1996.09',
        'Site' => '005: IECC',
        'Date' =>
        array (
          '@Year' => '1996',
          '#' => '',
        ),
        'Round' => '1',
        'White' => 'Andres Valverde',
        'Black' => 'Dale Whitehead',
        'Result' => '1-0',
      ),
      'moves' =>
      array (
        'move' =>
        array (
          0 =>
          array (
            '@n' => '0',
            '@piece' => 'P',
            '@from' => 'd2',
            '@to' => 'd4',
            '@display' => 'd4',
            '#' => '',
          ),
          1 =>
          array (
            '@n' => '1',
            '@piece' => 'p',
            '@from' => 'd7',
            '@to' => 'd5',
            '@display' => 'd5',
            '#' => '',
          ),
          2 =>
          array (
            '@n' => '2',
            '@piece' => 'P',
            '@from' => 'c2',
            '@to' => 'c4',
            '@display' => 'c4',
            '#' => '',
          ),
          3 =>
          array (
            '@n' => '3',
            '@piece' => 'p',
            '@from' => 'c7',
            '@to' => 'c6',
            '@display' => 'c6',
            '#' => '',
          ),
          4 =>
          array (
            '@n' => '4',
            '@piece' => 'N',
            '@from' => 'b1',
            '@to' => 'c3',
            '@display' => 'Nc3',
            '#' => '',
          ),
          5 =>
          array (
            '@n' => '5',
            '@piece' => 'n',
            '@from' => 'g8',
            '@to' => 'f6',
            '@display' => 'Nf6',
            '#' => '',
          ),
          6 =>
          array (
            '@n' => '6',
            '@piece' => 'N',
            '@from' => 'g1',
            '@to' => 'f3',
            '@display' => 'Nf3',
            '#' => '',
          ),
          7 =>
          array (
            '@n' => '7',
            '@piece' => 'p',
            '@from' => 'e7',
            '@to' => 'e6',
            '@display' => 'e6',
            '#' => '',
          ),
          8 =>
          array (
            '@n' => '8',
            '@piece' => 'B',
            '@from' => 'c1',
            '@to' => 'g5',
            '@display' => 'Bg5',
            '#' => '',
          ),
          9 =>
          array (
            '@n' => '9',
            '@piece' => 'n',
            '@from' => 'b8',
            '@to' => 'd7',
            '@display' => 'Nbd7',
            '#' => '',
          ),
          10 =>
          array (
            '@n' => '10',
            '@piece' => 'P',
            '@from' => 'e2',
            '@to' => 'e3',
            '@display' => 'e3',
            '#' => '',
          ),
          11 =>
          array (
            '@n' => '11',
            '@piece' => 'q',
            '@from' => 'd8',
            '@to' => 'a5',
            '@display' => 'Qa5',
            '#' => '',
          ),
          12 =>
          array (
            '@n' => '12',
            '@piece' => 'P',
            '@from' => 'c4',
            '@to' => 'd5',
            '@display' => 'cxd5',
            '#' => '',
          ),
          13 =>
          array (
            '@n' => '13',
            '@piece' => 'n',
            '@from' => 'f6',
            '@to' => 'd5',
            '@display' => 'Nxd5',
            '#' => '',
          ),
          14 =>
          array (
            '@n' => '14',
            '@piece' => 'Q',
            '@from' => 'd1',
            '@to' => 'd2',
            '@display' => 'Qd2',
            '#' => '',
          ),
          15 =>
          array (
            '@n' => '15',
            '@piece' => 'n',
            '@from' => 'd7',
            '@to' => 'b6',
            '@display' => 'Nd7b6',
            '#' => '',
          ),
          16 =>
          array (
            '@n' => '16',
            '@piece' => 'B',
            '@from' => 'f1',
            '@to' => 'd3',
            '@display' => 'Bd3',
            '#' => '',
          ),
          17 =>
          array (
            '@n' => '17',
            '@piece' => 'n',
            '@from' => 'd5',
            '@to' => 'c3',
            '@display' => 'Nxc3',
            '#' => '',
          ),
          18 =>
          array (
            '@n' => '18',
            '@piece' => 'P',
            '@from' => 'b2',
            '@to' => 'c3',
            '@display' => 'bxc3',
            '#' => '',
          ),
          19 =>
          array (
            '@n' => '19',
            '@piece' => 'n',
            '@from' => 'b6',
            '@to' => 'd5',
            '@display' => 'Nd5',
            '#' => '',
          ),
          20 =>
          array (
            '@n' => '20',
            '@piece' => 'K',
            '@from' => 'e1',
            '@to' => 'g1',
            '@display' => 'O-O',
            '#' => '',
          ),
          21 =>
          array (
            '@n' => '21',
            '@piece' => 'q',
            '@from' => 'a5',
            '@to' => 'c3',
            '@display' => 'Qxc3',
            '#' => '',
          ),
          22 =>
          array (
            '@n' => '22',
            '@piece' => 'Q',
            '@from' => 'd2',
            '@to' => 'e2',
            '@display' => 'Qe2',
            '#' => '',
          ),
          23 =>
          array (
            '@n' => '23',
            '@piece' => 'b',
            '@from' => 'f8',
            '@to' => 'd6',
            '@display' => 'Bd6',
            '#' => '',
          ),
          24 =>
          array (
            '@n' => '24',
            '@piece' => 'R',
            '@from' => 'a1',
            '@to' => 'c1',
            '@display' => 'Rac1',
            '#' => '',
          ),
          25 =>
          array (
            '@n' => '25',
            '@piece' => 'q',
            '@from' => 'c3',
            '@to' => 'a5',
            '@display' => 'Qa5',
            '#' => '',
          ),
          26 =>
          array (
            '@n' => '26',
            '@piece' => 'B',
            '@from' => 'd3',
            '@to' => 'b1',
            '@display' => 'Bb1',
            '#' => '',
          ),
          27 =>
          array (
            '@n' => '27',
            '@piece' => 'n',
            '@from' => 'd5',
            '@to' => 'c3',
            '@display' => 'Nc3',
            '#' => '',
          ),
          28 =>
          array (
            '@n' => '28',
            '@piece' => 'R',
            '@from' => 'c1',
            '@to' => 'c3',
            '@display' => 'Rxc3',
            '#' => '',
          ),
          29 =>
          array (
            '@n' => '29',
            '@piece' => 'q',
            '@from' => 'a5',
            '@to' => 'c3',
            '@display' => 'Qxc3',
            '#' => '',
          ),
          30 =>
          array (
            '@n' => '30',
            '@piece' => 'P',
            '@from' => 'e3',
            '@to' => 'e4',
            '@display' => 'e4',
            '#' => '',
          ),
          31 =>
          array (
            '@n' => '31',
            '@piece' => 'k',
            '@from' => 'e8',
            '@to' => 'g8',
            '@display' => 'O-O',
            '#' => '',
          ),
          32 =>
          array (
            '@n' => '32',
            '@piece' => 'P',
            '@from' => 'e4',
            '@to' => 'e5',
            '@display' => 'e5',
            '#' => '',
          ),
          33 =>
          array (
            '@n' => '33',
            '@piece' => 'b',
            '@from' => 'd6',
            '@to' => 'e5',
            '@display' => 'Bxe5',
            '#' => '',
          ),
          34 =>
          array (
            '@n' => '34',
            '@piece' => 'P',
            '@from' => 'd4',
            '@to' => 'e5',
            '@display' => 'dxe5',
            '#' => '',
          ),
          35 =>
          array (
            '@n' => '35',
            '@piece' => 'p',
            '@from' => 'f7',
            '@to' => 'f6',
            '@display' => 'f6',
            '#' => '',
          ),
          36 =>
          array (
            '@n' => '36',
            '@piece' => 'Q',
            '@from' => 'e2',
            '@to' => 'e4',
            '@display' => 'Qe4',
            '#' => '',
          ),
          37 =>
          array (
            '@n' => '37',
            '@piece' => 'p',
            '@from' => 'f6',
            '@to' => 'f5',
            '@display' => 'f5',
            '#' => '',
          ),
          38 =>
          array (
            '@n' => '38',
            '@piece' => 'Q',
            '@from' => 'e4',
            '@to' => 'h4',
            '@display' => 'Qh4',
            '#' => '',
          ),
          39 =>
          array (
            '@n' => '39',
            '@piece' => 'p',
            '@from' => 'c6',
            '@to' => 'c5',
            '@display' => 'c5',
            '#' => '',
          ),
          40 =>
          array (
            '@n' => '40',
            '@piece' => 'R',
            '@from' => 'f1',
            '@to' => 'd1',
            '@display' => 'Rd1',
            '#' => '',
          ),
          41 =>
          array (
            '@n' => '41',
            '@piece' => 'q',
            '@from' => 'c3',
            '@to' => 'a5',
            '@display' => 'Qa5',
            '#' => '',
          ),
          42 =>
          array (
            '@n' => '42',
            '@piece' => 'B',
            '@from' => 'g5',
            '@to' => 'd8',
            '@display' => 'Bd8',
            '#' => '',
          ),
          43 =>
          array (
            '@n' => '43',
            '@piece' => 'q',
            '@from' => 'a5',
            '@to' => 'b4',
            '@display' => 'Qb4',
            '#' => '',
          ),
          44 =>
          array (
            '@n' => '44',
            '@piece' => 'Q',
            '@from' => 'h4',
            '@to' => 'b4',
            '@display' => 'Qxb4',
            '#' => '',
          ),
          45 =>
          array (
            '@n' => '45',
            '@piece' => 'p',
            '@from' => 'c5',
            '@to' => 'b4',
            '@display' => 'cxb4',
            '#' => '',
          ),
          46 =>
          array (
            '@n' => '46',
            '@piece' => 'B',
            '@from' => 'd8',
            '@to' => 'e7',
            '@display' => 'Be7',
            '#' => '',
          ),
          47 =>
          array (
            '@n' => '47',
            '@piece' => 'r',
            '@from' => 'f8',
            '@to' => 'e8',
            '@display' => 'Re8',
            '#' => '',
          ),
          48 =>
          array (
            '@n' => '48',
            '@piece' => 'B',
            '@from' => 'e7',
            '@to' => 'b4',
            '@display' => 'Bxb4',
            '#' => '',
          ),
          49 =>
          array (
            '@n' => '49',
            '@piece' => 'p',
            '@from' => 'b7',
            '@to' => 'b6',
            '@display' => 'b6',
            '#' => '',
          ),
          50 =>
          array (
            '@n' => '50',
            '@piece' => 'P',
            '@from' => 'h2',
            '@to' => 'h3',
            '@display' => 'h3',
            '#' => '',
          ),
          51 =>
          array (
            '@n' => '51',
            '@piece' => 'b',
            '@from' => 'c8',
            '@to' => 'a6',
            '@display' => 'Ba6',
            '#' => '',
          ),
          52 =>
          array (
            '@n' => '52',
            '@piece' => 'B',
            '@from' => 'b1',
            '@to' => 'd3',
            '@display' => 'Bd3',
            '#' => '',
          ),
          53 =>
          array (
            '@n' => '53',
            '@piece' => 'r',
            '@from' => 'e8',
            '@to' => 'd8',
            '@display' => 'Red8',
            '#' => '',
          ),
          54 =>
          array (
            '@n' => '54',
            '@piece' => 'B',
            '@from' => 'b4',
            '@to' => 'd6',
            '@display' => 'Bd6',
            '#' => '',
          ),
          55 =>
          array (
            '@n' => '55',
            '@piece' => 'b',
            '@from' => 'a6',
            '@to' => 'd3',
            '@display' => 'Bxd3',
            '#' => '',
          ),
          56 =>
          array (
            '@n' => '56',
            '@piece' => 'R',
            '@from' => 'd1',
            '@to' => 'd3',
            '@display' => 'Rxd3',
            '#' => '',
          ),
          57 =>
          array (
            '@n' => '57',
            '@piece' => 'r',
            '@from' => 'a8',
            '@to' => 'c8',
            '@display' => 'Rac8',
            '#' => '',
          ),
          58 =>
          array (
            '@n' => '58',
            '@piece' => 'N',
            '@from' => 'f3',
            '@to' => 'd4',
            '@display' => 'Nd4',
            '#' => '',
          ),
          59 =>
          array (
            '@n' => '59',
            '@piece' => 'r',
            '@from' => 'c8',
            '@to' => 'c1',
            '@state' => 'check',
            '@display' => 'Rc1+',
            '#' => '',
          ),
          60 =>
          array (
            '@n' => '60',
            '@piece' => 'K',
            '@from' => 'g1',
            '@to' => 'h2',
            '@display' => 'Kh2',
            '#' => '',
          ),
          61 =>
          array (
            '@n' => '61',
            '@piece' => 'k',
            '@from' => 'g8',
            '@to' => 'f7',
            '@display' => 'Kf7',
            '#' => '',
          ),
          62 =>
          array (
            '@n' => '62',
            '@piece' => 'R',
            '@from' => 'd3',
            '@to' => 'a3',
            '@display' => 'Ra3',
            '#' => '',
          ),
          63 =>
          array (
            '@n' => '63',
            '@piece' => 'r',
            '@from' => 'd8',
            '@to' => 'd7',
            '@display' => 'Rd7',
            '#' => '',
          ),
          64 =>
          array (
            '@n' => '64',
            '@piece' => 'R',
            '@from' => 'a3',
            '@to' => 'a6',
            '@display' => 'Ra6',
            '#' => '',
          ),
          65 =>
          array (
            '@n' => '65',
            '@piece' => 'p',
            '@from' => 'g7',
            '@to' => 'g5',
            '@display' => 'g5',
            '#' => '',
          ),
          66 =>
          array (
            '@n' => '66',
            '@piece' => 'N',
            '@from' => 'd4',
            '@to' => 'b5',
            '@display' => 'Nb5',
            '#' => '',
          ),
          67 =>
          array (
            '@n' => '67',
            '@piece' => 'p',
            '@from' => 'f5',
            '@to' => 'f4',
            '@display' => 'f4',
            '#' => '',
          ),
          68 =>
          array (
            '@n' => '68',
            '@piece' => 'P',
            '@from' => 'g2',
            '@to' => 'g4',
            '@display' => 'g4',
            '#' => '',
          ),
          69 =>
          array (
            '@n' => '69',
            '@piece' => 'p',
            '@from' => 'f4',
            '@to' => 'g3',
            '@state' => 'check',
            '@display' => 'fxg3+',
            'text' => 'e.p.',
          ),
          70 =>
          array (
            '@n' => '70',
            '@piece' => 'K',
            '@from' => 'h2',
            '@to' => 'g3',
            '@display' => 'Kxg3',
            '#' => '',
          ),
          71 =>
          array (
            '@n' => '71',
            '@piece' => 'k',
            '@from' => 'f7',
            '@to' => 'g6',
            '@display' => 'Kg6',
            '#' => '',
          ),
          72 =>
          array (
            '@n' => '72',
            '@piece' => 'R',
            '@from' => 'a6',
            '@to' => 'a7',
            '@display' => 'Rxa7',
            '#' => '',
          ),
          73 =>
          array (
            '@n' => '73',
            '@piece' => 'r',
            '@from' => 'd7',
            '@to' => 'a7',
            '@display' => 'Rxa7',
            '#' => '',
          ),
          74 =>
          array (
            '@n' => '74',
            '@piece' => 'N',
            '@from' => 'b5',
            '@to' => 'a7',
            '@display' => 'Nxa7',
            '#' => '',
          ),
          75 =>
          array (
            '@n' => '75',
            '@piece' => 'r',
            '@from' => 'c1',
            '@to' => 'c3',
            '@state' => 'check',
            '@display' => 'Rc3+',
            '#' => '',
          ),
          76 =>
          array (
            '@n' => '76',
            '@piece' => 'K',
            '@from' => 'g3',
            '@to' => 'g2',
            '@display' => 'Kg2',
            '#' => '',
          ),
          77 =>
          array (
            '@n' => '77',
            '@piece' => 'k',
            '@from' => 'g6',
            '@to' => 'f5',
            '@display' => 'Kf5',
            '#' => '',
          ),
          78 =>
          array (
            '@n' => '78',
            '@piece' => 'N',
            '@from' => 'a7',
            '@to' => 'b5',
            '@display' => 'Nb5',
            '#' => '',
          ),
          79 =>
          array (
            '@n' => '79',
            '@piece' => 'r',
            '@from' => 'c3',
            '@to' => 'd3',
            '@display' => 'Rd3',
            '#' => '',
          ),
          80 =>
          array (
            '@n' => '80',
            '@piece' => 'B',
            '@from' => 'd6',
            '@to' => 'c7',
            '@display' => 'Bc7',
            '#' => '',
          ),
          81 =>
          array (
            '@n' => '81',
            '@piece' => 'p',
            '@from' => 'h7',
            '@to' => 'h5',
            '@display' => 'h5',
            '#' => '',
          ),
          82 =>
          array (
            '@n' => '82',
            '@piece' => 'P',
            '@from' => 'a2',
            '@to' => 'a4',
            '@display' => 'a4',
            '#' => '',
          ),
          83 =>
          array (
            '@n' => '83',
            '@piece' => 'p',
            '@from' => 'g5',
            '@to' => 'g4',
            '@display' => 'g4',
            '#' => '',
          ),
          84 =>
          array (
            '@n' => '84',
            '@piece' => 'P',
            '@from' => 'h3',
            '@to' => 'g4',
            '@state' => 'check',
            '@display' => 'hxg4+',
            '#' => '',
          ),
          85 =>
          array (
            '@n' => '85',
            '@piece' => 'k',
            '@from' => 'f5',
            '@to' => 'g4',
            '@display' => 'Kxg4',
            '#' => '',
          ),
          86 =>
          array (
            '@n' => '86',
            '@piece' => 'B',
            '@from' => 'c7',
            '@to' => 'b6',
            '@display' => 'Bxb6',
            '#' => '',
          ),
          87 =>
          array (
            '@n' => '87',
            '@piece' => 'r',
            '@from' => 'd3',
            '@to' => 'b3',
            '@display' => 'Rb3',
            '#' => '',
          ),
          88 =>
          array (
            '@n' => '88',
            '@piece' => 'B',
            '@from' => 'b6',
            '@to' => 'c5',
            '@display' => 'Bc5',
            '#' => '',
          ),
          89 =>
          array (
            '@n' => '89',
            '@piece' => 'r',
            '@from' => 'b3',
            '@to' => 'b2',
            '@display' => 'Rb2',
            '#' => '',
          ),
          90 =>
          array (
            '@n' => '90',
            '@piece' => 'N',
            '@from' => 'b5',
            '@to' => 'a3',
            '@display' => 'Na3',
            '#' => '',
          ),
          91 =>
          array (
            '@n' => '91',
            '@piece' => 'r',
            '@from' => 'b2',
            '@to' => 'e2',
            '@display' => 'Re2',
            '#' => '',
          ),
          92 =>
          array (
            '@n' => '92',
            '@piece' => 'P',
            '@from' => 'a4',
            '@to' => 'a5',
            '@display' => 'a5',
            '#' => '',
          ),
          93 =>
          array (
            '@n' => '93',
            '@piece' => 'r',
            '@from' => 'e2',
            '@to' => 'e5',
            '@display' => 'Rxe5',
            '#' => '',
          ),
          94 =>
          array (
            '@n' => '94',
            '@piece' => 'B',
            '@from' => 'c5',
            '@to' => 'b4',
            '@display' => 'Bb4',
            '#' => '',
          ),
          95 =>
          array (
            '@n' => '95',
            '@piece' => 'r',
            '@from' => 'e5',
            '@to' => 'g5',
            '@display' => 'Rg5',
            '#' => '',
          ),
          96 =>
          array (
            '@n' => '96',
            '@piece' => 'P',
            '@from' => 'a5',
            '@to' => 'a6',
            '@display' => 'a6',
            '#' => '',
          ),
          97 =>
          array (
            '@n' => '97',
            '@piece' => 'p',
            '@from' => 'h5',
            '@to' => 'h4',
            '@display' => 'h4',
            '#' => '',
          ),
          98 =>
          array (
            '@n' => '98',
            '@piece' => 'P',
            '@from' => 'a6',
            '@to' => 'a7',
            '@display' => 'a7',
            '#' => '',
          ),
        ),
        'end' => '1-0',
      ),
    ),
    5 =>
    array (
      'info' =>
      array (
        'Event' => 'CSS 2/97 Yazgac Kombi',
        'Site' => '006: ?',
        'Date' =>
        array (
          '@Year' => '1997',
          '#' => '',
        ),
        'White' => 'Kom1: Karpov',
        'Black' => 'Kortschnoj, Baguio 1978',
        'Result' => '1-0',
        'FEN' =>
        array (
          '@ActiveColor' => 'w',
          '@DrawCount' => '0',
          '@MoveCount' => '1',
          '#' => '
4rk2/2p2prp/pq2b2N/1p6/8/2PR1Q2/PP4PP/5R1K/ 
',
        ),
        'PlyCount' => '7',
        'EventDate' =>
        array (
          '@Year' => '1997',
          '#' => '',
        ),
      ),
      'moves' =>
      array (
        'move' =>
        array (
          0 =>
          array (
            '@n' => '0',
            '@piece' => 'R',
            '@from' => 'd3',
            '@to' => 'd7',
            '@NAG' => '1',
            '@display' => 'Rd7',
            '#' => '',
          ),
          1 =>
          array (
            '@n' => '1',
            '@piece' => 'r',
            '@from' => 'e8',
            '@to' => 'b8',
            '@display' => 'Rb8',
            '#' => '',
          ),
          2 =>
          array (
            '@n' => '2',
            '@piece' => 'N',
            '@from' => 'h6',
            '@to' => 'f7',
            '@display' => 'Nxf7',
            '#' => '',
          ),
          3 =>
          array (
            '@n' => '3',
            '@piece' => 'b',
            '@from' => 'e6',
            '@to' => 'd7',
            '@display' => 'Bxd7',
            '#' => '',
          ),
          4 =>
          array (
            '@n' => '4',
            '@piece' => 'N',
            '@from' => 'f7',
            '@to' => 'd8',
            '@state' => 'check',
            '@display' => 'Nd8+',
            '#' => '',
          ),
          5 =>
          array (
            '@n' => '5',
            '@piece' => 'k',
            '@from' => 'f8',
            '@to' => 'e7',
            '@display' => 'Ke7',
            '#' => '',
          ),
          6 =>
          array (
            '@n' => '6',
            '@piece' => 'Q',
            '@from' => 'f3',
            '@to' => 'f8',
            '@state' => 'checkmate',
            '@display' => 'Qf8#',
            '#' => '',
          ),
        ),
        'end' => '1-0',
      ),
    ),
    6 =>
    array (
      'info' =>
      array (
        'Site' => '007: New Orleans',
        'Date' =>
        array (
          '@Year' => '1858',
          '#' => '',
        ),
        'White' => 'Morphy,Paul',
        'Black' => 'Amateur',
        'Result' => '1-0',
        'FEN' =>
        array (
          '@ActiveColor' => 'w',
          '@CastlingAvailability' => 'Kkq',
          '@DrawCount' => '0',
          '@MoveCount' => '0',
          '#' => '
rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/1NBQKBNR/ 
',
        ),
        'Prologue' => 'White plays without queen\'s rook',
      ),
      'moves' =>
      array (
        'move' =>
        array (
          0 =>
          array (
            '@n' => '0',
            '@piece' => 'P',
            '@from' => 'e2',
            '@to' => 'e4',
            '@display' => 'e4',
            '#' => '',
          ),
          1 =>
          array (
            '@n' => '1',
            '@piece' => 'p',
            '@from' => 'e7',
            '@to' => 'e5',
            '@display' => 'e5',
            '#' => '',
          ),
          2 =>
          array (
            '@n' => '2',
            '@piece' => 'N',
            '@from' => 'g1',
            '@to' => 'f3',
            '@display' => 'Nf3',
            '#' => '',
          ),
          3 =>
          array (
            '@n' => '3',
            '@piece' => 'n',
            '@from' => 'b8',
            '@to' => 'c6',
            '@display' => 'Nc6',
            '#' => '',
          ),
          4 =>
          array (
            '@n' => '4',
            '@piece' => 'B',
            '@from' => 'f1',
            '@to' => 'c4',
            '@display' => 'Bc4',
            '#' => '',
          ),
          5 =>
          array (
            '@n' => '5',
            '@piece' => 'n',
            '@from' => 'g8',
            '@to' => 'f6',
            '@display' => 'Nf6',
            '#' => '',
          ),
          6 =>
          array (
            '@n' => '6',
            '@piece' => 'N',
            '@from' => 'f3',
            '@to' => 'g5',
            '@display' => 'Ng5',
            '#' => '',
          ),
          7 =>
          array (
            '@n' => '7',
            '@piece' => 'p',
            '@from' => 'd7',
            '@to' => 'd5',
            '@display' => 'd5',
            '#' => '',
          ),
          8 =>
          array (
            '@n' => '8',
            '@piece' => 'P',
            '@from' => 'e4',
            '@to' => 'd5',
            '@display' => 'exd5',
            '#' => '',
          ),
          9 =>
          array (
            '@n' => '9',
            '@piece' => 'n',
            '@from' => 'f6',
            '@to' => 'd5',
            '@display' => 'Nxd5',
            '#' => '',
          ),
          10 =>
          array (
            '@n' => '10',
            '@piece' => 'N',
            '@from' => 'g5',
            '@to' => 'f7',
            '@display' => 'Nxf7',
            '#' => '',
          ),
          11 =>
          array (
            '@n' => '11',
            '@piece' => 'k',
            '@from' => 'e8',
            '@to' => 'f7',
            '@display' => 'Kxf7',
            '#' => '',
          ),
          12 =>
          array (
            '@n' => '12',
            '@piece' => 'Q',
            '@from' => 'd1',
            '@to' => 'f3',
            '@state' => 'check',
            '@display' => 'Qf3+',
            '#' => '',
          ),
          13 =>
          array (
            '@n' => '13',
            '@piece' => 'k',
            '@from' => 'f7',
            '@to' => 'e6',
            '@display' => 'Ke6',
            '#' => '',
          ),
          14 =>
          array (
            '@n' => '14',
            '@piece' => 'N',
            '@from' => 'b1',
            '@to' => 'c3',
            '@display' => 'Nc3',
            '#' => '',
          ),
          15 =>
          array (
            '@n' => '15',
            '@piece' => 'n',
            '@from' => 'c6',
            '@to' => 'd4',
            '@display' => 'Nd4',
            '#' => '',
          ),
          16 =>
          array (
            '@n' => '16',
            '@piece' => 'B',
            '@from' => 'c4',
            '@to' => 'd5',
            '@state' => 'check',
            '@display' => 'Bxd5+',
            '#' => '',
          ),
          17 =>
          array (
            '@n' => '17',
            '@piece' => 'k',
            '@from' => 'e6',
            '@to' => 'd6',
            '@display' => 'Kd6',
            '#' => '',
          ),
          18 =>
          array (
            '@n' => '18',
            '@piece' => 'Q',
            '@from' => 'f3',
            '@to' => 'f7',
            '@display' => 'Qf7',
            '#' => '',
          ),
          19 =>
          array (
            '@n' => '19',
            '@piece' => 'b',
            '@from' => 'c8',
            '@to' => 'e6',
            '@display' => 'Be6',
            '#' => '',
          ),
          20 =>
          array (
            '@n' => '20',
            '@piece' => 'B',
            '@from' => 'd5',
            '@to' => 'e6',
            '@display' => 'Bxe6',
            '#' => '',
          ),
          21 =>
          array (
            '@n' => '21',
            '@piece' => 'n',
            '@from' => 'd4',
            '@to' => 'e6',
            '@display' => 'Nxe6',
            '#' => '',
          ),
          22 =>
          array (
            '@n' => '22',
            '@piece' => 'N',
            '@from' => 'c3',
            '@to' => 'e4',
            '@state' => 'check',
            '@display' => 'Ne4+',
            '#' => '',
          ),
          23 =>
          array (
            '@n' => '23',
            '@piece' => 'k',
            '@from' => 'd6',
            '@to' => 'd5',
            '@display' => 'Kd5',
            '#' => '',
          ),
          24 =>
          array (
            '@n' => '24',
            '@piece' => 'P',
            '@from' => 'c2',
            '@to' => 'c4',
            '@state' => 'check',
            '@display' => 'c4+',
            '#' => '',
          ),
          25 =>
          array (
            '@n' => '25',
            '@piece' => 'k',
            '@from' => 'd5',
            '@to' => 'e4',
            '@display' => 'Kxe4',
            '#' => '',
          ),
          26 =>
          array (
            '@n' => '26',
            '@piece' => 'Q',
            '@from' => 'f7',
            '@to' => 'e6',
            '@display' => 'Qxe6',
            '#' => '',
          ),
          27 =>
          array (
            '@n' => '27',
            '@piece' => 'q',
            '@from' => 'd8',
            '@to' => 'd4',
            '@display' => 'Qd4',
            '#' => '',
          ),
          28 =>
          array (
            '@n' => '28',
            '@piece' => 'Q',
            '@from' => 'e6',
            '@to' => 'g4',
            '@state' => 'check',
            '@display' => 'Qg4+',
            '#' => '',
          ),
          29 =>
          array (
            '@n' => '29',
            '@piece' => 'k',
            '@from' => 'e4',
            '@to' => 'd3',
            '@display' => 'Kd3',
            '#' => '',
          ),
          30 =>
          array (
            '@n' => '30',
            '@piece' => 'Q',
            '@from' => 'g4',
            '@to' => 'e2',
            '@state' => 'check',
            '@display' => 'Qe2+',
            '#' => '',
          ),
          31 =>
          array (
            '@n' => '31',
            '@piece' => 'k',
            '@from' => 'd3',
            '@to' => 'c2',
            '@display' => 'Kc2',
            '#' => '',
          ),
          32 =>
          array (
            '@n' => '32',
            '@piece' => 'P',
            '@from' => 'd2',
            '@to' => 'd3',
            '@state' => 'check',
            '@display' => 'd3+',
            '#' => '',
          ),
          33 =>
          array (
            '@n' => '33',
            '@piece' => 'k',
            '@from' => 'c2',
            '@to' => 'c1',
            '@display' => 'Kxc1',
            '#' => '',
          ),
          34 =>
          array (
            '@n' => '34',
            '@piece' => 'K',
            '@from' => 'e1',
            '@to' => 'g1',
            '@state' => 'checkmate',
            '@display' => 'O-O#',
            '#' => '',
          ),
        ),
        'end' => '1-0',
      ),
    ),
    7 =>
    array (
      'info' =>
      array (
        'Event' => 'Free Game',
        'Site' => '008: ??',
        'Date' =>
        array (
          '@Year' => '1997',
          '#' => '',
        ),
        'Round' => '1',
        'White' => 'Ruscalleda,Francesc',
        'Black' => 'Rosenboom,Manfred',
        'Result' => '*',
      ),
      'moves' =>
      array (
        'move' =>
        array (
          0 =>
          array (
            '@n' => '0',
            '@piece' => 'N',
            '@from' => 'g1',
            '@to' => 'f3',
            '@display' => 'Nf3',
            '#' => '',
          ),
          1 =>
          array (
            '@n' => '1',
            '@piece' => 'p',
            '@from' => 'd7',
            '@to' => 'd5',
            '@display' => 'd5',
            '#' => '',
          ),
          2 =>
          array (
            '@n' => '2',
            '@piece' => 'P',
            '@from' => 'c2',
            '@to' => 'c4',
            '@display' => 'c4',
            '#' => '',
          ),
          3 =>
          array (
            '@n' => '3',
            '@piece' => 'p',
            '@from' => 'd5',
            '@to' => 'c4',
            '@display' => 'dxc4',
            '#' => '',
          ),
          4 =>
          array (
            '@n' => '4',
            '@piece' => 'N',
            '@from' => 'b1',
            '@to' => 'a3',
            '@display' => 'Na3',
            '#' => '',
          ),
          5 =>
          array (
            '@n' => '5',
            '@piece' => 'p',
            '@from' => 'c7',
            '@to' => 'c5',
            '@display' => 'c5',
            '#' => '',
          ),
          6 =>
          array (
            '@n' => '6',
            '@piece' => 'N',
            '@from' => 'a3',
            '@to' => 'c4',
            '@display' => 'Nxc4',
            '#' => '',
          ),
          7 =>
          array (
            '@n' => '7',
            '@piece' => 'n',
            '@from' => 'b8',
            '@to' => 'c6',
            '@display' => 'Nc6',
            '#' => '',
          ),
          8 =>
          array (
            '@n' => '8',
            '@piece' => 'P',
            '@from' => 'b2',
            '@to' => 'b3',
            '@display' => 'b3',
            '#' => '',
          ),
          9 =>
          array (
            '@n' => '9',
            '@piece' => 'p',
            '@from' => 'e7',
            '@to' => 'e5',
            '@display' => 'e5',
            '#' => '',
          ),
          10 =>
          array (
            '@n' => '10',
            '@piece' => 'B',
            '@from' => 'c1',
            '@to' => 'b2',
            '@display' => 'Bb2',
            '#' => '',
          ),
          11 =>
          array (
            '@n' => '11',
            '@piece' => 'p',
            '@from' => 'f7',
            '@to' => 'f6',
            '@display' => 'f6',
            '#' => '',
          ),
          12 =>
          array (
            '@n' => '12',
            '@piece' => 'P',
            '@from' => 'g2',
            '@to' => 'g3',
            '@display' => 'g3',
            '#' => '',
          ),
          13 =>
          array (
            '@n' => '13',
            '@piece' => 'n',
            '@from' => 'g8',
            '@to' => 'e7',
            '@display' => 'Nge7',
            '#' => '',
          ),
          14 =>
          array (
            '@n' => '14',
            '@piece' => 'B',
            '@from' => 'f1',
            '@to' => 'g2',
            '@display' => 'Bg2',
            '#' => '',
          ),
          15 =>
          array (
            '@n' => '15',
            '@piece' => 'n',
            '@from' => 'e7',
            '@to' => 'f5',
            '@display' => 'Nf5',
            '#' => '',
          ),
          16 =>
          array (
            '@n' => '16',
            '@piece' => 'K',
            '@from' => 'e1',
            '@to' => 'g1',
            '@display' => 'O-O',
            '#' => '',
          ),
          17 =>
          array (
            '@n' => '17',
            '@piece' => 'b',
            '@from' => 'f8',
            '@to' => 'e7',
            '@display' => 'Be7',
            '#' => '',
          ),
          18 =>
          array (
            '@n' => '18',
            '@piece' => 'P',
            '@from' => 'd2',
            '@to' => 'd3',
            '@display' => 'd3',
            '#' => '',
          ),
          19 =>
          array (
            '@n' => '19',
            '@piece' => 'b',
            '@from' => 'c8',
            '@to' => 'e6',
            '@display' => 'Be6',
            '#' => '',
          ),
          20 =>
          array (
            '@n' => '20',
            '@piece' => 'P',
            '@from' => 'e2',
            '@to' => 'e3',
            '@display' => 'e3',
            '#' => '',
          ),
          21 =>
          array (
            '@n' => '21',
            '@piece' => 'q',
            '@from' => 'd8',
            '@to' => 'd7',
            '@display' => 'Qd7',
            '#' => '',
          ),
          22 =>
          array (
            '@n' => '22',
            '@piece' => 'N',
            '@from' => 'f3',
            '@to' => 'e1',
            '@display' => 'Ne1',
            '#' => '',
          ),
          23 =>
          array (
            '@n' => '23',
            '@piece' => 'k',
            '@from' => 'e8',
            '@to' => 'g8',
            '@display' => 'O-O',
            '#' => '',
          ),
          24 =>
          array (
            '@n' => '24',
            '@piece' => 'R',
            '@from' => 'a1',
            '@to' => 'c1',
            '@display' => 'Rc1',
            '#' => '',
          ),
          25 =>
          array (
            '@n' => '25',
            '@piece' => 'r',
            '@from' => 'a8',
            '@to' => 'c8',
            '@display' => 'Rac8',
            '#' => '',
          ),
        ),
        'end' => '*',
      ),
    ),
    8 =>
    array (
      'info' =>
      array (
        'Site' => '009: Th Trio 61 IECC (1)',
        'Date' =>
        array (
          '@Year' => '1997',
          '#' => '',
        ),
        'White' => 'Hucks, Lew',
        'Black' => 'Mongle, John H.',
        'Result' => '*',
        'PlyCount' => '63',
      ),
      'moves' =>
      array (
        'move' =>
        array (
          0 =>
          array (
            '@n' => '0',
            '@piece' => 'P',
            '@from' => 'd2',
            '@to' => 'd4',
            '@display' => 'd4',
            '#' => '',
          ),
          1 =>
          array (
            '@n' => '1',
            '@piece' => 'p',
            '@from' => 'd7',
            '@to' => 'd5',
            '@display' => 'd5',
            '#' => '',
          ),
          2 =>
          array (
            '@n' => '2',
            '@piece' => 'P',
            '@from' => 'e2',
            '@to' => 'e4',
            '@display' => 'e4',
            '#' => '',
          ),
          3 =>
          array (
            '@n' => '3',
            '@piece' => 'p',
            '@from' => 'd5',
            '@to' => 'e4',
            '@display' => 'dxe4',
            '#' => '',
          ),
          4 =>
          array (
            '@n' => '4',
            '@piece' => 'N',
            '@from' => 'b1',
            '@to' => 'c3',
            '@display' => 'Nc3',
            '#' => '',
          ),
          5 =>
          array (
            '@n' => '5',
            '@piece' => 'n',
            '@from' => 'g8',
            '@to' => 'f6',
            '@display' => 'Nf6',
            '#' => '',
          ),
          6 =>
          array (
            '@n' => '6',
            '@piece' => 'P',
            '@from' => 'f2',
            '@to' => 'f3',
            '@display' => 'f3',
            '#' => '',
          ),
          7 =>
          array (
            '@n' => '7',
            '@piece' => 'p',
            '@from' => 'e4',
            '@to' => 'f3',
            '@display' => 'exf3',
            '#' => '',
          ),
          8 =>
          array (
            '@n' => '8',
            '@piece' => 'N',
            '@from' => 'g1',
            '@to' => 'f3',
            '@display' => 'Nxf3',
            '#' => '',
          ),
          9 =>
          array (
            '@n' => '9',
            '@piece' => 'p',
            '@from' => 'g7',
            '@to' => 'g6',
            '@display' => 'g6',
            '#' => '',
          ),
          10 =>
          array (
            '@n' => '10',
            '@piece' => 'B',
            '@from' => 'f1',
            '@to' => 'c4',
            '@display' => 'Bc4',
            '#' => '',
          ),
          11 =>
          array (
            '@n' => '11',
            '@piece' => 'b',
            '@from' => 'f8',
            '@to' => 'g7',
            '@display' => 'Bg7',
            '#' => '',
          ),
          12 =>
          array (
            '@n' => '12',
            '@piece' => 'K',
            '@from' => 'e1',
            '@to' => 'g1',
            '@display' => 'O-O',
            '#' => '',
          ),
          13 =>
          array (
            '@n' => '13',
            '@piece' => 'k',
            '@from' => 'e8',
            '@to' => 'g8',
            '@display' => 'O-O',
            '#' => '',
          ),
          14 =>
          array (
            '@n' => '14',
            '@piece' => 'Q',
            '@from' => 'd1',
            '@to' => 'e1',
            '@display' => 'Qe1',
            '#' => '',
          ),
          15 =>
          array (
            '@n' => '15',
            '@piece' => 'n',
            '@from' => 'b8',
            '@to' => 'c6',
            '@display' => 'Nc6',
            '#' => '',
          ),
          16 =>
          array (
            '@n' => '16',
            '@piece' => 'Q',
            '@from' => 'e1',
            '@to' => 'h4',
            '@display' => 'Qh4',
            '#' => '',
          ),
          17 =>
          array (
            '@n' => '17',
            '@piece' => 'n',
            '@from' => 'f6',
            '@to' => 'g4',
            '@display' => 'Ng4',
            '#' => '',
          ),
          18 =>
          array (
            '@n' => '18',
            '@piece' => 'B',
            '@from' => 'c1',
            '@to' => 'f4',
            '@display' => 'Bf4',
            '#' => '',
          ),
          19 =>
          array (
            '@n' => '19',
            '@piece' => 'b',
            '@from' => 'g7',
            '@to' => 'f6',
            '@display' => 'Bf6',
            '#' => '',
          ),
          20 =>
          array (
            '@n' => '20',
            '@piece' => 'Q',
            '@from' => 'h4',
            '@to' => 'g3',
            '@display' => 'Qg3',
            '#' => '',
          ),
          21 =>
          array (
            '@n' => '21',
            '@piece' => 'n',
            '@from' => 'c6',
            '@to' => 'd4',
            '@display' => 'Nxd4',
            '#' => '',
          ),
          22 =>
          array (
            '@n' => '22',
            '@piece' => 'R',
            '@from' => 'a1',
            '@to' => 'd1',
            '@display' => 'Rad1',
            '#' => '',
          ),
          23 =>
          array (
            '@n' => '23',
            '@piece' => 'p',
            '@from' => 'c7',
            '@to' => 'c6',
            '@display' => 'c6',
            '#' => '',
          ),
          24 =>
          array (
            '@n' => '24',
            '@piece' => 'K',
            '@from' => 'g1',
            '@to' => 'h1',
            '@display' => 'Kh1',
            '#' => '',
          ),
          25 =>
          array (
            '@n' => '25',
            '@piece' => 'q',
            '@from' => 'd8',
            '@to' => 'b6',
            '@display' => 'Qb6',
            '#' => '',
          ),
          26 =>
          array (
            '@n' => '26',
            '@piece' => 'N',
            '@from' => 'c3',
            '@to' => 'e4',
            '@display' => 'Ne4',
            '#' => '',
          ),
          27 =>
          array (
            '@n' => '27',
            '@piece' => 'n',
            '@from' => 'd4',
            '@to' => 'c2',
            '@display' => 'Nxc2',
            '#' => '',
          ),
          28 =>
          array (
            '@n' => '28',
            '@piece' => 'N',
            '@from' => 'f3',
            '@to' => 'g5',
            '@display' => 'Nfg5',
            '#' => '',
          ),
          29 =>
          array (
            '@n' => '29',
            '@piece' => 'b',
            '@from' => 'c8',
            '@to' => 'f5',
            '@display' => 'Bf5',
            '#' => '',
          ),
          30 =>
          array (
            '@n' => '30',
            '@piece' => 'P',
            '@from' => 'h2',
            '@to' => 'h3',
            '@display' => 'h3',
            '#' => '',
          ),
          31 =>
          array (
            '@n' => '31',
            '@piece' => 'n',
            '@from' => 'g4',
            '@to' => 'e3',
            '@display' => 'Nge3',
            '#' => '',
          ),
          32 =>
          array (
            '@n' => '32',
            '@piece' => 'Q',
            '@from' => 'g3',
            '@to' => 'h4',
            '@display' => 'Qh4',
            '#' => '',
          ),
          33 =>
          array (
            '@n' => '33',
            '@piece' => 'p',
            '@from' => 'h7',
            '@to' => 'h5',
            '@display' => 'h5',
            '#' => '',
          ),
          34 =>
          array (
            '@n' => '34',
            '@piece' => 'N',
            '@from' => 'e4',
            '@to' => 'f6',
            '@state' => 'check',
            '@display' => 'Nxf6+',
            '#' => '',
          ),
          35 =>
          array (
            '@n' => '35',
            '@piece' => 'p',
            '@from' => 'e7',
            '@to' => 'f6',
            '@display' => 'exf6',
            '#' => '',
          ),
          36 =>
          array (
            '@n' => '36',
            '@piece' => 'B',
            '@from' => 'c4',
            '@to' => 'f7',
            '@state' => 'check',
            '@display' => 'Bxf7+',
            '#' => '',
          ),
          37 =>
          array (
            '@n' => '37',
            '@piece' => 'r',
            '@from' => 'f8',
            '@to' => 'f7',
            '@display' => 'Rxf7',
            '#' => '',
          ),
          38 =>
          array (
            '@n' => '38',
            '@piece' => 'N',
            '@from' => 'g5',
            '@to' => 'f7',
            '@display' => 'Nxf7',
            '#' => '',
          ),
          39 =>
          array (
            '@n' => '39',
            '@piece' => 'k',
            '@from' => 'g8',
            '@to' => 'f7',
            '@display' => 'Kxf7',
            '#' => '',
          ),
          40 =>
          array (
            '@n' => '40',
            '@piece' => 'B',
            '@from' => 'f4',
            '@to' => 'e3',
            '@display' => 'Bxe3',
            '#' => '',
          ),
          41 =>
          array (
            '@n' => '41',
            '@piece' => 'n',
            '@from' => 'c2',
            '@to' => 'e3',
            '@display' => 'Nxe3',
            '#' => '',
          ),
          42 =>
          array (
            '@n' => '42',
            '@piece' => 'R',
            '@from' => 'f1',
            '@to' => 'f5',
            '@display' => 'Rxf5',
            '#' => '',
          ),
          43 =>
          array (
            '@n' => '43',
            '@piece' => 'n',
            '@from' => 'e3',
            '@to' => 'f5',
            '@display' => 'Nxf5',
            '#' => '',
          ),
          44 =>
          array (
            '@n' => '44',
            '@piece' => 'Q',
            '@from' => 'h4',
            '@to' => 'c4',
            '@state' => 'check',
            '@display' => 'Qc4+',
            '#' => '',
          ),
          45 =>
          array (
            '@n' => '45',
            '@piece' => 'k',
            '@from' => 'f7',
            '@to' => 'e7',
            '@display' => 'Ke7',
            '#' => '',
          ),
          46 =>
          array (
            '@n' => '46',
            '@piece' => 'Q',
            '@from' => 'c4',
            '@to' => 'e4',
            '@state' => 'check',
            '@display' => 'Qe4+',
            '#' => '',
          ),
          47 =>
          array (
            '@n' => '47',
            '@piece' => 'k',
            '@from' => 'e7',
            '@to' => 'f8',
            '@display' => 'Kf8',
            '#' => '',
          ),
          48 =>
          array (
            '@n' => '48',
            '@piece' => 'Q',
            '@from' => 'e4',
            '@to' => 'f4',
            '@display' => 'Qf4',
            '#' => '',
          ),
          49 =>
          array (
            '@n' => '49',
            '@piece' => 'q',
            '@from' => 'b6',
            '@to' => 'b2',
            '@display' => 'Qxb2',
            '#' => '',
          ),
          50 =>
          array (
            '@n' => '50',
            '@piece' => 'R',
            '@from' => 'd1',
            '@to' => 'd7',
            '@display' => 'Rd7',
            '#' => '',
          ),
          51 =>
          array (
            '@n' => '51',
            '@piece' => 'r',
            '@from' => 'a8',
            '@to' => 'c8',
            '@display' => 'Rc8',
            '#' => '',
          ),
          52 =>
          array (
            '@n' => '52',
            '@piece' => 'P',
            '@from' => 'g2',
            '@to' => 'g4',
            '@display' => 'g4',
            '#' => '',
          ),
          53 =>
          array (
            '@n' => '53',
            '@piece' => 'r',
            '@from' => 'c8',
            '@to' => 'e8',
            '@display' => 'Re8',
            '#' => '',
          ),
          54 =>
          array (
            '@n' => '54',
            '@piece' => 'R',
            '@from' => 'd7',
            '@to' => 'd1',
            '@display' => 'Rd1',
            '#' => '',
          ),
          55 =>
          array (
            '@n' => '55',
            '@piece' => 'n',
            '@from' => 'f5',
            '@to' => 'h4',
            '@display' => 'Nh4',
            '#' => '',
          ),
          56 =>
          array (
            '@n' => '56',
            '@piece' => 'R',
            '@from' => 'd1',
            '@to' => 'g1',
            '@display' => 'Rg1',
            'move' =>
            array (
              0 =>
              array (
                '@n' => '0',
                '@piece' => 'Q',
                '@from' => 'f4',
                '@to' => 'f1',
                '@display' => 'Qf1',
                '#' => '',
              ),
              1 =>
              array (
                '@n' => '1',
                '@piece' => 'r',
                '@from' => 'e8',
                '@to' => 'e2',
                '@NAG' => '19',
                '@display' => 'Re2',
                '#' => '',
              ),
              2 =>
              array (
                '@n' => '0',
                '@piece' => 'Q',
                '@from' => 'f4',
                '@to' => 'd6',
                '@state' => 'check',
                '@display' => 'Qd6+',
                '#' => '',
              ),
              3 =>
              array (
                '@n' => '1',
                '@piece' => 'k',
                '@from' => 'f8',
                '@to' => 'g8',
                '@display' => 'Kg8',
                '#' => '',
              ),
              4 =>
              array (
                '@n' => '2',
                '@piece' => 'R',
                '@from' => 'd1',
                '@to' => 'g1',
                '@display' => 'Rg1',
                '#' => '',
              ),
              5 =>
              array (
                '@n' => '3',
                '@piece' => 'q',
                '@from' => 'b2',
                '@to' => 'a2',
                '@display' => 'Qxa2',
                '#' => '',
              ),
              6 =>
              array (
                '@n' => '4',
                '@piece' => 'P',
                '@from' => 'g4',
                '@to' => 'h5',
                '@display' => 'gxh5',
                '#' => '',
              ),
              7 =>
              array (
                '@n' => '5',
                '@piece' => 'q',
                '@from' => 'a2',
                '@to' => 'b3',
                '@display' => 'Qb3',
                '#' => '',
              ),
              8 =>
              array (
                '@n' => '6',
                '@piece' => 'R',
                '@from' => 'g1',
                '@to' => 'g3',
                '@display' => 'Rg3',
                '#' => '',
              ),
              9 =>
              array (
                '@n' => '7',
                '@piece' => 'r',
                '@from' => 'e8',
                '@to' => 'e1',
                '@state' => 'check',
                '@display' => 'Re1+',
                '#' => '',
              ),
              10 =>
              array (
                '@n' => '8',
                '@piece' => 'K',
                '@from' => 'h1',
                '@to' => 'h2',
                '@NAG' => '19',
                '@display' => 'Kh2',
                '#' => '',
              ),
            ),
          ),
          57 =>
          array (
            '@n' => '0',
            '@piece' => 'q',
            '@from' => 'b2',
            '@to' => 'c3',
            '@display' => 'Qc3',
            'move' =>
            array (
              0 =>
              array (
                '@n' => '0',
                '@piece' => 'r',
                '@from' => 'e8',
                '@to' => 'e1',
                '@display' => 'Re1',
                '#' => '',
              ),
              1 =>
              array (
                '@n' => '1',
                '@piece' => 'Q',
                '@from' => 'f4',
                '@to' => 'b8',
                '@state' => 'check',
                '@display' => 'Qb8+',
                'move' =>
                array (
                  0 =>
                  array (
                    '@n' => '0',
                    '@piece' => 'Q',
                    '@from' => 'f4',
                    '@to' => 'g3',
                    '@display' => 'Qg3',
                    '#' => '',
                  ),
                  1 =>
                  array (
                    '@n' => '1',
                    '@piece' => 'r',
                    '@from' => 'e1',
                    '@to' => 'g1',
                    '@state' => 'check',
                    '@NAG' => '19',
                    '@display' => 'Rxg1+',
                    '#' => '',
                  ),
                  2 =>
                  array (
                    '@n' => '0',
                    '@piece' => 'Q',
                    '@from' => 'f4',
                    '@to' => 'd6',
                    '@state' => 'check',
                    '@display' => 'Qd6+',
                    '#' => '',
                  ),
                  3 =>
                  array (
                    '@n' => '1',
                    '@piece' => 'k',
                    '@from' => 'f8',
                    '@to' => 'g7',
                    '@display' => 'Kg7',
                    'move' =>
                    array (
                      0 =>
                      array (
                        '@n' => '0',
                        '@piece' => 'k',
                        '@from' => 'f8',
                        '@to' => 'g8',
                        '@display' => 'Kg8',
                        '#' => '',
                      ),
                      1 =>
                      array (
                        '@n' => '1',
                        '@piece' => 'Q',
                        '@from' => 'd6',
                        '@to' => 'b8',
                        '@state' => 'check',
                        '@display' => 'Qb8+',
                        '#' => '',
                      ),
                      2 =>
                      array (
                        '@n' => '2',
                        '@piece' => 'k',
                        '@from' => 'g8',
                        '@to' => 'h7',
                        '@display' => 'Kh7',
                        '#' => '',
                      ),
                      3 =>
                      array (
                        '@n' => '3',
                        '@piece' => 'Q',
                        '@from' => 'b8',
                        '@to' => 'c7',
                        '@state' => 'check',
                        '@display' => 'Qc7+',
                        '#' => '',
                      ),
                      4 =>
                      array (
                        '@n' => '4',
                        '@piece' => 'k',
                        '@from' => 'h7',
                        '@to' => 'h6',
                        '@NAG' => '19',
                        '@display' => 'Kh6',
                        '#' => '',
                      ),
                    ),
                  ),
                  4 =>
                  array (
                    '@n' => '0',
                    '@piece' => 'Q',
                    '@from' => 'd6',
                    '@to' => 'c7',
                    '@state' => 'check',
                    '@NAG' => '19',
                    '@display' => 'Qc7+',
                    '#' => '',
                  ),
                ),
              ),
              2 =>
              array (
                '@n' => '0',
                '@piece' => 'k',
                '@from' => 'f8',
                '@to' => 'g7',
                '@display' => 'Kg7',
                'move' =>
                array (
                  0 =>
                  array (
                    '@n' => '0',
                    '@piece' => 'k',
                    '@from' => 'f8',
                    '@to' => 'f7',
                    '@display' => 'Kf7',
                    '#' => '',
                  ),
                  1 =>
                  array (
                    '@n' => '1',
                    '@piece' => 'Q',
                    '@from' => 'b8',
                    '@to' => 'c7',
                    '@state' => 'check',
                    '@display' => 'Qc7+',
                    '#' => '',
                  ),
                  2 =>
                  array (
                    '@n' => '2',
                    '@piece' => 'k',
                    '@from' => 'f7',
                    '@to' => 'g8',
                    '@NAG' => '19',
                    '@display' => 'Kg8',
                    '#' => '',
                  ),
                ),
              ),
              3 =>
              array (
                '@n' => '0',
                '@piece' => 'Q',
                '@from' => 'b8',
                '@to' => 'c7',
                '@state' => 'check',
                '@display' => 'Qc7+',
                '#' => '',
              ),
              4 =>
              array (
                '@n' => '1',
                '@piece' => 'k',
                '@from' => 'g7',
                '@to' => 'h6',
                '@display' => 'Kh6',
                '#' => '',
              ),
              5 =>
              array (
                '@n' => '2',
                '@piece' => 'Q',
                '@from' => 'c7',
                '@to' => 'f4',
                '@state' => 'check',
                '@display' => 'Qf4+',
                '#' => '',
              ),
              6 =>
              array (
                '@n' => '3',
                '@piece' => 'p',
                '@from' => 'g6',
                '@to' => 'g5',
                '@display' => 'g5',
                '#' => '',
              ),
              7 =>
              array (
                '@n' => '4',
                '@piece' => 'Q',
                '@from' => 'f4',
                '@to' => 'g3',
                '@display' => 'Qg3',
                '#' => '',
              ),
              8 =>
              array (
                '@n' => '5',
                '@piece' => 'r',
                '@from' => 'e1',
                '@to' => 'c1',
                '@display' => 'Rc1',
                'move' =>
                array (
                  0 =>
                  array (
                    '@n' => '0',
                    '@piece' => 'r',
                    '@from' => 'e1',
                    '@to' => 'g1',
                    '@state' => 'check',
                    '@display' => 'Rxg1+',
                    '#' => '',
                  ),
                  1 =>
                  array (
                    '@n' => '1',
                    '@piece' => 'K',
                    '@from' => 'h1',
                    '@to' => 'g1',
                    '@NAG' => '19',
                    '@display' => 'Kxg1',
                    '#' => '',
                  ),
                ),
              ),
              9 =>
              array (
                '@n' => '0',
                '@piece' => 'P',
                '@from' => 'g4',
                '@to' => 'h5',
                '@display' => 'gxh5',
                '#' => '',
              ),
              10 =>
              array (
                '@n' => '1',
                '@piece' => 'r',
                '@from' => 'c1',
                '@to' => 'c3',
                '@display' => 'Rc3',
                '#' => '',
              ),
              11 =>
              array (
                '@n' => '2',
                '@piece' => 'Q',
                '@from' => 'g3',
                '@to' => 'h2',
                '@display' => 'Qh2',
                '#' => '',
              ),
              12 =>
              array (
                '@n' => '3',
                '@piece' => 'q',
                '@from' => 'b2',
                '@to' => 'h2',
                '@state' => 'check',
                '@display' => 'Qxh2+',
                '#' => '',
              ),
              13 =>
              array (
                '@n' => '4',
                '@piece' => 'K',
                '@from' => 'h1',
                '@to' => 'h2',
                '@NAG' => '19',
                '@display' => 'Kxh2',
                '#' => '',
              ),
            ),
          ),
          58 =>
          array (
            '@n' => '0',
            '@piece' => 'Q',
            '@from' => 'f4',
            '@to' => 'd6',
            '@state' => 'check',
            '@display' => 'Qd6+',
            '#' => '',
          ),
          59 =>
          array (
            '@n' => '1',
            '@piece' => 'k',
            '@from' => 'f8',
            '@to' => 'g8',
            '@display' => 'Kg8',
            '#' => '',
          ),
          60 =>
          array (
            '@n' => '2',
            '@piece' => 'Q',
            '@from' => 'd6',
            '@to' => 'g3',
            '@display' => 'Qg3',
            '#' => '',
          ),
          61 =>
          array (
            '@n' => '3',
            '@piece' => 'q',
            '@from' => 'c3',
            '@to' => 'g3',
            '@display' => 'Qxg3',
            '#' => '',
          ),
        ),
        'end' => '*',
      ),
    ),
    9 =>
    array (
      'info' =>
      array (
        'Event' => 'Prague',
        'Site' => '010: Prague',
        'Date' =>
        array (
          '@Year' => '1942',
          '@Month' => '12',
          '@Day' => '12',
          '#' => '',
        ),
        'Round' => '11',
        'White' => 'Alekhine, Alexander',
        'Black' => 'Junge, Klaus',
        'Result' => '1-0',
        'EventDate' =>
        array (
          '@Year' => '1942',
          '@Month' => '12',
          '@Day' => '12',
          '#' => '',
        ),
        'ECO' => 'E03',
        'PlyCount' => '57',
      ),
      'moves' =>
      array (
        'move' =>
        array (
          0 =>
          array (
            '@n' => '0',
            '@piece' => 'P',
            '@from' => 'd2',
            '@to' => 'd4',
            '@display' => 'd4',
            '#' => '',
          ),
          1 =>
          array (
            '@n' => '1',
            '@piece' => 'p',
            '@from' => 'd7',
            '@to' => 'd5',
            '@display' => 'd5',
            '#' => '',
          ),
          2 =>
          array (
            '@n' => '2',
            '@piece' => 'P',
            '@from' => 'c2',
            '@to' => 'c4',
            '@display' => 'c4',
            '#' => '',
          ),
          3 =>
          array (
            '@n' => '3',
            '@piece' => 'p',
            '@from' => 'e7',
            '@to' => 'e6',
            '@display' => 'e6',
            '#' => '',
          ),
          4 =>
          array (
            '@n' => '4',
            '@piece' => 'N',
            '@from' => 'g1',
            '@to' => 'f3',
            '@display' => 'Nf3',
            '#' => '',
          ),
          5 =>
          array (
            '@n' => '5',
            '@piece' => 'n',
            '@from' => 'g8',
            '@to' => 'f6',
            '@display' => 'Nf6',
            '#' => '',
          ),
          6 =>
          array (
            '@n' => '6',
            '@piece' => 'P',
            '@from' => 'g2',
            '@to' => 'g3',
            '@display' => 'g3',
            '#' => '',
          ),
          7 =>
          array (
            '@n' => '7',
            '@piece' => 'p',
            '@from' => 'd5',
            '@to' => 'c4',
            '@display' => 'dxc4',
            '#' => '',
          ),
          8 =>
          array (
            '@n' => '8',
            '@piece' => 'Q',
            '@from' => 'd1',
            '@to' => 'a4',
            '@state' => 'check',
            '@display' => 'Qa4+',
            '#' => '',
          ),
          9 =>
          array (
            '@n' => '9',
            '@piece' => 'n',
            '@from' => 'b8',
            '@to' => 'd7',
            '@display' => 'Nbd7',
            '#' => '',
          ),
          10 =>
          array (
            '@n' => '10',
            '@piece' => 'B',
            '@from' => 'f1',
            '@to' => 'g2',
            '@display' => 'Bg2',
            '#' => '',
          ),
          11 =>
          array (
            '@n' => '11',
            '@piece' => 'p',
            '@from' => 'a7',
            '@to' => 'a6',
            '@display' => 'a6',
            '#' => '',
          ),
          12 =>
          array (
            '@n' => '12',
            '@piece' => 'Q',
            '@from' => 'a4',
            '@to' => 'c4',
            '@display' => 'Qxc4',
            '#' => '',
          ),
          13 =>
          array (
            '@n' => '13',
            '@piece' => 'p',
            '@from' => 'b7',
            '@to' => 'b5',
            '@display' => 'b5',
            '#' => '',
          ),
          14 =>
          array (
            '@n' => '14',
            '@piece' => 'Q',
            '@from' => 'c4',
            '@to' => 'c6',
            '@display' => 'Qc6',
            '#' => '',
          ),
          15 =>
          array (
            '@n' => '15',
            '@piece' => 'r',
            '@from' => 'a8',
            '@to' => 'b8',
            '@display' => 'Rb8',
            '#' => '',
          ),
          16 =>
          array (
            '@n' => '16',
            '@piece' => 'K',
            '@from' => 'e1',
            '@to' => 'g1',
            '@display' => 'O-O',
            '#' => '',
          ),
          17 =>
          array (
            '@n' => '17',
            '@piece' => 'b',
            '@from' => 'c8',
            '@to' => 'b7',
            '@display' => 'Bb7',
            '#' => '',
          ),
          18 =>
          array (
            '@n' => '18',
            '@piece' => 'Q',
            '@from' => 'c6',
            '@to' => 'c2',
            '@display' => 'Qc2',
            '#' => '',
          ),
          19 =>
          array (
            '@n' => '19',
            '@piece' => 'p',
            '@from' => 'c7',
            '@to' => 'c5',
            '@display' => 'c5',
            '#' => '',
          ),
          20 =>
          array (
            '@n' => '20',
            '@piece' => 'P',
            '@from' => 'a2',
            '@to' => 'a4',
            '@annotation' => '!',
            '@display' => 'a4',
            '#' => '',
          ),
          21 =>
          array (
            '@n' => '21',
            '@piece' => 'b',
            '@from' => 'b7',
            '@to' => 'f3',
            '@annotation' => '?!',
            '@display' => 'Bxf3',
            '#' => '',
          ),
          22 =>
          array (
            '@n' => '22',
            '@piece' => 'B',
            '@from' => 'g2',
            '@to' => 'f3',
            '@display' => 'Bxf3',
            '#' => '',
          ),
          23 =>
          array (
            '@n' => '23',
            '@piece' => 'p',
            '@from' => 'c5',
            '@to' => 'd4',
            '@display' => 'cxd4',
            '#' => '',
          ),
          24 =>
          array (
            '@n' => '24',
            '@piece' => 'P',
            '@from' => 'a4',
            '@to' => 'b5',
            '@display' => 'axb5',
            '#' => '',
          ),
          25 =>
          array (
            '@n' => '25',
            '@piece' => 'p',
            '@from' => 'a6',
            '@to' => 'b5',
            '@display' => 'axb5',
            '#' => '',
          ),
          26 =>
          array (
            '@n' => '26',
            '@piece' => 'R',
            '@from' => 'f1',
            '@to' => 'd1',
            '@display' => 'Rd1',
            '#' => '',
          ),
          27 =>
          array (
            '@n' => '27',
            '@piece' => 'q',
            '@from' => 'd8',
            '@to' => 'b6',
            '@display' => 'Qb6',
            '#' => '',
          ),
          28 =>
          array (
            '@n' => '28',
            '@piece' => 'N',
            '@from' => 'b1',
            '@to' => 'd2',
            '@display' => 'Nd2',
            '#' => '',
          ),
          29 =>
          array (
            '@n' => '29',
            '@piece' => 'p',
            '@from' => 'e6',
            '@to' => 'e5',
            '@display' => 'e5',
            '#' => '',
          ),
          30 =>
          array (
            '@n' => '30',
            '@piece' => 'N',
            '@from' => 'd2',
            '@to' => 'b3',
            '@display' => 'Nb3',
            '#' => '',
          ),
          31 =>
          array (
            '@n' => '31',
            '@piece' => 'n',
            '@from' => 'd7',
            '@to' => 'c5',
            '@annotation' => '?!',
            '@display' => 'Nc5',
            '#' => '',
          ),
          32 =>
          array (
            '@n' => '32',
            '@piece' => 'N',
            '@from' => 'b3',
            '@to' => 'c5',
            '@display' => 'Nxc5',
            '#' => '',
          ),
          33 =>
          array (
            '@n' => '33',
            '@piece' => 'b',
            '@from' => 'f8',
            '@to' => 'c5',
            '@display' => 'Bxc5',
            '#' => '',
          ),
          34 =>
          array (
            '@n' => '34',
            '@piece' => 'R',
            '@from' => 'a1',
            '@to' => 'a6',
            '@annotation' => '!',
            '@display' => 'Ra6',
            '#' => '',
          ),
          35 =>
          array (
            '@n' => '35',
            '@piece' => 'q',
            '@from' => 'b6',
            '@to' => 'a6',
            '@display' => 'Qxa6',
            '#' => '',
          ),
          36 =>
          array (
            '@n' => '36',
            '@piece' => 'Q',
            '@from' => 'c2',
            '@to' => 'c5',
            '@display' => 'Qxc5',
            '#' => '',
          ),
          37 =>
          array (
            '@n' => '37',
            '@piece' => 'q',
            '@from' => 'a6',
            '@to' => 'e6',
            '@display' => 'Qe6',
            '#' => '',
          ),
          38 =>
          array (
            '@n' => '38',
            '@piece' => 'B',
            '@from' => 'f3',
            '@to' => 'c6',
            '@state' => 'check',
            '@display' => 'Bc6+',
            '#' => '',
          ),
          39 =>
          array (
            '@n' => '39',
            '@piece' => 'n',
            '@from' => 'f6',
            '@to' => 'd7',
            '@display' => 'Nd7',
            '#' => '',
          ),
          40 =>
          array (
            '@n' => '40',
            '@piece' => 'B',
            '@from' => 'c6',
            '@to' => 'd7',
            '@state' => 'check',
            '@display' => 'Bxd7+',
            '#' => '',
          ),
          41 =>
          array (
            '@n' => '41',
            '@piece' => 'k',
            '@from' => 'e8',
            '@to' => 'd7',
            '@display' => 'Kxd7',
            '#' => '',
          ),
          42 =>
          array (
            '@n' => '42',
            '@piece' => 'Q',
            '@from' => 'c5',
            '@to' => 'a7',
            '@state' => 'check',
            '@display' => 'Qa7+',
            '#' => '',
          ),
          43 =>
          array (
            '@n' => '43',
            '@piece' => 'k',
            '@from' => 'd7',
            '@to' => 'c6',
            '@annotation' => '??',
            '@display' => 'Kc6',
            '#' => '',
          ),
          44 =>
          array (
            '@n' => '44',
            '@piece' => 'B',
            '@from' => 'c1',
            '@to' => 'd2',
            '@display' => 'Bd2',
            '#' => '',
          ),
          45 =>
          array (
            '@n' => '45',
            '@piece' => 'r',
            '@from' => 'h8',
            '@to' => 'c8',
            '@annotation' => '?',
            '@display' => 'Rhc8',
            '#' => '',
          ),
          46 =>
          array (
            '@n' => '46',
            '@piece' => 'P',
            '@from' => 'e2',
            '@to' => 'e4',
            '@annotation' => '!',
            '@display' => 'e4',
            '#' => '',
          ),
          47 =>
          array (
            '@n' => '47',
            '@piece' => 'q',
            '@from' => 'e6',
            '@to' => 'b3',
            '@display' => 'Qb3',
            '#' => '',
          ),
          48 =>
          array (
            '@n' => '48',
            '@piece' => 'R',
            '@from' => 'd1',
            '@to' => 'a1',
            '@display' => 'Ra1',
            '#' => '',
          ),
          49 =>
          array (
            '@n' => '49',
            '@piece' => 'p',
            '@from' => 'b5',
            '@to' => 'b4',
            '@display' => 'b4',
            '#' => '',
          ),
          50 =>
          array (
            '@n' => '50',
            '@piece' => 'R',
            '@from' => 'a1',
            '@to' => 'a6',
            '@state' => 'check',
            '@display' => 'Ra6+',
            '#' => '',
          ),
          51 =>
          array (
            '@n' => '51',
            '@piece' => 'k',
            '@from' => 'c6',
            '@to' => 'b5',
            '@display' => 'Kb5',
            '#' => '',
          ),
          52 =>
          array (
            '@n' => '52',
            '@piece' => 'R',
            '@from' => 'a6',
            '@to' => 'a5',
            '@state' => 'check',
            '@display' => 'Ra5+',
            '#' => '',
          ),
          53 =>
          array (
            '@n' => '53',
            '@piece' => 'k',
            '@from' => 'b5',
            '@to' => 'c6',
            '@display' => 'Kc6',
            '#' => '',
          ),
          54 =>
          array (
            '@n' => '54',
            '@piece' => 'Q',
            '@from' => 'a7',
            '@to' => 'c5',
            '@state' => 'check',
            '@display' => 'Qc5+',
            '#' => '',
          ),
          55 =>
          array (
            '@n' => '55',
            '@piece' => 'k',
            '@from' => 'c6',
            '@to' => 'd7',
            '@display' => 'Kd7',
            '#' => '',
          ),
          56 =>
          array (
            '@n' => '56',
            '@piece' => 'R',
            '@from' => 'a5',
            '@to' => 'a7',
            '@state' => 'check',
            '@display' => 'Ra7+',
            '#' => '',
          ),
        ),
        'end' => '1-0',
      ),
    ),
    10 =>
    array (
      'info' =>
      array (
        'Event' => 'ICC r 3 3',
        'Site' => '011: Internet Chess Club',
        'Date' =>
        array (
          '@Year' => '1998',
          '@Month' => '01',
          '@Day' => '01',
          '#' => '',
        ),
        'White' =>
        array (
          '@Elo' => '1031',
          '#' => 'BabyBach',
        ),
        'Black' =>
        array (
          '@Elo' => '1111',
          '#' => 'ZenRhino',
        ),
        'Result' => '0-1',
      ),
      'moves' =>
      array (
        'move' =>
        array (
          0 =>
          array (
            '@n' => '0',
            '@piece' => 'N',
            '@from' => 'b1',
            '@to' => 'c3',
            '@display' => 'Nc3',
            '#' => '',
          ),
          1 =>
          array (
            '@n' => '1',
            '@piece' => 'p',
            '@from' => 'e7',
            '@to' => 'e5',
            '@display' => 'e5',
            '#' => '',
          ),
          2 =>
          array (
            '@n' => '2',
            '@piece' => 'N',
            '@from' => 'c3',
            '@to' => 'b1',
            '@display' => 'Nb1',
            '#' => '',
          ),
          3 =>
          array (
            '@n' => '3',
            '@piece' => 'p',
            '@from' => 'd7',
            '@to' => 'd5',
            '@display' => 'd5',
            '#' => '',
          ),
          4 =>
          array (
            '@n' => '4',
            '@piece' => 'P',
            '@from' => 'a2',
            '@to' => 'a3',
            '@display' => 'a3',
            '#' => '',
          ),
          5 =>
          array (
            '@n' => '5',
            '@piece' => 'p',
            '@from' => 'c7',
            '@to' => 'c5',
            '@display' => 'c5',
            '#' => '',
          ),
          6 =>
          array (
            '@n' => '6',
            '@piece' => 'P',
            '@from' => 'g2',
            '@to' => 'g3',
            '@display' => 'g3',
            '#' => '',
          ),
          7 =>
          array (
            '@n' => '7',
            '@piece' => 'p',
            '@from' => 'f7',
            '@to' => 'f5',
            '@display' => 'f5',
            '#' => '',
          ),
          8 =>
          array (
            '@n' => '8',
            '@piece' => 'B',
            '@from' => 'f1',
            '@to' => 'g2',
            '@display' => 'Bg2',
            '#' => '',
          ),
          9 =>
          array (
            '@n' => '9',
            '@piece' => 'n',
            '@from' => 'g8',
            '@to' => 'f6',
            '@display' => 'Nf6',
            '#' => '',
          ),
          10 =>
          array (
            '@n' => '10',
            '@piece' => 'N',
            '@from' => 'b1',
            '@to' => 'c3',
            '@display' => 'Nc3',
            '#' => '',
          ),
          11 =>
          array (
            '@n' => '11',
            '@piece' => 'n',
            '@from' => 'b8',
            '@to' => 'c6',
            '@display' => 'Nc6',
            '#' => '',
          ),
          12 =>
          array (
            '@n' => '12',
            '@piece' => 'N',
            '@from' => 'g1',
            '@to' => 'h3',
            '@display' => 'Nh3',
            '#' => '',
          ),
          13 =>
          array (
            '@n' => '13',
            '@piece' => 'b',
            '@from' => 'c8',
            '@to' => 'e6',
            '@display' => 'Be6',
            '#' => '',
          ),
          14 =>
          array (
            '@n' => '14',
            '@piece' => 'N',
            '@from' => 'c3',
            '@to' => 'b5',
            '@display' => 'Nb5',
            '#' => '',
          ),
          15 =>
          array (
            '@n' => '15',
            '@piece' => 'b',
            '@from' => 'f8',
            '@to' => 'd6',
            '@display' => 'Bd6',
            '#' => '',
          ),
          16 =>
          array (
            '@n' => '16',
            '@piece' => 'B',
            '@from' => 'g2',
            '@to' => 'f1',
            '@display' => 'Bf1',
            '#' => '',
          ),
          17 =>
          array (
            '@n' => '17',
            '@piece' => 'k',
            '@from' => 'e8',
            '@to' => 'g8',
            '@display' => 'O-O',
            '#' => '',
          ),
          18 =>
          array (
            '@n' => '18',
            '@piece' => 'N',
            '@from' => 'h3',
            '@to' => 'g5',
            '@display' => 'Ng5',
            '#' => '',
          ),
          19 =>
          array (
            '@n' => '19',
            '@piece' => 'r',
            '@from' => 'f8',
            '@to' => 'e8',
            '@display' => 'Re8',
            '#' => '',
          ),
          20 =>
          array (
            '@n' => '20',
            '@piece' => 'N',
            '@from' => 'g5',
            '@to' => 'h3',
            '@display' => 'Nh3',
            '#' => '',
          ),
          21 =>
          array (
            '@n' => '21',
            '@piece' => 'n',
            '@from' => 'f6',
            '@to' => 'e4',
            '@display' => 'Ne4',
            '#' => '',
          ),
          22 =>
          array (
            '@n' => '22',
            '@piece' => 'P',
            '@from' => 'b2',
            '@to' => 'b3',
            '@display' => 'b3',
            '#' => '',
          ),
          23 =>
          array (
            '@n' => '23',
            '@piece' => 'p',
            '@from' => 'f5',
            '@to' => 'f4',
            '@display' => 'f4',
            '#' => '',
          ),
          24 =>
          array (
            '@n' => '24',
            '@piece' => 'P',
            '@from' => 'g3',
            '@to' => 'f4',
            '@display' => 'gxf4',
            '#' => '',
          ),
          25 =>
          array (
            '@n' => '25',
            '@piece' => 'p',
            '@from' => 'e5',
            '@to' => 'f4',
            '@display' => 'exf4',
            '#' => '',
          ),
          26 =>
          array (
            '@n' => '26',
            '@piece' => 'P',
            '@from' => 'd2',
            '@to' => 'd4',
            '@display' => 'd4',
            '#' => '',
          ),
          27 =>
          array (
            '@n' => '27',
            '@piece' => 'n',
            '@from' => 'c6',
            '@to' => 'd4',
            '@display' => 'Nxd4',
            '#' => '',
          ),
          28 =>
          array (
            '@n' => '28',
            '@piece' => 'N',
            '@from' => 'b5',
            '@to' => 'd6',
            '@display' => 'Nxd6',
            '#' => '',
          ),
          29 =>
          array (
            '@n' => '29',
            '@piece' => 'q',
            '@from' => 'd8',
            '@to' => 'd6',
            '@display' => 'Qxd6',
            '#' => '',
          ),
          30 =>
          array (
            '@n' => '30',
            '@piece' => 'B',
            '@from' => 'c1',
            '@to' => 'f4',
            '@display' => 'Bxf4',
            '#' => '',
          ),
          31 =>
          array (
            '@n' => '31',
            '@piece' => 'q',
            '@from' => 'd6',
            '@to' => 'a6',
            '@display' => 'Qa6',
            '#' => '',
          ),
          32 =>
          array (
            '@n' => '32',
            '@piece' => 'B',
            '@from' => 'f4',
            '@to' => 'c1',
            '@display' => 'Bc1',
            '#' => '',
          ),
          33 =>
          array (
            '@n' => '33',
            '@piece' => 'b',
            '@from' => 'e6',
            '@to' => 'h3',
            '@display' => 'Bxh3',
            '#' => '',
          ),
          34 =>
          array (
            '@n' => '34',
            '@piece' => 'B',
            '@from' => 'f1',
            '@to' => 'h3',
            '@display' => 'Bxh3',
            '#' => '',
          ),
          35 =>
          array (
            '@n' => '35',
            '@piece' => 'r',
            '@from' => 'e8',
            '@to' => 'f8',
            '@display' => 'Rf8',
            '#' => '',
          ),
          36 =>
          array (
            '@n' => '36',
            '@piece' => 'R',
            '@from' => 'h1',
            '@to' => 'f1',
            '@display' => 'Rf1',
            '#' => '',
          ),
          37 =>
          array (
            '@n' => '37',
            '@piece' => 'q',
            '@from' => 'a6',
            '@to' => 'a5',
            '@state' => 'check',
            '@display' => 'Qa5+',
            '#' => '',
          ),
          38 =>
          array (
            '@n' => '38',
            '@piece' => 'B',
            '@from' => 'c1',
            '@to' => 'd2',
            '@display' => 'Bd2',
            '#' => '',
          ),
          39 =>
          array (
            '@n' => '39',
            '@piece' => 'n',
            '@from' => 'e4',
            '@to' => 'd2',
            '@display' => 'Nxd2',
            '#' => '',
          ),
          40 =>
          array (
            '@n' => '40',
            '@piece' => 'R',
            '@from' => 'f1',
            '@to' => 'h1',
            '@display' => 'Rh1',
            '#' => '',
          ),
          41 =>
          array (
            '@n' => '41',
            '@piece' => 'n',
            '@from' => 'd2',
            '@to' => 'b3',
            '@state' => 'check',
            '@display' => 'Nd2xb3+',
            '#' => '',
          ),
          42 =>
          array (
            '@n' => '42',
            '@piece' => 'K',
            '@from' => 'e1',
            '@to' => 'f1',
            '@display' => 'Kf1',
            '#' => '',
          ),
          43 =>
          array (
            '@n' => '43',
            '@piece' => 'n',
            '@from' => 'b3',
            '@to' => 'a1',
            '@display' => 'Nxa1',
            '#' => '',
          ),
          44 =>
          array (
            '@n' => '44',
            '@piece' => 'Q',
            '@from' => 'd1',
            '@to' => 'a1',
            '@display' => 'Qxa1',
            '#' => '',
          ),
          45 =>
          array (
            '@n' => '45',
            '@piece' => 'q',
            '@from' => 'a5',
            '@to' => 'd2',
            '@display' => 'Qd2',
            '#' => '',
          ),
          46 =>
          array (
            '@n' => '46',
            '@piece' => 'Q',
            '@from' => 'a1',
            '@to' => 'b1',
            '@display' => 'Qb1',
            '#' => '',
          ),
          47 =>
          array (
            '@n' => '47',
            '@piece' => 'q',
            '@from' => 'd2',
            '@to' => 'e2',
            '@state' => 'check',
            '@display' => 'Qxe2+',
            '#' => '',
          ),
          48 =>
          array (
            '@n' => '48',
            '@piece' => 'K',
            '@from' => 'f1',
            '@to' => 'g1',
            '@display' => 'Kg1',
            '#' => '',
          ),
          49 =>
          array (
            '@n' => '49',
            '@piece' => 'q',
            '@from' => 'e2',
            '@to' => 'f2',
            '@state' => 'check',
            '@display' => 'Qxf2+',
            '#' => '',
          ),
        ),
        'end' => '0-1',
      ),
    ),
    11 =>
    array (
      'info' =>
      array (
        'Event' => 'ICC r 3 3',
        'Site' => '012: Internet Chess Club',
        'Date' =>
        array (
          '@Year' => '1998',
          '@Month' => '01',
          '@Day' => '01',
          '#' => '',
        ),
        'White' =>
        array (
          '@Elo' => '1118',
          '#' => 'ZenRhino',
        ),
        'Black' =>
        array (
          '@Elo' => '959',
          '#' => 'BabyBach',
        ),
        'Result' => '1/2-1/2',
      ),
      'moves' =>
      array (
        'move' =>
        array (
          0 =>
          array (
            '@n' => '0',
            '@piece' => 'P',
            '@from' => 'g2',
            '@to' => 'g4',
            '@display' => 'g4',
            '#' => '',
          ),
          1 =>
          array (
            '@n' => '1',
            '@piece' => 'p',
            '@from' => 'g7',
            '@to' => 'g5',
            '@display' => 'g5',
            '#' => '',
          ),
          2 =>
          array (
            '@n' => '2',
            '@piece' => 'B',
            '@from' => 'f1',
            '@to' => 'g2',
            '@display' => 'Bg2',
            '#' => '',
          ),
          3 =>
          array (
            '@n' => '3',
            '@piece' => 'p',
            '@from' => 'd7',
            '@to' => 'd6',
            '@display' => 'd6',
            '#' => '',
          ),
          4 =>
          array (
            '@n' => '4',
            '@piece' => 'P',
            '@from' => 'h2',
            '@to' => 'h3',
            '@display' => 'h3',
            '#' => '',
          ),
          5 =>
          array (
            '@n' => '5',
            '@piece' => 'n',
            '@from' => 'b8',
            '@to' => 'a6',
            '@display' => 'Na6',
            '#' => '',
          ),
          6 =>
          array (
            '@n' => '6',
            '@piece' => 'P',
            '@from' => 'c2',
            '@to' => 'c4',
            '@display' => 'c4',
            '#' => '',
          ),
          7 =>
          array (
            '@n' => '7',
            '@piece' => 'p',
            '@from' => 'f7',
            '@to' => 'f6',
            '@display' => 'f6',
            '#' => '',
          ),
          8 =>
          array (
            '@n' => '8',
            '@piece' => 'P',
            '@from' => 'd2',
            '@to' => 'd4',
            '@display' => 'd4',
            '#' => '',
          ),
          9 =>
          array (
            '@n' => '9',
            '@piece' => 'p',
            '@from' => 'h7',
            '@to' => 'h5',
            '@display' => 'h5',
            '#' => '',
          ),
          10 =>
          array (
            '@n' => '10',
            '@piece' => 'P',
            '@from' => 'e2',
            '@to' => 'e4',
            '@display' => 'e4',
            '#' => '',
          ),
          11 =>
          array (
            '@n' => '11',
            '@piece' => 'p',
            '@from' => 'f6',
            '@to' => 'f5',
            '@display' => 'f5',
            '#' => '',
          ),
          12 =>
          array (
            '@n' => '12',
            '@piece' => 'P',
            '@from' => 'g4',
            '@to' => 'f5',
            '@display' => 'gxf5',
            '#' => '',
          ),
          13 =>
          array (
            '@n' => '13',
            '@piece' => 'p',
            '@from' => 'e7',
            '@to' => 'e6',
            '@display' => 'e6',
            '#' => '',
          ),
          14 =>
          array (
            '@n' => '14',
            '@piece' => 'P',
            '@from' => 'e4',
            '@to' => 'e5',
            '@display' => 'e5',
            '#' => '',
          ),
          15 =>
          array (
            '@n' => '15',
            '@piece' => 'b',
            '@from' => 'f8',
            '@to' => 'g7',
            '@display' => 'Bg7',
            '#' => '',
          ),
          16 =>
          array (
            '@n' => '16',
            '@piece' => 'N',
            '@from' => 'b1',
            '@to' => 'c3',
            '@display' => 'Nc3',
            '#' => '',
          ),
          17 =>
          array (
            '@n' => '17',
            '@piece' => 'q',
            '@from' => 'd8',
            '@to' => 'e7',
            '@display' => 'Qe7',
            '#' => '',
          ),
          18 =>
          array (
            '@n' => '18',
            '@piece' => 'N',
            '@from' => 'g1',
            '@to' => 'f3',
            '@display' => 'Nf3',
            '#' => '',
          ),
          19 =>
          array (
            '@n' => '19',
            '@piece' => 'b',
            '@from' => 'g7',
            '@to' => 'h6',
            '@display' => 'Bh6',
            '#' => '',
          ),
          20 =>
          array (
            '@n' => '20',
            '@piece' => 'P',
            '@from' => 'h3',
            '@to' => 'h4',
            '@display' => 'h4',
            '#' => '',
          ),
          21 =>
          array (
            '@n' => '21',
            '@piece' => 'q',
            '@from' => 'e7',
            '@to' => 'h7',
            '@display' => 'Qh7',
            '#' => '',
          ),
          22 =>
          array (
            '@n' => '22',
            '@piece' => 'B',
            '@from' => 'c1',
            '@to' => 'g5',
            '@display' => 'Bxg5',
            '#' => '',
          ),
          23 =>
          array (
            '@n' => '23',
            '@piece' => 'p',
            '@from' => 'd6',
            '@to' => 'e5',
            '@display' => 'dxe5',
            '#' => '',
          ),
          24 =>
          array (
            '@n' => '24',
            '@piece' => 'B',
            '@from' => 'g5',
            '@to' => 'h6',
            '@display' => 'Bxh6',
            '#' => '',
          ),
          25 =>
          array (
            '@n' => '25',
            '@piece' => 'n',
            '@from' => 'g8',
            '@to' => 'h6',
            '@display' => 'Nxh6',
            '#' => '',
          ),
          26 =>
          array (
            '@n' => '26',
            '@piece' => 'N',
            '@from' => 'f3',
            '@to' => 'g5',
            '@display' => 'Ng5',
            '#' => '',
          ),
          27 =>
          array (
            '@n' => '27',
            '@piece' => 'q',
            '@from' => 'h7',
            '@to' => 'f5',
            '@display' => 'Qxf5',
            '#' => '',
          ),
          28 =>
          array (
            '@n' => '28',
            '@piece' => 'K',
            '@from' => 'e1',
            '@to' => 'g1',
            '@display' => 'O-O',
            '#' => '',
          ),
          29 =>
          array (
            '@n' => '29',
            '@piece' => 'r',
            '@from' => 'h8',
            '@to' => 'g8',
            '@display' => 'Rg8',
            '#' => '',
          ),
          30 =>
          array (
            '@n' => '30',
            '@piece' => 'P',
            '@from' => 'd4',
            '@to' => 'd5',
            '@display' => 'd5',
            '#' => '',
          ),
          31 =>
          array (
            '@n' => '31',
            '@piece' => 'r',
            '@from' => 'g8',
            '@to' => 'g7',
            '@display' => 'Rg7',
            '#' => '',
          ),
          32 =>
          array (
            '@n' => '32',
            '@piece' => 'N',
            '@from' => 'g5',
            '@to' => 'e6',
            '@display' => 'Nxe6',
            '#' => '',
          ),
          33 =>
          array (
            '@n' => '33',
            '@piece' => 'r',
            '@from' => 'g7',
            '@to' => 'd7',
            '@display' => 'Rd7',
            '#' => '',
          ),
          34 =>
          array (
            '@n' => '34',
            '@piece' => 'N',
            '@from' => 'e6',
            '@to' => 'g7',
            '@state' => 'check',
            '@display' => 'Ng7+',
            '#' => '',
          ),
          35 =>
          array (
            '@n' => '35',
            '@piece' => 'r',
            '@from' => 'd7',
            '@to' => 'g7',
            '@display' => 'Rxg7',
            '#' => '',
          ),
          36 =>
          array (
            '@n' => '36',
            '@piece' => 'P',
            '@from' => 'd5',
            '@to' => 'd6',
            '@display' => 'd6',
            '#' => '',
          ),
          37 =>
          array (
            '@n' => '37',
            '@piece' => 'b',
            '@from' => 'c8',
            '@to' => 'd7',
            '@display' => 'Bd7',
            '#' => '',
          ),
          38 =>
          array (
            '@n' => '38',
            '@piece' => 'P',
            '@from' => 'd6',
            '@to' => 'c7',
            '@display' => 'dxc7',
            '#' => '',
          ),
          39 =>
          array (
            '@n' => '39',
            '@piece' => 'q',
            '@from' => 'f5',
            '@to' => 'f7',
            '@display' => 'Qf7',
            '#' => '',
          ),
          40 =>
          array (
            '@n' => '40',
            '@piece' => 'N',
            '@from' => 'c3',
            '@to' => 'd5',
            '@display' => 'Nd5',
            '#' => '',
          ),
          41 =>
          array (
            '@n' => '41',
            '@piece' => 'r',
            '@from' => 'g7',
            '@to' => 'g4',
            '@display' => 'Rg4',
            '#' => '',
          ),
          42 =>
          array (
            '@n' => '42',
            '@piece' => 'Q',
            '@from' => 'd1',
            '@to' => 'e1',
            '@display' => 'Qe1',
            '#' => '',
          ),
          43 =>
          array (
            '@n' => '43',
            '@piece' => 'q',
            '@from' => 'f7',
            '@to' => 'f5',
            '@display' => 'Qf5',
            '#' => '',
          ),
          44 =>
          array (
            '@n' => '44',
            '@piece' => 'N',
            '@from' => 'd5',
            '@to' => 'e3',
            '@display' => 'Ne3',
            '#' => '',
          ),
          45 =>
          array (
            '@n' => '45',
            '@piece' => 'q',
            '@from' => 'f5',
            '@to' => 'f3',
            '@display' => 'Qf3',
            '#' => '',
          ),
          46 =>
          array (
            '@n' => '46',
            '@piece' => 'N',
            '@from' => 'e3',
            '@to' => 'g4',
            '@display' => 'Nxg4',
            '#' => '',
          ),
          47 =>
          array (
            '@n' => '47',
            '@piece' => 'q',
            '@from' => 'f3',
            '@to' => 'f5',
            '@display' => 'Qf5',
            '#' => '',
          ),
          48 =>
          array (
            '@n' => '48',
            '@piece' => 'N',
            '@from' => 'g4',
            '@to' => 'e3',
            '@display' => 'Ne3',
            '#' => '',
          ),
          49 =>
          array (
            '@n' => '49',
            '@piece' => 'q',
            '@from' => 'f5',
            '@to' => 'h7',
            '@display' => 'Qh7',
            '#' => '',
          ),
          50 =>
          array (
            '@n' => '50',
            '@piece' => 'B',
            '@from' => 'g2',
            '@to' => 'b7',
            '@display' => 'Bxb7',
            '#' => '',
          ),
          51 =>
          array (
            '@n' => '51',
            '@piece' => 'q',
            '@from' => 'h7',
            '@to' => 'g7',
            '@state' => 'check',
            '@display' => 'Qg7+',
            '#' => '',
          ),
          52 =>
          array (
            '@n' => '52',
            '@piece' => 'N',
            '@from' => 'e3',
            '@to' => 'g2',
            '@display' => 'Ng2',
            '#' => '',
          ),
          53 =>
          array (
            '@n' => '53',
            '@piece' => 'n',
            '@from' => 'h6',
            '@to' => 'g4',
            '@display' => 'Ng4',
            '#' => '',
          ),
          54 =>
          array (
            '@n' => '54',
            '@piece' => 'B',
            '@from' => 'b7',
            '@to' => 'a8',
            '@display' => 'Bxa8',
            '#' => '',
          ),
          55 =>
          array (
            '@n' => '55',
            '@piece' => 'b',
            '@from' => 'd7',
            '@to' => 'e6',
            '@display' => 'Be6',
            '#' => '',
          ),
          56 =>
          array (
            '@n' => '56',
            '@piece' => 'B',
            '@from' => 'a8',
            '@to' => 'b7',
            '@display' => 'Bb7',
            '#' => '',
          ),
          57 =>
          array (
            '@n' => '57',
            '@piece' => 'q',
            '@from' => 'g7',
            '@to' => 'e7',
            '@display' => 'Qe7',
            '#' => '',
          ),
          58 =>
          array (
            '@n' => '58',
            '@piece' => 'P',
            '@from' => 'c7',
            '@to' => 'c8',
            '@promote' => 'Q',
            '@state' => 'check',
            '@display' => 'c8=Q+',
            '#' => '',
          ),
          59 =>
          array (
            '@n' => '59',
            '@piece' => 'b',
            '@from' => 'e6',
            '@to' => 'c8',
            '@display' => 'Bxc8',
            '#' => '',
          ),
          60 =>
          array (
            '@n' => '60',
            '@piece' => 'B',
            '@from' => 'b7',
            '@to' => 'c8',
            '@display' => 'Bxc8',
            '#' => '',
          ),
          61 =>
          array (
            '@n' => '61',
            '@piece' => 'n',
            '@from' => 'a6',
            '@to' => 'b8',
            '@display' => 'Nb8',
            '#' => '',
          ),
          62 =>
          array (
            '@n' => '62',
            '@piece' => 'R',
            '@from' => 'a1',
            '@to' => 'd1',
            '@display' => 'Rd1',
            '#' => '',
          ),
          63 =>
          array (
            '@n' => '63',
            '@piece' => 'q',
            '@from' => 'e7',
            '@to' => 'c7',
            '@display' => 'Qc7',
            '#' => '',
          ),
          64 =>
          array (
            '@n' => '64',
            '@piece' => 'P',
            '@from' => 'f2',
            '@to' => 'f4',
            '@display' => 'f4',
            '#' => '',
          ),
          65 =>
          array (
            '@n' => '65',
            '@piece' => 'q',
            '@from' => 'c7',
            '@to' => 'c8',
            '@display' => 'Qxc8',
            '#' => '',
          ),
          66 =>
          array (
            '@n' => '66',
            '@piece' => 'P',
            '@from' => 'f4',
            '@to' => 'e5',
            '@display' => 'fxe5',
            '#' => '',
          ),
          67 =>
          array (
            '@n' => '67',
            '@piece' => 'q',
            '@from' => 'c8',
            '@to' => 'c5',
            '@state' => 'check',
            '@display' => 'Qc5+',
            '#' => '',
          ),
          68 =>
          array (
            '@n' => '68',
            '@piece' => 'R',
            '@from' => 'f1',
            '@to' => 'f2',
            '@display' => 'Rf2',
            '#' => '',
          ),
          69 =>
          array (
            '@n' => '69',
            '@piece' => 'q',
            '@from' => 'c5',
            '@to' => 'e5',
            '@display' => 'Qxe5',
            '#' => '',
          ),
          70 =>
          array (
            '@n' => '70',
            '@piece' => 'Q',
            '@from' => 'e1',
            '@to' => 'e5',
            '@state' => 'check',
            '@display' => 'Qxe5+',
            '#' => '',
          ),
          71 =>
          array (
            '@n' => '71',
            '@piece' => 'n',
            '@from' => 'g4',
            '@to' => 'e5',
            '@display' => 'Nxe5',
            '#' => '',
          ),
          72 =>
          array (
            '@n' => '72',
            '@piece' => 'R',
            '@from' => 'd1',
            '@to' => 'f1',
            '@display' => 'Rdf1',
            '#' => '',
          ),
          73 =>
          array (
            '@n' => '73',
            '@piece' => 'n',
            '@from' => 'b8',
            '@to' => 'c6',
            '@display' => 'Nbc6',
            '#' => '',
          ),
          74 =>
          array (
            '@n' => '74',
            '@piece' => 'R',
            '@from' => 'f2',
            '@to' => 'f8',
            '@state' => 'check',
            '@display' => 'Rf8+',
            '#' => '',
          ),
          75 =>
          array (
            '@n' => '75',
            '@piece' => 'k',
            '@from' => 'e8',
            '@to' => 'd7',
            '@display' => 'Kd7',
            '#' => '',
          ),
          76 =>
          array (
            '@n' => '76',
            '@piece' => 'R',
            '@from' => 'f8',
            '@to' => 'h8',
            '@display' => 'Rh8',
            '#' => '',
          ),
          77 =>
          array (
            '@n' => '77',
            '@piece' => 'n',
            '@from' => 'e5',
            '@to' => 'c4',
            '@display' => 'Nxc4',
            '#' => '',
          ),
          78 =>
          array (
            '@n' => '78',
            '@piece' => 'R',
            '@from' => 'h8',
            '@to' => 'h5',
            '@display' => 'Rxh5',
            '#' => '',
          ),
          79 =>
          array (
            '@n' => '79',
            '@piece' => 'n',
            '@from' => 'c4',
            '@to' => 'a5',
            '@display' => 'Nc4a5',
            '#' => '',
          ),
          80 =>
          array (
            '@n' => '80',
            '@piece' => 'R',
            '@from' => 'h5',
            '@to' => 'h7',
            '@state' => 'check',
            '@display' => 'Rh7+',
            '#' => '',
          ),
          81 =>
          array (
            '@n' => '81',
            '@piece' => 'k',
            '@from' => 'd7',
            '@to' => 'd6',
            '@display' => 'Kd6',
            '#' => '',
          ),
          82 =>
          array (
            '@n' => '82',
            '@piece' => 'R',
            '@from' => 'f1',
            '@to' => 'f6',
            '@state' => 'check',
            '@display' => 'Rf6+',
            '#' => '',
          ),
          83 =>
          array (
            '@n' => '83',
            '@piece' => 'k',
            '@from' => 'd6',
            '@to' => 'e5',
            '@display' => 'Ke5',
            '#' => '',
          ),
          84 =>
          array (
            '@n' => '84',
            '@piece' => 'R',
            '@from' => 'f6',
            '@to' => 'f8',
            '@display' => 'Rf8',
            '#' => '',
          ),
          85 =>
          array (
            '@n' => '85',
            '@piece' => 'n',
            '@from' => 'a5',
            '@to' => 'c4',
            '@display' => 'Nc4',
            '#' => '',
          ),
          86 =>
          array (
            '@n' => '86',
            '@piece' => 'R',
            '@from' => 'f8',
            '@to' => 'c8',
            '@display' => 'Rc8',
            '#' => '',
          ),
          87 =>
          array (
            '@n' => '87',
            '@piece' => 'k',
            '@from' => 'e5',
            '@to' => 'd6',
            '@display' => 'Kd6',
            '#' => '',
          ),
          88 =>
          array (
            '@n' => '88',
            '@piece' => 'R',
            '@from' => 'c8',
            '@to' => 'c6',
            '@state' => 'check',
            '@display' => 'Rxc6+',
            '#' => '',
          ),
          89 =>
          array (
            '@n' => '89',
            '@piece' => 'k',
            '@from' => 'd6',
            '@to' => 'c6',
            '@display' => 'Kxc6',
            '#' => '',
          ),
          90 =>
          array (
            '@n' => '90',
            '@piece' => 'R',
            '@from' => 'h7',
            '@to' => 'h6',
            '@state' => 'check',
            '@display' => 'Rh6+',
            '#' => '',
          ),
          91 =>
          array (
            '@n' => '91',
            '@piece' => 'n',
            '@from' => 'c4',
            '@to' => 'd6',
            '@display' => 'Nd6',
            '#' => '',
          ),
          92 =>
          array (
            '@n' => '92',
            '@piece' => 'R',
            '@from' => 'h6',
            '@to' => 'f6',
            '@display' => 'Rf6',
            '#' => '',
          ),
          93 =>
          array (
            '@n' => '93',
            '@piece' => 'k',
            '@from' => 'c6',
            '@to' => 'd7',
            '@display' => 'Kd7',
            '#' => '',
          ),
          94 =>
          array (
            '@n' => '94',
            '@piece' => 'P',
            '@from' => 'h4',
            '@to' => 'h5',
            '@display' => 'h5',
            '#' => '',
          ),
          95 =>
          array (
            '@n' => '95',
            '@piece' => 'n',
            '@from' => 'd6',
            '@to' => 'e4',
            '@display' => 'Ne4',
            '#' => '',
          ),
          96 =>
          array (
            '@n' => '96',
            '@piece' => 'R',
            '@from' => 'f6',
            '@to' => 'f7',
            '@state' => 'check',
            '@display' => 'Rf7+',
            '#' => '',
          ),
          97 =>
          array (
            '@n' => '97',
            '@piece' => 'k',
            '@from' => 'd7',
            '@to' => 'e6',
            '@display' => 'Ke6',
            '#' => '',
          ),
          98 =>
          array (
            '@n' => '98',
            '@piece' => 'R',
            '@from' => 'f7',
            '@to' => 'g7',
            '@display' => 'Rg7',
            '#' => '',
          ),
          99 =>
          array (
            '@n' => '99',
            '@piece' => 'k',
            '@from' => 'e6',
            '@to' => 'f6',
            '@display' => 'Kf6',
            '#' => '',
          ),
          100 =>
          array (
            '@n' => '100',
            '@piece' => 'P',
            '@from' => 'h5',
            '@to' => 'h6',
            '@display' => 'h6',
            '#' => '',
          ),
          101 =>
          array (
            '@n' => '101',
            '@piece' => 'p',
            '@from' => 'a7',
            '@to' => 'a6',
            '@display' => 'a6',
            '#' => '',
          ),
          102 =>
          array (
            '@n' => '102',
            '@piece' => 'R',
            '@from' => 'g7',
            '@to' => 'g8',
            '@display' => 'Rg8',
            '#' => '',
          ),
          103 =>
          array (
            '@n' => '103',
            '@piece' => 'n',
            '@from' => 'e4',
            '@to' => 'g5',
            '@display' => 'Ng5',
            '#' => '',
          ),
          104 =>
          array (
            '@n' => '104',
            '@piece' => 'R',
            '@from' => 'g8',
            '@to' => 'f8',
            '@state' => 'check',
            '@display' => 'Rf8+',
            '#' => '',
          ),
          105 =>
          array (
            '@n' => '105',
            '@piece' => 'n',
            '@from' => 'g5',
            '@to' => 'f7',
            '@display' => 'Nf7',
            '#' => '',
          ),
          106 =>
          array (
            '@n' => '106',
            '@piece' => 'P',
            '@from' => 'h6',
            '@to' => 'h7',
            '@display' => 'h7',
            '#' => '',
          ),
          107 =>
          array (
            '@n' => '107',
            '@piece' => 'k',
            '@from' => 'f6',
            '@to' => 'e7',
            '@display' => 'Ke7',
            '#' => '',
          ),
          108 =>
          array (
            '@n' => '108',
            '@piece' => 'P',
            '@from' => 'h7',
            '@to' => 'h8',
            '@promote' => 'Q',
            '@display' => 'h8=Q',
            '#' => '',
          ),
          109 =>
          array (
            '@n' => '109',
            '@piece' => 'n',
            '@from' => 'f7',
            '@to' => 'd6',
            '@display' => 'Nd6',
            '#' => '',
          ),
          110 =>
          array (
            '@n' => '110',
            '@piece' => 'Q',
            '@from' => 'h8',
            '@to' => 'f6',
            '@state' => 'check',
            '@display' => 'Qf6+',
            '#' => '',
          ),
          111 =>
          array (
            '@n' => '111',
            '@piece' => 'k',
            '@from' => 'e7',
            '@to' => 'd7',
            '@display' => 'Kd7',
            '#' => '',
          ),
          112 =>
          array (
            '@n' => '112',
            '@piece' => 'Q',
            '@from' => 'f6',
            '@to' => 'g6',
            '@display' => 'Qg6',
            '#' => '',
          ),
          113 =>
          array (
            '@n' => '113',
            '@piece' => 'n',
            '@from' => 'd6',
            '@to' => 'f7',
            '@display' => 'Nf7',
            '#' => '',
          ),
          114 =>
          array (
            '@n' => '114',
            '@piece' => 'Q',
            '@from' => 'g6',
            '@to' => 'f7',
            '@state' => 'check',
            '@display' => 'Qxf7+',
            '#' => '',
          ),
          115 =>
          array (
            '@n' => '115',
            '@piece' => 'k',
            '@from' => 'd7',
            '@to' => 'c6',
            '@display' => 'Kc6',
            '#' => '',
          ),
          116 =>
          array (
            '@n' => '116',
            '@piece' => 'Q',
            '@from' => 'f7',
            '@to' => 'f6',
            '@state' => 'check',
            '@display' => 'Qf6+',
            '#' => '',
          ),
          117 =>
          array (
            '@n' => '117',
            '@piece' => 'k',
            '@from' => 'c6',
            '@to' => 'c7',
            '@display' => 'Kc7',
            '#' => '',
          ),
          118 =>
          array (
            '@n' => '118',
            '@piece' => 'R',
            '@from' => 'f8',
            '@to' => 'f7',
            '@state' => 'check',
            '@display' => 'Rf7+',
            '#' => '',
          ),
          119 =>
          array (
            '@n' => '119',
            '@piece' => 'k',
            '@from' => 'c7',
            '@to' => 'b8',
            '@display' => 'Kb8',
            '#' => '',
          ),
          120 =>
          array (
            '@n' => '120',
            '@piece' => 'Q',
            '@from' => 'f6',
            '@to' => 'a6',
            '@display' => 'Qxa6',
            '#' => '',
          ),
        ),
        'end' => '1/2-1/2',
      ),
    ),
    12 =>
    array (
      'info' =>
      array (
        'Event' => 'Bilbao',
        'Site' => '998: ?',
        'Date' =>
        array (
          '@Year' => '1987',
          '@Month' => '01',
          '@Day' => '01',
          '#' => '',
        ),
        'White' =>
        array (
          '@Elo' => '2620',
          '#' => 'Andersson,U',
        ),
        'Black' =>
        array (
          '@Elo' => '2740',
          '#' => 'Karpov,A',
        ),
        'Result' => '1/2-1/2',
        'ECO' => 'A05',
      ),
      'moves' =>
      array (
        'move' =>
        array (
          0 =>
          array (
            '@n' => '0',
            '@piece' => 'N',
            '@from' => 'g1',
            '@to' => 'f3',
            '@display' => 'Nf3',
            '#' => '',
          ),
          1 =>
          array (
            '@n' => '1',
            '@piece' => 'n',
            '@from' => 'g8',
            '@to' => 'f6',
            '@display' => 'Nf6',
            '#' => '',
          ),
          2 =>
          array (
            '@n' => '2',
            '@piece' => 'P',
            '@from' => 'c2',
            '@to' => 'c4',
            '@display' => 'c4',
            '#' => '',
          ),
          3 =>
          array (
            '@n' => '3',
            '@piece' => 'p',
            '@from' => 'b7',
            '@to' => 'b6',
            '@display' => 'b6',
            '#' => '',
          ),
          4 =>
          array (
            '@n' => '4',
            '@piece' => 'P',
            '@from' => 'g2',
            '@to' => 'g3',
            '@display' => 'g3',
            '#' => '',
          ),
          5 =>
          array (
            '@n' => '5',
            '@piece' => 'b',
            '@from' => 'c8',
            '@to' => 'b7',
            '@display' => 'Bb7',
            '#' => '',
          ),
          6 =>
          array (
            '@n' => '6',
            '@piece' => 'B',
            '@from' => 'f1',
            '@to' => 'g2',
            '@display' => 'Bg2',
            '#' => '',
          ),
          7 =>
          array (
            '@n' => '7',
            '@piece' => 'p',
            '@from' => 'e7',
            '@to' => 'e6',
            '@display' => 'e6',
            '#' => '',
          ),
          8 =>
          array (
            '@n' => '8',
            '@piece' => 'K',
            '@from' => 'e1',
            '@to' => 'g1',
            '@display' => 'O-O',
            '#' => '',
          ),
          9 =>
          array (
            '@n' => '9',
            '@piece' => 'b',
            '@from' => 'f8',
            '@to' => 'e7',
            '@display' => 'Be7',
            '#' => '',
          ),
          10 =>
          array (
            '@n' => '10',
            '@piece' => 'P',
            '@from' => 'd2',
            '@to' => 'd4',
            '@display' => 'd4',
            '#' => '',
          ),
          11 =>
          array (
            '@n' => '11',
            '@piece' => 'k',
            '@from' => 'e8',
            '@to' => 'g8',
            '@display' => 'O-O',
            '#' => '',
          ),
          12 =>
          array (
            '@n' => '12',
            '@piece' => 'N',
            '@from' => 'b1',
            '@to' => 'c3',
            '@display' => 'Nc3',
            '#' => '',
          ),
          13 =>
          array (
            '@n' => '13',
            '@piece' => 'n',
            '@from' => 'f6',
            '@to' => 'e4',
            '@display' => 'Ne4',
            '#' => '',
          ),
          14 =>
          array (
            '@n' => '14',
            '@piece' => 'N',
            '@from' => 'c3',
            '@to' => 'e4',
            '@display' => 'Nxe4',
            '#' => '',
          ),
          15 =>
          array (
            '@n' => '15',
            '@piece' => 'b',
            '@from' => 'b7',
            '@to' => 'e4',
            '@display' => 'Bxe4',
            '#' => '',
          ),
          16 =>
          array (
            '@n' => '16',
            '@piece' => 'N',
            '@from' => 'f3',
            '@to' => 'e1',
            '@display' => 'Ne1',
            '#' => '',
          ),
          17 =>
          array (
            '@n' => '17',
            '@piece' => 'b',
            '@from' => 'e4',
            '@to' => 'g2',
            '@display' => 'Bxg2',
            '#' => '',
          ),
          18 =>
          array (
            '@n' => '18',
            '@piece' => 'N',
            '@from' => 'e1',
            '@to' => 'g2',
            '@display' => 'Nxg2',
            '#' => '',
          ),
          19 =>
          array (
            '@n' => '19',
            '@piece' => 'p',
            '@from' => 'd7',
            '@to' => 'd5',
            '@display' => 'd5',
            '#' => '',
          ),
          20 =>
          array (
            '@n' => '20',
            '@piece' => 'Q',
            '@from' => 'd1',
            '@to' => 'a4',
            '@display' => 'Qa4',
            '#' => '',
          ),
          21 =>
          array (
            '@n' => '21',
            '@piece' => 'q',
            '@from' => 'd8',
            '@to' => 'e8',
            '@display' => 'Qe8',
            '#' => '',
          ),
          22 =>
          array (
            '@n' => '22',
            '@piece' => 'Q',
            '@from' => 'a4',
            '@to' => 'e8',
            '@display' => 'Qxe8',
            '#' => '',
          ),
          23 =>
          array (
            '@n' => '23',
            '@piece' => 'r',
            '@from' => 'f8',
            '@to' => 'e8',
            '@display' => 'Rxe8',
            '#' => '',
          ),
          24 =>
          array (
            '@n' => '24',
            '@piece' => 'P',
            '@from' => 'c4',
            '@to' => 'd5',
            '@display' => 'cxd5',
            '#' => '',
          ),
          25 =>
          array (
            '@n' => '25',
            '@piece' => 'p',
            '@from' => 'e6',
            '@to' => 'd5',
            '@display' => 'exd5',
            '#' => '',
          ),
          26 =>
          array (
            '@n' => '26',
            '@piece' => 'R',
            '@from' => 'f1',
            '@to' => 'd1',
            '@display' => 'Rd1',
            '#' => '',
          ),
          27 =>
          array (
            '@n' => '27',
            '@piece' => 'n',
            '@from' => 'b8',
            '@to' => 'a6',
            '@display' => 'Na6',
            '#' => '',
          ),
          28 =>
          array (
            '@n' => '28',
            '@piece' => 'B',
            '@from' => 'c1',
            '@to' => 'e3',
            '@display' => 'Be3',
            '#' => '',
          ),
          29 =>
          array (
            '@n' => '29',
            '@piece' => 'r',
            '@from' => 'a8',
            '@to' => 'd8',
            '@display' => 'Rad8',
            '#' => '',
          ),
          30 =>
          array (
            '@n' => '30',
            '@piece' => 'R',
            '@from' => 'a1',
            '@to' => 'c1',
            '@display' => 'Rac1',
            '#' => '',
          ),
          31 =>
          array (
            '@n' => '31',
            '@piece' => 'p',
            '@from' => 'g7',
            '@to' => 'g5',
            '@display' => 'g5',
            '#' => '',
          ),
          32 =>
          array (
            '@n' => '32',
            '@piece' => 'B',
            '@from' => 'e3',
            '@to' => 'd2',
            '@display' => 'Bd2',
            '#' => '',
          ),
          33 =>
          array (
            '@n' => '33',
            '@piece' => 'p',
            '@from' => 'c7',
            '@to' => 'c5',
            '@display' => 'c5',
            '#' => '',
          ),
          34 =>
          array (
            '@n' => '34',
            '@piece' => 'P',
            '@from' => 'd4',
            '@to' => 'c5',
            '@display' => 'dxc5',
            '#' => '',
          ),
          35 =>
          array (
            '@n' => '35',
            '@piece' => 'n',
            '@from' => 'a6',
            '@to' => 'c5',
            '@display' => 'Nxc5',
            '#' => '',
          ),
          36 =>
          array (
            '@n' => '36',
            '@piece' => 'B',
            '@from' => 'd2',
            '@to' => 'c3',
            '@display' => 'Bc3',
            '#' => '',
          ),
          37 =>
          array (
            '@n' => '37',
            '@piece' => 'n',
            '@from' => 'c5',
            '@to' => 'e6',
            '@display' => 'Ne6',
            '#' => '',
          ),
          38 =>
          array (
            '@n' => '38',
            '@piece' => 'P',
            '@from' => 'e2',
            '@to' => 'e3',
            '@display' => 'e3',
            '#' => '',
          ),
          39 =>
          array (
            '@n' => '39',
            '@piece' => 'b',
            '@from' => 'e7',
            '@to' => 'c5',
            '@display' => 'Bc5',
            '#' => '',
          ),
          40 =>
          array (
            '@n' => '40',
            '@piece' => 'K',
            '@from' => 'g1',
            '@to' => 'f1',
            '@display' => 'Kf1',
            '#' => '',
          ),
          41 =>
          array (
            '@n' => '41',
            '@piece' => 'p',
            '@from' => 'h7',
            '@to' => 'h6',
            '@display' => 'h6',
            '#' => '',
          ),
          42 =>
          array (
            '@n' => '42',
            '@piece' => 'R',
            '@from' => 'd1',
            '@to' => 'd3',
            '@display' => 'Rd3',
            '#' => '',
          ),
          43 =>
          array (
            '@n' => '43',
            '@piece' => 'p',
            '@from' => 'd5',
            '@to' => 'd4',
            '@display' => 'd4',
            '#' => '',
          ),
          44 =>
          array (
            '@n' => '44',
            '@piece' => 'P',
            '@from' => 'e3',
            '@to' => 'd4',
            '@display' => 'exd4',
            '#' => '',
          ),
          45 =>
          array (
            '@n' => '45',
            '@piece' => 'b',
            '@from' => 'c5',
            '@to' => 'd4',
            '@display' => 'Bxd4',
            '#' => '',
          ),
          46 =>
          array (
            '@n' => '46',
            '@piece' => 'B',
            '@from' => 'c3',
            '@to' => 'd4',
            '@display' => 'Bxd4',
            '#' => '',
          ),
          47 =>
          array (
            '@n' => '47',
            '@piece' => 'n',
            '@from' => 'e6',
            '@to' => 'd4',
            '@display' => 'Nxd4',
            '#' => '',
          ),
          48 =>
          array (
            '@n' => '48',
            '@piece' => 'R',
            '@from' => 'd3',
            '@to' => 'd1',
            '@display' => 'Rdd1',
            '#' => '',
          ),
        ),
        'end' => '1/2-1/2',
      ),
    ),
    13 =>
    array (
      'info' =>
      array (
        'Event' => 'NPS Challenge',
        'Site' => '999: Rec.Games.Chess.Computer',
        'Date' =>
        array (
          '@Year' => '1997',
          '@Month' => '03',
          '@Day' => '03',
          '#' => '',
        ),
        'Round' => '1',
        'White' => 'WCrafty-11.19',
        'Black' => 'Hiarcs 6.0 (final release)',
        'Result' => '*',
      ),
      'moves' =>
      array (
        'move' =>
        array (
          0 =>
          array (
            '@n' => '0',
            '@piece' => 'P',
            '@from' => 'e2',
            '@to' => 'e4',
            '@display' => 'e4',
            '#' => '',
          ),
          1 =>
          array (
            '@n' => '1',
            '@piece' => 'p',
            '@from' => 'e7',
            '@to' => 'e5',
            '@display' => 'e5',
            '#' => '',
          ),
          2 =>
          array (
            '@n' => '2',
            '@piece' => 'N',
            '@from' => 'g1',
            '@to' => 'f3',
            '@display' => 'Nf3',
            '#' => '',
          ),
          3 =>
          array (
            '@n' => '3',
            '@piece' => 'n',
            '@from' => 'b8',
            '@to' => 'c6',
            '@display' => 'Nc6',
            '#' => '',
          ),
          4 =>
          array (
            '@n' => '4',
            '@piece' => 'B',
            '@from' => 'f1',
            '@to' => 'b5',
            '@display' => 'Bb5',
            '#' => '',
          ),
          5 =>
          array (
            '@n' => '5',
            '@piece' => 'p',
            '@from' => 'a7',
            '@to' => 'a6',
            '@display' => 'a6',
            '#' => '',
          ),
          6 =>
          array (
            '@n' => '6',
            '@piece' => 'B',
            '@from' => 'b5',
            '@to' => 'a4',
            '@display' => 'Ba4',
            '#' => '',
          ),
          7 =>
          array (
            '@n' => '7',
            '@piece' => 'n',
            '@from' => 'g8',
            '@to' => 'f6',
            '@display' => 'Nf6',
            '#' => '',
          ),
          8 =>
          array (
            '@n' => '8',
            '@piece' => 'K',
            '@from' => 'e1',
            '@to' => 'g1',
            '@display' => 'O-O',
            '#' => '',
          ),
          9 =>
          array (
            '@n' => '9',
            '@piece' => 'b',
            '@from' => 'f8',
            '@to' => 'e7',
            '@display' => 'Be7',
            '#' => '',
          ),
          10 =>
          array (
            '@n' => '10',
            '@piece' => 'R',
            '@from' => 'f1',
            '@to' => 'e1',
            '@display' => 'Re1',
            '#' => '',
          ),
          11 =>
          array (
            '@n' => '11',
            '@piece' => 'p',
            '@from' => 'b7',
            '@to' => 'b5',
            '@display' => 'b5',
            '#' => '',
          ),
          12 =>
          array (
            '@n' => '12',
            '@piece' => 'B',
            '@from' => 'a4',
            '@to' => 'b3',
            '@display' => 'Bb3',
            '#' => '',
          ),
          13 =>
          array (
            '@n' => '13',
            '@piece' => 'p',
            '@from' => 'd7',
            '@to' => 'd6',
            '@display' => 'd6',
            '#' => '',
          ),
          14 =>
          array (
            '@n' => '14',
            '@piece' => 'P',
            '@from' => 'c2',
            '@to' => 'c3',
            '@display' => 'c3',
            '#' => '',
          ),
          15 =>
          array (
            '@n' => '15',
            '@piece' => 'b',
            '@from' => 'c8',
            '@to' => 'g4',
            '@display' => 'Bg4',
            '#' => '',
          ),
          16 =>
          array (
            '@n' => '16',
            '@piece' => 'P',
            '@from' => 'h2',
            '@to' => 'h3',
            '@display' => 'h3',
            '#' => '',
          ),
          17 =>
          array (
            '@n' => '17',
            '@piece' => 'b',
            '@from' => 'g4',
            '@to' => 'h5',
            '@display' => 'Bh5',
            '#' => '',
          ),
          18 =>
          array (
            '@n' => '18',
            '@piece' => 'P',
            '@from' => 'd2',
            '@to' => 'd3',
            '@display' => 'd3',
            '#' => '',
          ),
          19 =>
          array (
            '@n' => '19',
            '@piece' => 'k',
            '@from' => 'e8',
            '@to' => 'g8',
            '@display' => 'O-O',
            '#' => '',
          ),
          20 =>
          array (
            '@n' => '20',
            '@piece' => 'P',
            '@from' => 'a2',
            '@to' => 'a4',
            '@display' => 'a4',
            '#' => '',
          ),
          21 =>
          array (
            '@n' => '21',
            '@piece' => 'n',
            '@from' => 'c6',
            '@to' => 'a5',
            '@display' => 'Na5',
            '#' => '',
          ),
          22 =>
          array (
            '@n' => '22',
            '@piece' => 'B',
            '@from' => 'b3',
            '@to' => 'c2',
            '@display' => 'Bc2',
            '#' => '',
          ),
          23 =>
          array (
            '@n' => '23',
            '@piece' => 'p',
            '@from' => 'b5',
            '@to' => 'b4',
            '@display' => 'b4',
            '#' => '',
          ),
          24 =>
          array (
            '@n' => '24',
            '@piece' => 'P',
            '@from' => 'd3',
            '@to' => 'd4',
            '@display' => 'd4',
            '#' => '',
          ),
          25 =>
          array (
            '@n' => '25',
            '@piece' => 'b',
            '@from' => 'h5',
            '@to' => 'f3',
            '@display' => 'Bxf3',
            '#' => '',
          ),
          26 =>
          array (
            '@n' => '26',
            '@piece' => 'Q',
            '@from' => 'd1',
            '@to' => 'f3',
            '@display' => 'Qxf3',
            '#' => '',
          ),
          27 =>
          array (
            '@n' => '27',
            '@piece' => 'p',
            '@from' => 'c7',
            '@to' => 'c5',
            '@display' => 'c5',
            '#' => '',
          ),
          28 =>
          array (
            '@n' => '28',
            '@piece' => 'P',
            '@from' => 'd4',
            '@to' => 'c5',
            '@display' => 'dxc5',
            '#' => '',
          ),
          29 =>
          array (
            '@n' => '29',
            '@piece' => 'p',
            '@from' => 'd6',
            '@to' => 'c5',
            '@display' => 'dxc5',
            '#' => '',
          ),
          30 =>
          array (
            '@n' => '30',
            '@piece' => 'N',
            '@from' => 'b1',
            '@to' => 'd2',
            '@display' => 'Nd2',
            '#' => '',
          ),
          31 =>
          array (
            '@n' => '31',
            '@piece' => 'q',
            '@from' => 'd8',
            '@to' => 'c7',
            '@display' => 'Qc7',
            '#' => '',
          ),
          32 =>
          array (
            '@n' => '32',
            '@piece' => 'N',
            '@from' => 'd2',
            '@to' => 'f1',
            '@display' => 'Nf1',
            '#' => '',
          ),
          33 =>
          array (
            '@n' => '33',
            '@piece' => 'r',
            '@from' => 'f8',
            '@to' => 'd8',
            '@display' => 'Rfd8',
            '#' => '',
          ),
          34 =>
          array (
            '@n' => '34',
            '@piece' => 'N',
            '@from' => 'f1',
            '@to' => 'e3',
            '@display' => 'Ne3',
            '#' => '',
          ),
          35 =>
          array (
            '@n' => '35',
            '@piece' => 'p',
            '@from' => 'b4',
            '@to' => 'b3',
            '@display' => 'b3',
            '#' => '',
          ),
          36 =>
          array (
            '@n' => '36',
            '@piece' => 'B',
            '@from' => 'c2',
            '@to' => 'd1',
            '@display' => 'Bd1',
            '#' => '',
          ),
          37 =>
          array (
            '@n' => '37',
            '@piece' => 'p',
            '@from' => 'c5',
            '@to' => 'c4',
            '@display' => 'c4',
            '#' => '',
          ),
          38 =>
          array (
            '@n' => '38',
            '@piece' => 'N',
            '@from' => 'e3',
            '@to' => 'd5',
            '@display' => 'Nd5',
            '#' => '',
          ),
          39 =>
          array (
            '@n' => '39',
            '@piece' => 'n',
            '@from' => 'f6',
            '@to' => 'd5',
            '@display' => 'Nxd5',
            '#' => '',
          ),
          40 =>
          array (
            '@n' => '40',
            '@piece' => 'P',
            '@from' => 'e4',
            '@to' => 'd5',
            '@display' => 'exd5',
            '#' => '',
          ),
          41 =>
          array (
            '@n' => '41',
            '@piece' => 'n',
            '@from' => 'a5',
            '@to' => 'b7',
            '@display' => 'Nb7',
            '#' => '',
          ),
          42 =>
          array (
            '@n' => '42',
            '@piece' => 'B',
            '@from' => 'd1',
            '@to' => 'e2',
            '@display' => 'Be2',
            '#' => '',
          ),
          43 =>
          array (
            '@n' => '43',
            '@piece' => 'n',
            '@from' => 'b7',
            '@to' => 'd6',
            '@display' => 'Nd6',
            '#' => '',
          ),
          44 =>
          array (
            '@n' => '44',
            '@piece' => 'B',
            '@from' => 'e2',
            '@to' => 'f1',
            '@display' => 'Bf1',
            '#' => '',
          ),
          45 =>
          array (
            '@n' => '45',
            '@piece' => 'p',
            '@from' => 'f7',
            '@to' => 'f6',
            '@display' => 'f6',
            '#' => '',
          ),
          46 =>
          array (
            '@n' => '46',
            '@piece' => 'P',
            '@from' => 'g2',
            '@to' => 'g3',
            '@display' => 'g3',
            '#' => '',
          ),
          47 =>
          array (
            '@n' => '47',
            '@piece' => 'p',
            '@from' => 'e5',
            '@to' => 'e4',
            '@display' => 'e4',
            '#' => '',
          ),
          48 =>
          array (
            '@n' => '48',
            '@piece' => 'Q',
            '@from' => 'f3',
            '@to' => 'g2',
            '@display' => 'Qg2',
            '#' => '',
          ),
          49 =>
          array (
            '@n' => '49',
            '@piece' => 'p',
            '@from' => 'f6',
            '@to' => 'f5',
            '@display' => 'f5',
            '#' => '',
          ),
          50 =>
          array (
            '@n' => '50',
            '@piece' => 'B',
            '@from' => 'c1',
            '@to' => 'f4',
            '@display' => 'Bf4',
            '#' => '',
          ),
          51 =>
          array (
            '@n' => '51',
            '@piece' => 'q',
            '@from' => 'c7',
            '@to' => 'c5',
            '@display' => 'Qc5',
            '#' => '',
          ),
          52 =>
          array (
            '@n' => '52',
            '@piece' => 'R',
            '@from' => 'a1',
            '@to' => 'd1',
            '@display' => 'Rad1',
            '#' => '',
          ),
          53 =>
          array (
            '@n' => '53',
            '@piece' => 'n',
            '@from' => 'd6',
            '@to' => 'f7',
            '@display' => 'Nf7',
            '#' => '',
          ),
        ),
        'end' => '*',
      ),
    ),
  ),
  '@xmlns' => 'x-schema:pgn.xdr',
    );

    $data[0][1] = file_get_contents(dirname(dirname(__DIR__)) . '/p2x.xml');

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->encoder = new XmlEncoder();
  }

  /**
   * @test
   * @dataProvider provider
   */
  public function xmlDecode($normalized, $pgnxml) {
    $decoded = $this->encoder->decode($pgnxml, 'pgnxml');

    $this->assertEquals($normalized, $decoded, 'Decoded PGNXML is equal to expected PGN data.');
  }

  /**
   * @test
   * @dataProvider provider
   */
  public function xmlEncode($normalized, $pgnxml) {
    $encoded = $this->encoder->encode($normalized, 'pgnxml', array('xml_root_node_name' => 'PGNGAMES'));

    $internal_errors = libxml_use_internal_errors(true);

    $expected = new \DOMDocument;
    $expected->preserveWhiteSpace = FALSE;
    $expected->loadXML($pgnxml);

    $actual = new \DOMDocument;
    $actual->preserveWhiteSpace = FALSE;
    $actual->loadXML($encoded);

    $this->assertEquals($expected, $actual, 'Encoded PGN data is equal to expected PGNXML.');

    libxml_use_internal_errors($internal_errors);
  }

}
