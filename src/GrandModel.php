<?php
/**
 * Created by PhpStorm.
 * User: jamesspence
 * Date: 5/5/15
 * Time: 5:11 PM
 */

namespace App\Grandiloquent;


use App\Grandiloquent\Relations\BelongsTo;
use App\Grandiloquent\Relations\BelongsToMany;
use App\Grandiloquent\Relations\HasMany;
use App\Grandiloquent\Relations\HasManyThrough;
use App\Grandiloquent\Relations\HasOne;
use Carbon\Carbon;
use DB;
use FrogFrame\Util\DateTimeFormatter;
//use Illuminate\Database\Eloquent\GrandCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class GrandModel extends Model
{
	/**
	 * @param array $models
	 * @return GrandCollection
	 */
	public function newCollection(array $models = [])
	{
		return new GrandCollection($models);
	}

    /**
     * @param GrandModel[] $models
     * @return bool
     */
    public static function fieldsAreDirtyMany(array $models)
    {
        /** @var GrandModel $model */
        foreach($models as $model)
        {
            if($model->fieldsAreDirty())
                return true;
        }
        return false;
    }

    /**
     * Checks if any fields are dirty and returns true / false
     * @return bool
     */
    public function fieldsAreDirty()
    {
        return !empty($this->getDirty());
    }

	public function push()
	{
		if(!$this->save())
			return false;

		foreach ($this->relations as $models)
		{
			$models = $models instanceof GrandCollection ?
				$models : GrandCollection::make([$models]);

			$models->saveMany();
			/** @var GrandModel $model */
			foreach($models as $model)
			{
				if(!$model->push())
					return false;
			}
		}

		return true;
	}

    /**
     * Retrieves the timestamp fields, if timestamps are set.
     * @return array
     */
	public function getTimestampFields()
	{
		if($this->timestamps)
		{
			$timestampFields = [
				$this->getCreatedAtColumn(),
				$this->getUpdatedAtColumn()
			];
			return $timestampFields;
		}
		return [];
	}

	/**
     * Returns the table name in a static format.
	 * @return string
	 */
	public static function getTableName()
	{
		return (new static)->getTable();
	}

    /**
     * @param array $attributes
     * @return array
     */
	public function getAttributes(array $attributes = [])
	{
        if(empty($attributes))
            return parent::getAttributes();

		$attributesArray = [];
		foreach($attributes as $attribute)
		{
			$attributesArray[$attribute] = $this->getAttribute($attribute);
		}
		return $attributesArray;
	}

    /**
     * @param string $related
     * @param null $table
     * @param null $foreignKey
     * @param null $otherKey
     * @param null $relation
     * @return BelongsToMany
     */
	public function belongsToMany($related, $table = null, $foreignKey = null, $otherKey = null, $relation = null)
	{
		// If no relationship name was passed, we will pull backtraces to get the
		// name of the calling function. We will use that function name as the
		// title of this relation since that is a great convention to apply.
		if (is_null($relation))
		{
			$relation = $this->getBelongsToManyCaller();
		}

		// First, we'll need to determine the foreign key and "other key" for the
		// relationship. Once we have determined the keys we'll make the query
		// instances as well as the relationship instances we need for this.
		$foreignKey = $foreignKey ?: $this->getForeignKey();

		/** @var GrandModel $instance */
		$instance = new $related;

		$otherKey = $otherKey ?: $instance->getForeignKey();

		// If no table name was provided, we can guess it by concatenating the two
		// models using underscores in alphabetical order. The two model names
		// are transformed to snake case from their default CamelCase also.
		if (is_null($table))
		{
			$table = $this->joiningTable($related);
		}

		// Now we're ready to create a new query builder for the related model and
		// the relationship instances for the relation. The relations will set
		// appropriate query constraint and entirely manages the hydrations.
		$query = $instance->newQuery();

		return new BelongsToMany($query, $this, $table, $foreignKey, $otherKey, $relation);
	}


    /**
     * @param string $related
     * @param null $foreignKey
     * @param null $localKey
     * @return HasOne
     */
	public function hasOne($related, $foreignKey = null, $localKey = null)
	{
		$foreignKey = $foreignKey ?: $this->getForeignKey();

		/** @var GrandModel $instance */
		$instance = new $related;

		$localKey = $localKey ?: $this->getKeyName();

		return new HasOne($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey);
	}

    /**
     * @param string $related
     * @param null $foreignKey
     * @param null $localKey
     * @return HasMany
     */
	public function hasMany($related, $foreignKey = null, $localKey = null)
	{
		$foreignKey = $foreignKey ?: $this->getForeignKey();

		$instance = new $related;

		$localKey = $localKey ?: $this->getKeyName();

		return new HasMany($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey);
	}

	public function fireModelEvent($event, $halt = true)
	{
		return parent::fireModelEvent($event, $halt);
	}

	public function updateTimestamps()
	{
		parent::updateTimestamps();
	}

	public function getKeyForSaveQuery()
	{
		return parent::getKeyForSaveQuery();
	}

	public function finishSave(array $options)
	{
		parent::finishSave($options);
	}
    
}