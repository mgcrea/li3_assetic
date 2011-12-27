# Lithium Assetic Plugin #
by `Olivier Louvignes`

## DESCRIPTION ##

This repository contains a [Lithium](https://github.com/UnionOfRAD/lithium) helper that wraps [Assetic](https://github.com/kriswallsmith/assetic) asset manager functionnality.

## SETUP ##

Using `Lithium Assetic Plugin` requires [Assetic](https://github.com/kriswallsmith/assetic).

1. Clone [Assetic](https://github.com/kriswallsmith/assetic) to the `app/librairies/_source/assetic` folder.

		git submodule add https://github.com/kriswallsmith/assetic.git libraries/_source/assetic

2. Symlink `app/librairies/Assetic` to `app/librairies/_source/assetic/src/Assetic`.

		ln -s _source/assetic/src/Assetic libraries/Assetic

2. Clone this plugin to `app/librairies/li3_assetic`

		git submodule add https://github.com/mgcrea/li3_assetic.git libraries/li3_assetic

3. Load both librairies in your `config/bootstrap/librairies.php` file :

		Libraries::add('Assetic');
		Libraries::add('li3_assetic');

4. Configure the helper in your `config/bootstrap/media.php` file (or somewhere else), like :

		/**
		 * Assetic configuration
		 */

		use lithium\core\Libraries;
		use li3_assetic\extensions\helper\Assetic;

		Libraries::add('lessc', array('path' => LITHIUM_APP_PATH . '/libraries/lessphp', 'prefix' => false, 'suffix' => '.inc.php'));
		use Assetic\Filter\LessphpFilter;

		use Assetic\Filter\Yui;

		Assetic::config(array(
			'filters' => array(
				'lessphp' => new LessphpFilter(),
				'yui_css' => new Yui\CssCompressorFilter(LITHIUM_APP_PATH . '/../tools/yuicompressor-2.4.7.jar'),
				'yui_js' => new Yui\JsCompressorFilter(LITHIUM_APP_PATH . '/../tools/yuicompressor-2.4.7.jar')
			)
		));

5. Use the assetic helper in your layout :

		// Regular call
		<?php echo $this->assetic->script(array('libs/json2', 'libs/phonegap-1.2.0', 'libs/underscore', 'libs/mustache')); ?>
		// Use some filter (will be processed even in development mode)
		<?php echo $this->assetic->style(array('mobile/core'), array('target' => 'mobile.css', 'filters' => array('lessphp'))); ?>
		// Use glob asset (will be processed even in development mode)
		<?php echo $this->assetic->script(array('php/*.js'), array('target' => 'php.js'));

6. Make sur to end your layout with final (production only by default) configuration :

		<?php echo $this->assetic->styles(array('target' => 'mobile.css', 'filters' => 'yui_css')); ?> // Will not overwrite existing compiled file by default
		<?php echo $this->assetic->scripts(array('target' => 'mobile.js', 'filters' => 'yui_js', 'force' => true)); ?> // Will generated compiled output even if files exists

7. You can activate compilation/filters with (like on top of your layout file), it is off by default in a `development` environment :

		<?php $this->assetic->config(array('optimize' => true)); ?> // Force activation in development environment


## BUGS AND CONTRIBUTIONS ##

Patches welcome! Send a pull request.

Post issues on [Github](http://github.com/mgcrea/li3_assetic/issues)

The latest code will always be [here](http://github.com/mgcrea/li3_assetic)

## LICENSE ##

Copyright 2011 Olivier Louvignes. All rights reserved.

The MIT License

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
