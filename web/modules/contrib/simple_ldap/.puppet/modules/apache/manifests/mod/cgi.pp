class apache::mod::cgi {

    exec { 'a2enmod cgi':
        unless  => 'a2query -m cgi || a2query -m cgid',
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

}
