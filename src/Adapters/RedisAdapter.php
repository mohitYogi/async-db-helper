<?php

namespace MohitYogi\AsyncDBHelper\Adapters;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use MohitYogi\AsyncDBHelper\Constants\AsyncDBHelperConstants;

class RedisAdapter
{
    /**
     * @param $model
     * 
     * @return string hashkey
     * @author Subham Jobanputra
     */
    public static function insertEntry($model)
    {
        try {
            $hash_key = null;

            // dd(Redis::connection());

            if (!Redis::exists(AsyncDBHelperConstants::ENTRY_INDEX)) {
                Redis::set(AsyncDBHelperConstants::ENTRY_INDEX, 1);
            }

            $hash_key = AsyncDBHelperConstants::ENTRY_HASH_KEY . Redis::get(AsyncDBHelperConstants::ENTRY_INDEX);

            self::prepareValues($model);

            $prepared_values = self::prepareValuesForRedis($model);

            array_unshift($prepared_values, $hash_key);

            Redis::command(self::getSuitableRedisCommand(), $prepared_values);

            $model->setAttribute("redis_hash_key",$hash_key);

            Redis::incr(AsyncDBHelperConstants::ENTRY_INDEX);

            return $model;
        } catch (Exception $ex) {
            report($ex);
            Log::error("Exception while recording entry on redis", [$ex->getMessage()]);
        }
    }

    private static function prepareValues(&$model)
    {
        if($model->timestamps) {
            if ($model::CREATED_AT) {
                $model->setAttribute($model::CREATED_AT, Carbon::now()->toDateTimeString());
            }
            if ($model::UPDATED_AT) {
                $model->setAttribute($model::UPDATED_AT, Carbon::now()->toDateTimeString());
            }
        }
    }

    private static function prepareValuesForRedis($model)
    {
        $return_array = [];
    
        $return_array[] = 'model';
        $return_array[] = get_class($model);

        foreach ($model->getAttributes() as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $return_array[] = $key;
            $return_array[] =  $value;
        }
        return $return_array;
    }

    /**
     * Checks the version of the redis 
     * & provides command to use.
     * reference: https://redis.io/commands/hmset
     * 
     * @return String command
     */
    private static function getSuitableRedisCommand()
    {
        // HSET command is preferred to use after 4.0.0 version
        // for lower versions use HMSET
        $info = Redis::command("INFO");
        if (isset($info["Server"]) && isset($info["Server"]['redis_version'])) {
            $version = $info["Server"]['redis_version'];
            $version_arr = explode(".", $version);
            if (count($version_arr) > 0 && ((int) $version_arr[0] >= 4)) {
                return "HSET";
            }
        }
        return "HMSET";
    }
}