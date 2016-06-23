<?php

namespace Nova\Database;

use Nova\Database\Query\Expression;
use Nova\Database\Query\Builder as QueryBuilder;

use Closure;


class Query
{
    /**
     * The base Query Builder instance.
     *
     * @var \Nova\Database\Query\Builder
     */
    protected $query;

    /**
     * The model being queried.
     *
     * @var \Nova\Database\Model
     */
    protected $model;


    /**
     * Create a new Model Query Builder instance.
     *
     * @param  \Nova\Database\Query\Builder  $query
     * @return void
     */
    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * Find a model by its primary key.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return mixed|static|null
     */
    public function find($id, $columns = array('*'))
    {
        if (is_array($id)) {
            return $this->findMany($id, $columns);
        }

        $this->query->where($this->model->getKeyName(), '=', $id);

        return $this->first($columns);
    }

    /**
     * Find a model by its primary key.
     *
     * @param  array  $id
     * @param  array  $columns
     * @return array|null|static
     */
    public function findMany($id, $columns = array('*'))
    {
        if (empty($id)) return null;

        $this->query->whereIn($this->model->getKeyName(), $id);

        return $this->get($columns);
    }

    /**
     * Execute the query and get the first result.
     *
     * @param  array  $columns
     * @return mixed|static|null
     */
    public function first($columns = array('*'))
    {
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return array|static[]
     */
    public function get($columns = array('*'))
    {
        return $this->query->get($columns);
    }

    /**
     * Get a paginator for the "select" statement.
     *
     * @param  int    $perPage
     * @param  array  $columns
     * @return \Pagination\Paginator
     */
    public function paginate($perPage = null, $columns = array('*'))
    {
        // Get the Pagination Factory instance.
        $paginator = $this->query->getConnection()->getPaginator();

        $perPage = $perPage ?: $this->model->getPerPage();

        if (isset($this->query->groups)) {
            return $this->groupedPaginate($paginator, $perPage, $columns);
        } else {
            return $this->ungroupedPaginate($paginator, $perPage, $columns);
        }
    }

    /**
     * Get a paginator for a grouped statement.
     *
     * @param  \Pagination\Environment  $paginator
     * @param  int    $perPage
     * @param  array  $columns
     * @return \Pagination\Paginator
     */
    protected function groupedPaginate($paginator, $perPage, $columns)
    {
        $results = $this->get($columns)->all();

        return $this->query->buildRawPaginator($paginator, $results, $perPage);
    }

    /**
     * Get a paginator for an ungrouped statement.
     *
     * @param  \Pagination\Environment  $paginator
     * @param  int    $perPage
     * @param  array  $columns
     * @return \Pagination\Paginator
     */
    protected function ungroupedPaginate($paginator, $perPage, $columns)
    {
        $total = $this->query->getPaginationCount();

        $page = $paginator->getCurrentPage($total);

        $this->query->forPage($page, $perPage);

        return $paginator->make($this->get($columns)->all(), $total, $perPage);
    }

    /**
     * Get the underlying query builder instance.
     *
     * @return \Nova\Database\Query\Builder|static
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set the underlying query builder instance.
     *
     * @param  \Nova\Database\Query\Builder  $query
     * @return void
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * Get the model instance being queried.
     *
     * @return \Nova\Database\ORM\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set a model instance for the model being queried.
     *
     * @param  \Nova\Database\ORM\Model  $model
     * @return \Nova\Database\ORM\Builder
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        $this->query->from($model->getTable());

        return $this;
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $result = call_user_func_array(array($this->query, $method), $parameters);

        if ($result === $this->query) return $this;

        return $result;
    }

    /**
     * Force a clone of the underlying query builder when cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->query = clone $this->query;
    }

}
