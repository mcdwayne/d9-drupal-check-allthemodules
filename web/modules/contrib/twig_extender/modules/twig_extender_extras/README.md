# Installing

## Moment Function

See all possibilities https://github.com/fightbulc/moment.php

### Moment format

Using with drupal timezone
```
  {{ data|moment_format('d-M-Y') }}
```

Set a timezone
```
  {{ data|moment_format('d-M-Y','Europe/Berlin') }}
```

Using moment.js formats

```
  {{ data|moment_format('LLLL, 'Europe/Berlin', true) }}
```

### Moment operations

Usables operations add|subtract

Usable time limits seconds|minutes|hours|days|weeks|months|years

```
{{ data|moment_format('l') }} // Monday
{{ date|moment_operation('add', 'days', 1)|moment_format('l') }} // Tuesday
```

### Moment calendar

```
{{ data|moment_calendar }} // 3 days ago
```

### Moment difference

Usable operations relative|direction|seconds|minutes|hours|days|weeks|months|years

```
{{ from|moment_difference(to, 'relative') }}
```

## Field items

```
<ul>
  {% for item in content.field_tags|children %}
    <li>{{ item }}</li>
  {% endfor %}
</ul>
```

Sorted:

```
<ul>
  {% for item in content.field_tags|children(true) %}
    <li>{{ item }}</li>
  {% endfor %}
</ul>
```

## Formatter

```
{{ node.field_to_view|view('link', {target: '_blank'}) }}
```

## Raw URL value

```
{{ node.field_link|url_value }}
```
