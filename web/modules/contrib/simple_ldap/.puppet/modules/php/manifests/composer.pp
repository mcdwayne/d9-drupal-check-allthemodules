class php::composer {

    if !defined(Package['curl']) { package { 'curl': } }

    exec { 'php::composer::install':
        command => 'curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer',
        creates => '/usr/local/bin/composer',
        require => Package['curl', 'php5-cli'],
    }

}
