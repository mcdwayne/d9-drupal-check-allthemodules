define php::pear (
    $package = $title,
) {

    exec { "php::pear::${package}":
        command => "pear install ${package}",
        require => Package['php-pear'],
        unless  => "pear list ${package}",
    }

}
