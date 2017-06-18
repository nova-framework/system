<?php

namespace Nova\Foundation\Console;

use Nova\Support\Collection;
use Nova\Foundation\Application;
use Nova\Database\ORM\Model;

use Symfony\Component\VarDumper\Caster\Caster;

use Exception;


class FrameworkCaster
{
	/**
	 * Nova application methods to include in the presenter.
	 *
	 * @var array
	 */
	private static $appProperties = [
		'configurationIsCached',
		'environment',
		'environmentFile',
		'isLocal',
		'routesAreCached',
		'runningUnitTests',
		'version',
		'path',
		'basePath',
		'configPath',
		'databasePath',
		'langPath',
		'publicPath',
		'storagePath',
		'bootstrapPath',
	];

	/**
	 * Get an array representing the properties of an application.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @return array
	 */
	public static function castApplication(Application $app)
	{
		$results = array();

		foreach (self::$appProperties as $property) {
			try {
				$val = $app->$property();

				if (! is_null($val)) {
					$results[Caster::PREFIX_VIRTUAL.$property] = $val;
				}
			} catch (Exception $e) {
				//
			}
		}

		return $results;
	}

	/**
	 * Get an array representing the properties of a collection.
	 *
	 * @param  \Nova\Support\Collection  $collection
	 * @return array
	 */
	public static function castCollection(Collection $collection)
	{
		return array(
			Caster::PREFIX_VIRTUAL.'all' => $collection->all(),
		);
	}

	/**
	 * Get an array representing the properties of a model.
	 *
	 * @param  \Nova\Database\ORM\Model  $model
	 * @return array
	 */
	public static function castModel(Model $model)
	{
		$attributes = array_merge(
			$model->getAttributes(), $model->getRelations()
		);

		$visible = array_flip(
			$model->getVisible() ?: array_diff(array_keys($attributes), $model->getHidden())
		);

		$results = [];

		foreach (array_intersect_key($attributes, $visible) as $key => $value) {
			$results[(isset($visible[$key]) ? Caster::PREFIX_VIRTUAL : Caster::PREFIX_PROTECTED).$key] = $value;
		}

		return $results;
	}
}
