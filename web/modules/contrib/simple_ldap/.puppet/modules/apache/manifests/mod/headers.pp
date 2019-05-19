class apache::mod::headers {

    exec { 'a2enmod headers':
        creates => '/etc/apache2/mods-enabled/headers.load',
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

}
