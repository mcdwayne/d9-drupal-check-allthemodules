class apache::mod::php5 (
    $expose_php = 'On',
    $post_max_size = '8M',
    $max_execution_time = 30,
    $max_input_time = 60,
    $timezone = undef,
) {

    if !defined(Package['libapache2-mod-php5']) { package { 'libapache2-mod-php5': } }

    exec { 'a2enmod php5':
        creates => ['/etc/apache2/mods-enabled/php5.load', '/etc/apache2/mods-enabled/php5.conf'],
        require => Package['apache2', 'libapache2-mod-php5'],
        notify  => Service['apache2'],
    }

    file { '/etc/php5/apache2/php.ini':
        content => template('apache/etc/php5/apache2/php.ini.erb'),
        require => Package['libapache2-mod-php5'],
        notify  => Exec['apache::mod::php5::restart'],
    }

    exec { 'apache::mod::php5::restart':
        command     => '/bin/true',
        subscribe   => Exec['php::restart'],
        notify      => Service['apache2'],
        refreshonly => true,
    }

}
