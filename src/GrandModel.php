<?php
/**
 * Created by PhpStorm.
 * User: jamesspence
 * Date: 5/5/15
 * Time: 5:11 PM
 */

namespace Grandiloquent;


use Grandiloquent\Relations\BelongsToMany;
use Grandiloquent\Relations\HasMany;
use Grandiloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

abstract class GrandModel extends Model
{

    use GrandModelTrait;
    
}