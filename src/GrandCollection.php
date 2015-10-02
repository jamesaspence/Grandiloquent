<?php
/**
 * Created by PhpStorm.
 * User: jamesspence
 * Date: 5/8/15
 * Time: 9:20 AM
 */

namespace Grandiloquent;

use Grandiloquent\Exception\MassEventFireException;
use DB;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class GrandCollection extends EloquentCollection
{

    use GrandCollectionTrait;
    
}