class apache::mod::proxy_wstunnel {

    exec { 'a2enmod proxy_wstunnel':
        creates => '/etc/apache2/mods-enabled/proxy_wstunnel.load',
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

}
