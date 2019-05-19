class apache::mod::rewrite {

    exec { 'a2enmod rewrite':
        creates => '/etc/apache2/mods-enabled/rewrite.load',
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

}
