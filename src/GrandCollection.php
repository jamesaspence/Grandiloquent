<?php
/**
 * Created by PhpStorm.
 * User: jamesspence
 * Date: 5/8/15
 * Time: 9:20 AM
 */

namespace Grandiloquent;

use Grandiloquent\Exception\MassEventFireException;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class GrandCollection extends EloquentCollection
{
    public function __construct(...$arrays)
    {
        $items = [];
        foreach($arrays as $array)
        {
            $array = is_null($array) ? [] : $this->getArrayableItems($array);
            $items = $items + $array;
        }
        $this->items = $items;
    }
    
    public function saveMany()
	{
		$models = $this->all();

		$updates = [];
		$inserts = [];
        $unedited = [];
		/** @var GrandModel $model */
		foreach($models as $key => $model)
		{
			if($model->fieldsAreDirty())
			{
				if ($model->fireModelEvent('saving') === false)
				{
					throw new MassEventFireException("Unable to complete saveMany: event 'saving' failed on key number '$key'.");
				}
				if($model->exists)
					$updates[$key] = $model;
				else
					$inserts[$key] = $model;
			}
			else
				$unedited[$key] = $model;
		}

		//We append rather than array_merge here to retain the correct order keys for these models
		//That way if we pass this back to HasMany, it is able to attach joins in the correct order
		$newModels = $this->performUpdateMany($updates) + $this->performInsertMany($inserts) + $unedited;
		ksort($newModels);

		$this->items = $newModels;
		return $this;
	}

	public function performUpdateMany(array $models)
	{
		if(count($models) < 1)
			return [];

		/** @var GrandModel $model */
		foreach($models as $key => $model)
		{
            if($model->fireModelEvent("updating") === false)
            {
                throw new MassEventFireException("Unable to complete saveMany: event 'updating' failed on key number '$key'.");
            }
		}
		$models = $this->updateMany($models);
		/** @var GrandModel $model */
		foreach($models as $model)
		{
			$model->fireModelEvent("updated");
		}
		return $models;
	}

	public function pushMany()
	{
		$this->saveMany();

		$saveManys = [];
		/** @var GrandModel $model */
		foreach($this->items as $model)
		{
			foreach($model->getRelations() as $relations)
			{
				if($relations instanceof GrandCollection)
				{
					$class = $relations->first();

					if(!isset($class))
						continue;

					$className = get_class($class);
					$relations = $relations->all();
				}
				else
					$className = get_class($relations);

				if(!isset($saveManys[$className]))
					$saveManys[$className] = [];

				if(is_array($relations))
					$saveManys[$className] = array_merge($saveManys[$className], $relations);
				else
					$saveManys[$className][] = $relations;
			}
		}

		foreach($saveManys as $key => $saveMany)
		{
			$saveManys[$key] = GrandCollection::make($saveMany);
			/** @var GrandCollection $saveMany */
			$saveMany = $saveManys[$key];
			$saveMany->pushMany();
		}
		return true;
	}

	public function performInsertMany(array $models)
	{
		if(count($models) < 1)
			return [];

		$newModels = $this->insertMany($models);
		$orderedModels = [];
		$i = 0;
		/**
		 * @var GrandModel $model
		 */
		foreach($models as $key => $model)
		{
			$orderedModels[$key] = $newModels[$i];
			/** @var GrandModel $newModel */
			$newModel = $newModels[$i];
			$newModel->setRelations($model->getRelations());
			++$i;
		}

		return $orderedModels;
	}

	protected function updateMany(array $models)
	{
        if(count($models) < 1)
            return [];

        /**
         * @var GrandModel $baseModel
         * @var GrandModel $class
         */
		$baseModel = $models[0];
		$table = $baseModel->getTable();
		$updateSql = "update `$table` set ";
		$updateFieldsSql = [];
		$params = [];
		$ids = [];
		$idSql = "";

		/** @var GrandModel $model */
		foreach($models as $model)
		{
            if($model->timestamps)
                $model->updateTimestamps();
		}

		//TODO need to have each instance of model here
		foreach($this->getDirtyFields($models) as $updateField)
		{
			$updateFieldsSql[$updateField] = [
				"query" => " `$updateField` = case `{$baseModel->getKeyName()}` ",
				"params" => []
			];
		}

		/** @var GrandModel $model */
		foreach($models as $model)
		{
            foreach($updateFieldsSql as $updateField => $fieldArray)
            {
                $fieldArray["query"].= "when ? then ? ";
                $fieldArray["params"][] = $model->getKeyForSaveQuery();
                if(in_array($updateField, $model->getTimestampFields()))
                {
                    /** @var Carbon $time */
                    $time = $model->getAttribute($updateField);
                    $fieldArray["params"][] = $time->toDateTimeString();
                }
                else
                    $fieldArray["params"][] = $model->getAttribute($updateField);
                $updateFieldsSql[$updateField] = $fieldArray;
            }
            $ids[] = $model->getKeyForSaveQuery();
            $idSql.=", ?";
		}

		$idSql = substr($idSql, 2, strlen($idSql));

		$length = count($updateFieldsSql);
		$i = 1;
		foreach($updateFieldsSql as $updateField => $fieldArray)
		{
			$updateFieldsSql[$updateField]["query"].= "end";
			if($i < $length)
				$updateFieldsSql[$updateField]["query"].= ", ";
			$updateSql.= $updateFieldsSql[$updateField]["query"];
			$params = array_merge($params, $updateFieldsSql[$updateField]["params"]);
			++$i;
		}
		$updateSql.= " where `{$baseModel->getKeyName()}` in($idSql)";

		$params = array_merge($params, $ids);

		DB::statement($updateSql, $params);
		foreach($models as $model)
		{
            $model->finishSave(["touch" => false]);
		}
		return $models;
	}

	protected function insertMany(array $models)
	{
        if(count($models) < 1)
            return [];

		/** @var GrandModel $baseModel */
        $baseModel = $models[0];
		$table = $baseModel->getTable();

		/** @var GrandModel $model */
		foreach($models as $model)
		{
			if($model->timestamps)
				$model->updateTimestamps();

		}

		$fields = $this->getDirtyFields($models);
		$values = [];
		foreach($models as $model)
		{
			$values[] = $model->getAttributes($fields);
		}

		DB::table($table)->insert($values);
		$firstId = DB::connection("mysql")->getPdo()->lastInsertId();

        $class = get_class($baseModel);
		/** @var GrandCollection $newModels */
		$newModels = $class::where("id", ">=", $firstId)->take(count($models))->orderBy("id", "asc")->get();
		return $newModels->all();
	}

	protected function getDirtyFields(array $models)
	{
		$updateFields = [];
		/**
		 * @var GrandModel $model
		 */
		foreach($models as $order => $model)
		{
			$updateFields = array_merge($updateFields, array_diff(array_keys($model->getDirty()), $updateFields));
		}
		return $updateFields;
	}

	/**
	 * Returns the collection as a simple array of objects, rather than toArray()'s recursive array mapping.
	 * @return array
	 */
	public function toItemArray()
	{
		return $this->items;
	}
    
}