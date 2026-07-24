# Features

Features are plain PHP classes with a public `boot(): void` method. Each feature
lives in `src/features/` in the namespace `Alley\WP\Allegro_Audience\Features` and
uses the WordPress `class-{slug}.php` file-naming convention.

A feature registers its own hooks inside `boot()`:

```php
<?php

declare(strict_types=1);

namespace Alley\WP\Allegro_Audience\Features;

defined( 'ABSPATH' ) || exit;

class Hello {
	public function boot(): void {
		add_action( 'init', $this->say_hello( ... ) );
	}

	public function say_hello(): void {
		// ...
	}
}
```

Register the feature in `src/main.php` and require its file in the plugin
bootstrap (`wp-allegro-audience.php`):

```php
function main(): void {
	( new Allegro_Settings() )->boot();
	( new Load_Client_Script() )->boot();
	( new Hello() )->boot();
}
```
