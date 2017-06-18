<?php

namespace Nova\Database;


class ModelIdentifier
{
	/**
	 * The class name of the Model.
	 *
	 * @var string
	 */
	public $class;

	/**
	 * The unique identifier of the model.
	 *
	 * @var mixed
	 */
	public $id;

	/**
	 * Create a new Model identifier.
	 *
	 * @param  string  $class
	 * @param  mixed  $id
	 * @return void
	 */
	public function __construct($class, $id)
	{
		$this->id = $id;
		$this->class = $class;
	}
}
