<?php
/**
 * Rector Configuration
 *
 * @link https://getrector.com/documentation
 * @package allegro-audience
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
	->withParallel()
	->withIndent(
		indentChar: '	',
		indentSize: 1,
	)
	->withRootFiles()
	->withPaths( [
		__DIR__ . '/src',
		__DIR__ . '/tests',
	] )
	/**
	 * --------------------------------------------------------------------------
	 * Enabled rector rules/rulesets.
	 * --------------------------------------------------------------------------
	 *
	 * @link https://getrector.com/find-rule
	 */
	->withPreparedSets(
		codeQuality: true,
		deadCode: true,
		earlyReturn: true,
		typeDeclarations: true,
	)
	/**
	 * --------------------------------------------------------------------------
	 * Enable Rector to keep your code up-to-date with the latest features from the PHP version in your composer.json file
	 * --------------------------------------------------------------------------
	 */
	->withPhpSets()
	->withSets( [
		// Applies the rules for the PHPUnit version in composer.lock. Replaces
		// the per-version sets (PHPUNIT_100, PHPUNIT_110) dropped in
		// rector-phpunit 3.0.
		PHPUnitSetList::COMPOSER_BASED,
		PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
	] );
