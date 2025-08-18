<?php

namespace App\Services;

use App\Traits\ManagesData;
use Illuminate\Database\Eloquent\Model;
use Closure;

/**
 * Class BaseService
 *
 * An abstract base service layer for handling common CRUD operations.
 * Services extending this class must set the `$modelClass` property to the fully-qualified
 * class name of the Eloquent model they will manage.
 *
 * This class uses the `ManagesData` trait to centralize create/update logic.
 *
 * @package App\Services
 */
abstract class BaseService
{
    use ManagesData;

    /**
     * The fully qualified class name of the model.
     *
     * Example:
     *   protected string $modelClass = \App\Models\User::class;
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * The model instance for performing queries.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected Model $model;

    /**
     * BaseService constructor.
     *
     * Resolves the model class from the Laravel service container.
     */
    public function __construct()
    {
        $this->model = app($this->modelClass);
    }

    /**
     * Retrieve all records with optional relationships, pagination, dynamic ordering, and custom queries.
     *
     * @param array         $with            Relationships to eager load.
     * @param int           $perPage         Number of records per page.
     * @param Closure|null  $queryCallback   A closure to apply custom query constraints (e.g., where clauses).
     * @param string|null   $orderBy         Column name to order by.
     * @param string        $direction       Order direction: 'asc' or 'desc'.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(
        array $with = [],
        ?int $perPage = null,
        ?Closure $queryCallback = null,
        ?string $orderBy = null,
        string $direction = 'desc'
    ) {
        $query = $this->model->with($with);

        if ($queryCallback) {
            $queryCallback($query);
        }

        $orderByColumn = $orderBy ?: $this->model->getKeyName();
        $query->orderBy($orderByColumn, $direction);

        return is_null($perPage) ? $query->get() : $query->paginate($perPage);
    }

    /**
     * Retrieve a single record by its primary key.
     *
     * @param int   $id    The primary key value.
     * @param array $with  Relationships to eager load.
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getById(int $id, array $with = [])
    {
        return $this->model->with($with)->findOrFail($id);
    }

    /**
     * Create a new record in the database.
     *
     * @param array        $data                  Data to be saved.
     * @param array        $relations             Related models to sync or attach.
     * @param Closure|null $transactionalCallback Optional transactional logic after save.
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data, array $relations = [], ?Closure $transactionalCallback = null)
    {
        return $this->storeOrUpdate($data, new $this->modelClass, $relations, $transactionalCallback);
    }

    /**
     * Update an existing record in the database.
     *
     * @param int          $id                    Primary key of the record to update.
     * @param array        $data                  Data to be updated.
     * @param array        $relations             Related models to sync or attach.
     * @param Closure|null $transactionalCallback Optional transactional logic after update.
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function update(int $id, array $data, array $relations = [], ?Closure $transactionalCallback = null)
    {
        // dd($id, $data, $relations, $transactionalCallback);
        $record = $this->getById($id);
        return $this->storeOrUpdate($data, $record, $relations, $transactionalCallback);
    }

    /**
     * Delete a record by its primary key.
     *
     * @param int $id Primary key of the record to delete.
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->getById($id)->delete();
    }
}
