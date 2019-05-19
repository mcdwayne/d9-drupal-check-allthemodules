class apache::mod::expires {

    exec { 'a2enmod expires':
        creates => '/etc/apache2/mods-enabled/expires.load',
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

}
