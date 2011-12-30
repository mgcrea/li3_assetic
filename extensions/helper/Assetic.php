<?php
/**
 * Assetic Li3 Plugin
 *
 * @copyright     Copyright 2011, Olivier Louvignes (http://www.olouv.com)
 * @license       http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace li3_assetic\extensions\helper;

use lithium\core\Environment;
use lithium\core\Libraries;
use lithium\template\helper\Html;

use Assetic\AssetWriter;
use Assetic\AssetManager;
use Assetic\FilterManager;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\GlobAsset;

//use Assetic\Asset\AssetCache;
//use Assetic\Cache\FilesystemCache;

/**
 * A template helper that assists in generating CSS/JS static content
 */
class Assetic extends \lithium\template\Helper {

	public static $config = array();
	public static $filterManager;

	protected $scriptAssetCollection;
	protected $scriptAssetManager;
	protected $scriptAssetWriter;

	protected $styleAssetCollection;
	protected $styleAssetManager;
	protected $styleAssetWriter;

	/**
	 * Configures this helper
	 */
	public static function config($config = array()) {

		$defaults = array(
			'optimize' => (Environment::get() == 'production'),
			'debug' => (Environment::get() == 'development'),
			'stylesPath' => LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'webroot' . DIRECTORY_SEPARATOR . 'css',
			'scriptsPath' => LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'webroot' . DIRECTORY_SEPARATOR . 'js',
			'filters' => array()
		);
		$config += $defaults;

		// Merge config
		static::$config = array_merge(static::$config, $config);

		// Configure filters
		static::registerFilters($config['filters']);

	}

	/**
	 * Initializes helpers when used in a layout
	 */
	public function _init() {
		parent::_init();

		// Force helper static configuration
		if(!static::$config) static::config();

		$this->styleAssetCollection = new AssetCollection();
		$this->styleAssetManager = new AssetManager();
		// Initialize static assets writer
		$this->styleAssetWriter = new AssetWriter(static::$config['stylesPath']);

		$this->scriptAssetCollection = new AssetCollection();
		$this->scriptAssetManager = new AssetManager();
		// Initialize static assets writer
		$this->scriptAssetWriter = new AssetWriter(static::$config['scriptsPath']);

		// If we wanted to tap-into li3's generic helpers, that would be the way to achieve it using filters
		/*$this->_context->helper('html')->applyFilter('style', function($self, $params, $chain) {
			$params['path'] = $this->processParams($params['path'], $params['options']);
			$result = $chain->next($self, $params, $chain);
			return $result;
		});*/

	}

	public function style($source, $options = array()) {

		$defaults = array(
			'type' => "style",
			'target' => false,
			'filters' => array()
		);
		$options += $defaults;

		// Ensure arrays
		if(!is_array($source)) $source = array($source);
		if(!is_array($options['filters'])) $options['filters'] = array($options['filters']);

		$ac =& $this->styleAssetCollection;
		$aw =& $this->styleAssetWriter;

		// Resolve filters
		$filters = $this->resolveFilters($options['filters']);

		foreach($source as $leaf) {
			$leaf = self::guessExtension($leaf, $options);

			if(strpos($leaf, '*')) $asset = new GlobAsset(APP_CSS_PATH . DS . $leaf, $filters);
			else $asset = new FileAsset(APP_CSS_PATH . DS . $leaf, $filters);
			$ac->add($asset);

			if(!static::$config['optimize']) {
				if((!empty($filters) || get_class($asset) == 'Assetic\Asset\GlobAsset')) {
					$leaf = $options['target'] ?: self::normalizeExtension($leaf, $options['type']);
					$asset->setTargetPath($leaf);
					$aw->writeAsset($asset);
				}
				echo $this->_context->helper('html')->style($leaf) . "\n\t";
			}

		}

		return null;

	}

	public function styles($options = array()) {

		if(!static::$config['optimize']) {
			return $this->_context->styles();
		}

		$defaults = array(
			'target' => "main",
			'type' => "style",
			'force' => false,
			'filters' => array()
		);
		$options += $defaults;

		// Ensure arrays
		if(!is_array($options['filters'])) $options['filters'] = array($options['filters']);

		$ac =& $this->styleAssetCollection;
		$am =& $this->styleAssetManager;
		$aw =& $this->styleAssetWriter;

		// Resolve filters
		$filters = $this->resolveFilters($options['filters']);
		foreach($filters as $filter) {
			$ac->ensureFilter($filter);
		}

		$options['target'] = self::normalizeExtension($options['target'], $options['type']);
		$ac->setTargetPath($options['target']);
		$am->set(str_replace('.', '', $options['target']), $ac);

		//echo $ac->dump(); exit;
		// Write static assets
		if($options['force'] || !is_file($this->stylesWebroot . DS . $options['target'])) {
			$aw->writeManagerAssets($am);
		}

		return $this->_context->helper('html')->style($options['target'], array('inline' => true));

	}

	public function script($source, $options = array()) {

		$defaults = array(
			'type' => "script",
			'target' => false,
			'filters' => array()
		);
		$options += $defaults;

		// Ensure arrays
		if(!is_array($source)) $source = array($source);
		if(!is_array($options['filters'])) $options['filters'] = array($options['filters']);

		$ac =& $this->scriptAssetCollection;
		$aw =& $this->scriptAssetWriter;

		// Resolve filters
		$filters = $this->resolveFilters($options['filters']);

		foreach($source as $leaf) {
			$leaf = self::guessExtension($leaf, $options);
			if(strpos($leaf, '*')) $asset = new GlobAsset(APP_JS_PATH . DS . $leaf, $filters);
			else $asset = new FileAsset(APP_JS_PATH . DS . $leaf, $filters);
			$ac->add($asset);

			if(!static::$config['optimize']) {
				if((!empty($filters) || get_class($asset) == 'Assetic\Asset\GlobAsset')) {
					$leaf = $options['target'] ?: self::normalizeExtension($leaf, $options['type']);
					$asset->setTargetPath($leaf);
					$aw->writeAsset($asset);
				}
				echo $this->_context->helper('html')->script($leaf) . "\n\t";
			}
		}

		return null;

	}

	public function scripts($options = array()) {

		if(!static::$config['optimize']) {
			return $this->_context->scripts();
		}

		$defaults = array(
			'target' => "main",
			'type' => "script",
			'force' => false,
			'filters' => array()
		);
		$options += $defaults;

		// Ensure arrays
		if(!is_array($options['filters'])) $options['filters'] = array($options['filters']);

		$ac =& $this->scriptAssetCollection;
		$am =& $this->scriptAssetManager;
		$aw =& $this->scriptAssetWriter;

		// Resolve filters
		$filters = $this->resolveFilters($options['filters']);
		foreach($filters as $filter) {
			$ac->ensureFilter($filter);
		}

		$options['target'] = self::normalizeExtension($options['target'], $options['type']);
		$ac->setTargetPath($options['target']);
		$am->set(str_replace('.', '', $options['target']), $ac);

		//echo $ac->dump(); exit;
		// Write static assets
		if($options['force'] || !is_file($this->scriptsWebroot . DS . $options['target'])) {
			$aw->writeManagerAssets($am);
		}

		return $this->_context->helper('html')->script($options['target'], array('inline' => true));

	}

	private function resolveFilters($filters) {

		$fm =& static::$filterManager;

		$resolvedFilters = array();
		foreach($filters as $filter) {
			if(!$fm || !$fm->has($filter)) throw new \BadRequestException(sprintf('Filter `%s` has not been configured.', $filter));
			$resolvedFilters[] = $fm->get($filter);
		}

		return $resolvedFilters;

	}

	private function registerFilters($filters) {
		if(!is_array($filters)) $filters = array($filters);

		$fm =& static::$filterManager;
		if(!$fm) $fm = new FilterManager();

		foreach($filters as $key => $filter) {
			static::$filterManager->set($key, $filter);
		}

	}

	private static function guessExtension($leaf, $options = array()) {

		if(empty($options['type'])) return $leaf;

		if($options['type'] == 'style') {
			if(preg_match('/(.css|.less|.sass|.scss)$/is', $leaf)) return $leaf;
			else if(in_array('less', $options['filters']) || in_array('lessphp', $options['filters'])) $leaf .= '.less';
			else if(in_array('sass', $options['filters'])) $leaf .= '.scss';
			else $leaf .= '.css';
		} else if($options['type'] == 'script') {
			if(preg_match('/(.js)$/is', $leaf)) return $leaf;
			$leaf .= '.js';
		}

		return $leaf;

	}

	private static function normalizeExtension($leaf, $type) {

		if($type == 'style') {
			$leaf = preg_replace('/([^\.]+)(.css|.less|.sass|.scss)?$/is', '$1.css', $leaf);
		} else if($type == 'script') {
			$leaf = preg_replace('/([^\.]+)(.js)?$/is', '$1.js', $leaf);
		}

		$leaf = str_replace('*', 'combined', $leaf);

		return $leaf;

	}

}

?>
