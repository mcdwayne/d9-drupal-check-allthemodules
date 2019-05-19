class apache::mod::vhost_alias {

    exec { 'a2enmod vhost_alias':
        creates => '/etc/apache2/mods-enabled/vhost_alias.load',
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

}
