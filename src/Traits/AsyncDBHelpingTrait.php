<?php

namespace MohitYogi\AsyncDBHelper\Traits;

use MohitYogi\AsyncDBHelper\Adapters\RedisAdapter;

trait AsyncDBHelpingTrait
{
    /**
     * Returns the eloquent object with hash_key 
     * which is used to store this item in redis.
     * 
     * @param array $array
     * 
     * @return Eloquent $model 
     */
    public static function create(array $array)
    {
        $obj = new self($array);
        return RedisAdapter::insertEntry($obj);
    }


    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        if($this->exists){
            return parent::save($options);
        }
        return (bool) RedisAdapter::insertEntry($this);
    }
}
