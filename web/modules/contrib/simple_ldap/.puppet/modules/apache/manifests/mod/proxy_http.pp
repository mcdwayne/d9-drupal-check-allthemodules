class apache::mod::proxy_http {

    exec { 'a2enmod proxy_http':
        creates => '/etc/apache2/mods-enabled/proxy_http.load',
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

}
