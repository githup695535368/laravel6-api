<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-07-30
 * Time: 12:10
 */

namespace App\Libs\Support;


use Illuminate\Support\Collection;

class BatchLoader
{
    /**
     * 批量加载关联数据
     *
     * @param array|Collection $data
     * @param string $relation
     * @param string $foreignKeyName
     * @param callable|array|Collection $loader
     * @param string $resultKeyName
     * @param bool $dimension 维度（false：一维）
     *
     * @return array|Collection
     */
    public static function eagerLoad(
        $data,
        string $relation,
        string $foreignKeyName,
        $loader,
        $resultKeyName = 'id',
        $dimension = false
    ) {
        if (!$data instanceof Collection) {
            $collection = new Collection($data);
        } else {
            $collection = $data;
        }

        $ids = $collection->pluck($foreignKeyName)->unique()->filter()->toArray();
        if (empty($ids)) {
            return $data;
        }
        if (is_callable($loader)) {
            $results = collect(call_user_func_array($loader, [$ids]));
        } else {
            $results = collect($loader);
        }

        $results = $dimension === false ? $results->keyBy($resultKeyName) : $results->groupBy($resultKeyName);

        foreach ($data as &$item) {
            $foreignKey = data_get($item, $foreignKeyName);
            if (!is_null($foreignKey)) {
                data_set($item, $relation, $results->get($foreignKey));
            }
        }

        return $data;
    }
}
