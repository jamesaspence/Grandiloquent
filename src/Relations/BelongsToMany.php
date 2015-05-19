<?php
/**
 * Created by PhpStorm.
 * User: jamesspence
 * Date: 5/5/15
 * Time: 4:28 PM
 */

namespace App\Grandiloquent\Relations;

use App\Grandiloquent\GrandCollection;
use App\Grandiloquent\GrandModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;

class BelongsToMany extends EloquentBelongsToMany
{

    /**
     * @param array $models
     * @param array $joinings
     * @return array
     * @throws \App\Grandiloquent\Exception\MassEventFireException
     */
    public function saveMany(array $models, array $joinings = [])
	{
		$modelCollection = GrandCollection::make($models);
		$modelCollection->saveMany();
		/** @var GrandModel $model */
		foreach($modelCollection as $key => $model)
		{
			$this->attach($model->getKey(), array_get($joinings, $key), false);
		}
		$this->touchIfTouching();
		return $modelCollection->toItemArray();
	}
    
}