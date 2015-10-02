<?php
/**
 * Created by PhpStorm.
 * User: jamesspence
 * Date: 5/5/15
 * Time: 4:33 PM
 */

namespace Grandiloquent\Relations;

use Grandiloquent\GrandCollection;
use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;

class HasMany extends EloquentHasMany
{

    /**
     * @param array $models
     * @return array
     * @throws \Grandiloquent\Exception\MassEventFireException
     */
	public function saveMany($models)
	{
		$modelCollection = GrandCollection::make($models);
		return $modelCollection->saveMany()->toItemArray();
	}
    
}