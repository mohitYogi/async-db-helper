<?php

namespace MohitYogi\AsyncDBHelper\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use MohitYogi\AsyncDBHelper\Constants\AsyncDBHelperConstants;

class MoveDataFromRedisToDBCommand extends Command
{

    protected $signature = 'move-to-db:redis {--batch_size=1000 : The number of records to move to DB}';

    protected $description = 'Move the data stored on redis to DB';

    public function handle()
    {
        $this->info("Command initiated: MoveDataFromRedisToDBCommand");
        $batch_size = $this->option("batch_size");
        $this->info("Batch size:" . $batch_size);
        self::moveRedisEntriesToDb($batch_size);
        $this->info("Command completed: MoveDataFromRedisToDBCommand");
    }


    public function moveRedisEntryToDb($hash_key)
    {
        $this->info("Moving record: " . $hash_key, 'vvv');

        $data = Redis::hgetall($hash_key);
        if (!empty($data)) {
            self::prepareValuesForDB($data);

            $model = app($data['model']);

            unset($data['model']);

            $this->fillModel($model,$data);

            //before insert methods run.
            if (method_exists($model, 'beforeMoveToRedis')) {
                $this->info("beforeMoveToRedis: " .$hash_key, 'vvv');
                $model->beforeMoveToRedis();
            }

            $id = $model::insertGetId($model->getAttributes()); // Inserting Data in DB
            $model->setAttribute($model->getKeyName(), $id);
            $model->exists = true;

            //after insert methods run.
            if (method_exists($model, 'afterMoveToRedis')) {
                $this->info("afterMoveToRedis: " .$hash_key, 'vvv');
                $model->afterMoveToRedis();
            }
        }

        Redis::transaction()
            ->del($hash_key) // Deleting data from Redis
            ->incr(AsyncDBHelperConstants::ENTRY_NEXT_MOVE_INDEX)->execute(); // Incrementing Entry id for Last move id

        $this->info("Moved record: " . $hash_key . ", id:" . ($id ?? ''));

        return $hash_key;
    }

    public function moveRedisEntriesToDb($batch_size = 1000)
    {
        $hash_key = null;
        $telescope_entry_next_move_id = null;

        if (!$this->isEntriesAvailable()) {
            $this->info("No entries available to move.");
            return;
        }

        $telescope_entry_id = Redis::get(AsyncDBHelperConstants::ENTRY_INDEX);

        if (!Redis::exists(AsyncDBHelperConstants::ENTRY_NEXT_MOVE_INDEX)) {
            Redis::set(AsyncDBHelperConstants::ENTRY_NEXT_MOVE_INDEX, 1);
            $telescope_entry_next_move_id = 1;
        } else {
            $telescope_entry_next_move_id = Redis::get(AsyncDBHelperConstants::ENTRY_NEXT_MOVE_INDEX);
        }
        $this->info("Records: " . $telescope_entry_id . ", " . $telescope_entry_next_move_id);

        for ($i = 0; ($i < $batch_size) && ($telescope_entry_id > $telescope_entry_next_move_id); $i++) {
            $this->info("Moving record: " . $i);
            $hash_key = AsyncDBHelperConstants::ENTRY_HASH_KEY . $telescope_entry_next_move_id;
            $this->moveRedisEntryToDb($hash_key);
            $telescope_entry_next_move_id += 1;
        }
    }

    private function isEntriesAvailable()
    {
        if (!Redis::exists(AsyncDBHelperConstants::ENTRY_INDEX)) {
            return false;
        }
        return true;
    }

    private function prepareValuesForDB(array &$array)
    {
        foreach ($array as $key => $value) {
            if (empty($value)) {
                unset($array[$key]);
            }
        }
    }

    private function fillModel(&$model, array $data)
    {
        $model->fill($data);
        $un_filled_keys = array_diff(array_keys($data), $model->getFillable());
        foreach($un_filled_keys as $key){
            $model->setAttribute($key,$data[$key]);
        }
    }
}
