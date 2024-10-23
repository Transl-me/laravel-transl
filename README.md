# Package for the Laravel translation manager and database : Transl.me

[![GitHub Tests Action Status](https://github.com/transl-me/laravel-transl/actions/workflows/run-tests.yml/badge.svg)](https://github.com/transl-me/laravel-transl/actions/workflows/run-tests.yml)
[![GitHub PHPStan Action Status](https://github.com/transl-me/laravel-transl/actions/workflows/phpstan.yml/badge.svg)](https://github.com/transl-me/laravel-transl/actions/workflows/phpstan.yml)
[![GitHub Code Style Action Status](https://github.com/transl-me/laravel-transl/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/transl-me/laravel-transl/actions/workflows/fix-php-code-style-issues.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/transl-me/laravel-transl.svg?style=flat-square)](https://packagist.org/packages/transl-me/laravel-transl)
[![Total Downloads](https://img.shields.io/packagist/dt/transl-me/laravel-transl.svg?style=flat-square)](https://packagist.org/packages/transl-me/laravel-transl)

---

This package allows for pushing and pulling your Laravel localized content _(translation files by default)_ to [Transl.me](https://transl.me).

> [!TIP]
> Transl is a platform for developers, product owners, managers and translators to easily manage and automate localized content in a Laravel application. Localisation shouldn't be a burden on developers, it should be a burden on us.

## Installation

You can install the package via composer:

```bash
composer require transl-me/laravel-transl
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="transl-config"
```

You can check out what the contents of the published config file will be here: [config/transl.php](/config/transl.php).

## Usage _(Available commands)_

### Init

A one time command that initializes the defined project _(pushes the initial translation lines)_ on Transl.me.

```bash
php artisan transl:init
```

Check out [the command's signature](/src/Commands/TranslInitCommand.php) to learn more about it's possible options. 

### Push

Pushes the defined project's translation lines to Transl.me.

```bash
php artisan transl:push
```

Check out [the command's signature](/src/Commands/TranslPushCommand.php) to learn more about it's possible options. 

### Pull

Retrieves and stores the defined project's translation lines from Transl.me.

```bash
php artisan transl:pull
```

> [!NOTE]
> Unfortunately, when using with local translation files, we cannot guarantee the preservation of the original language file's formatting.
> This is because the language file contents are sent and retreive as JSON to and from Transl through HTTP.
> Therefore, any dynamic content and variables inside your translation files will be evualuated before being sent to Transl.
> No formatting information is transfered. Upon retrieval, the file's contents are reconstructed without the previously lost formating informations.

> [!TIP]
> Ensure any previous local changes are versioned.

Check out [the command's signature](/src/Commands/TranslPullCommand.php) to learn more about it's possible options. 

### Synch

Pulls then pushes the defined project's translation lines to Transl.me.

```bash
php artisan transl:synch
```

> [!NOTE]
> Same as for the push command regarding the inability to reconstruct the translation file's original content formatting _(when using with local translation files)_.

> [!TIP]
> Ensure any previous local changes are versioned.

Check out [the command's signature](/src/Commands/TranslSynchCommand.php) to learn more about it's possible options. 

### Analyse

Analyses the defined project's translation lines.

```bash
php artisan transl:analyse
```

Check out [the command's signature](/src/Commands/TranslAnalyseCommand.php) to learn more about it's possible options. 

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

If you're interested in contributing to the project, please read our [contributing docs](https://github.com/transl-me/laravel-transl/blob/main/.github/CONTRIBUTING.md) **before submitting a pull request**.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Victor GUTT](https://github.com/vicgutt)
-   [All Contributors](../../contributors)

## License

Please see [License File](LICENSE) for more information.
