# phpnomad/loader

[![Latest Version](https://img.shields.io/packagist/v/phpnomad/loader.svg)](https://packagist.org/packages/phpnomad/loader) [![Total Downloads](https://img.shields.io/packagist/dt/phpnomad/loader.svg)](https://packagist.org/packages/phpnomad/loader) [![PHP Version](https://img.shields.io/packagist/php-v/phpnomad/loader.svg)](https://packagist.org/packages/phpnomad/loader) [![License](https://img.shields.io/packagist/l/phpnomad/loader.svg)](https://packagist.org/packages/phpnomad/loader)

`phpnomad/loader` defines what an initializer is and provides the `Bootstrapper` that composes them. Every [PHPNomad](https://phpnomad.com) application starts by constructing a `Bootstrapper` with a DI container and a list of initializers, then calling `load()`. That's the moment bindings get registered, event listeners get attached, REST routes get wired up, and platform integrations take hold.

The pattern is the spine of the framework. `phpnomad/core` depends on it, [Siren](https://sirenaffiliates.com) uses it directly as the top of its `Application` class, and it's been running in production for years across Siren, several MCP servers, and other client systems.

## Installation

```bash
composer require phpnomad/loader
```

Most applications pull in [`phpnomad/core`](https://packagist.org/packages/phpnomad/core) instead, which depends on `phpnomad/loader` and bundles the rest of the framework foundation. Install loader directly if you're building from the bottom up or writing your own core.

## Quick Start

Construct a `Bootstrapper` with your container and one or more initializers, then call `load()`.

```php
<?php

use PHPNomad\Core\Bootstrap\CoreInitializer;
use PHPNomad\Di\Container\Container;
use PHPNomad\Loader\Bootstrapper;

$container = new Container();

(new Bootstrapper(
    $container,
    new CoreInitializer(),
    new WordPressInitializer(),
    new MyAppInitializer()
))->load();
```

Here's what an initializer looks like. This one registers a class binding and an event listener, which covers the two most common cases.

```php
<?php

use PHPNomad\Events\Interfaces\HasListeners;
use PHPNomad\Loader\Interfaces\HasClassDefinitions;

final class MyAppInitializer implements HasClassDefinitions, HasListeners
{
    public function getClassDefinitions(): array
    {
        return [
            PostRepository::class => PostRepositoryInterface::class,
        ];
    }

    public function getListeners(): array
    {
        return [
            PostPublished::class => SendNotificationOnPublish::class,
        ];
    }
}
```

The `Bootstrapper` inspects each initializer for opt-in interfaces and wires it up based on what it implements. An initializer can implement one of these interfaces or a dozen. Most are small and focused on a single slice of your application.

## Key Concepts

### Initializers

An initializer is a class that declares a chunk of your application's setup. Rather than putting every binding, listener, and route in one file, you break setup into small pieces and compose them in the `Bootstrapper`. Each PHPNomad package that needs to hook into the framework ships its own initializer, and your application code does the same.

This is how the framework stays platform-agnostic. Swap `WordPressInitializer` for a Symfony initializer and your application initializers keep working because they don't know anything about the platform they run on.

### What the Bootstrapper does

When you call `load()`, the `Bootstrapper` walks each initializer and checks which opt-in interfaces it implements. The ones you'll reach for most often are `HasClassDefinitions`, `HasListeners`, `HasControllers`, `HasLoadCondition`, and `Loadable`.

- `HasClassDefinitions` registers class bindings into the DI container
- `HasListeners` attaches event listeners through the event strategy
- `HasControllers` registers REST endpoints through the REST strategy
- `HasLoadCondition` lets the initializer decide whether it should run at all
- `Loadable` runs an arbitrary `load()` method after everything else is wired

Additional opt-in interfaces cover mutations, event bindings, task handlers, GraphQL type definitions, console commands, facades, and update routines. The [bootstrapping guide at phpnomad.com](https://phpnomad.com) walks through each one with examples.

## Documentation

Full documentation lives at [phpnomad.com](https://phpnomad.com), including the bootstrapping guide, the initializer interface reference, and how `phpnomad/loader` composes with `phpnomad/di`, `phpnomad/event`, `phpnomad/rest`, and the rest of the framework.

## Contributing

PHPNomad is built and maintained by [Alex Standiford](https://alexstandiford.com), who's been using it for years to ship real software. Because `phpnomad/loader` is the foundation every application sits on, changes here are conservative and get exercised in production before they land.

Contributions are welcome. Good places to start include filing an issue for a bug you hit, proposing a new opt-in interface with a concrete use case, or improving the bootstrapping docs. Each PHPNomad package lives in its own repo, so issues and pull requests for loader go on this repo. If you want orientation before picking something up, opening a discussion issue on `phpnomad/core` is a fine place to start.

## License

MIT, see [LICENSE.txt](LICENSE.txt) for the full text.
