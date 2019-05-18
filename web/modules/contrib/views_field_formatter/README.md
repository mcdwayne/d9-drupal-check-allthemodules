# Installation

```bash
docker-compose up -d
```

```bash
docker-compose exec -u www-data php composer install
```

```bash
docker-compose exec -u www-data php ./vendor/bin/run drupal:site-install
```

Using the default configuration, the development site web root should be in the `build` directory.

Then the site should be available at [http://127.0.0.1:80/](http://127.0.0.1:80/).
