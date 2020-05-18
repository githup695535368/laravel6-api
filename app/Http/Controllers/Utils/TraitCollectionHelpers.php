<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-07-30
 * Time: 12:08
 */

namespace App\Http\Controllers\Utils;


use App\Libs\Support\BatchLoader;
use Illuminate\Support\Collection;

trait TraitCollectionHelpers
{
    protected function pluckAsUniqueArray($data, $column)
    {
        if (!$data instanceof Collection) {
            $data = collect($data);
        }

        return $data->pluck($column)->unique()->toArray();
    }

    /**
     * 批量加载关联数据
     *
     * @param array|Collection $data
     * @param string $relation
     * @param string $foreignKeyName
     * @param callable $loader
     * @param string $resultKeyName
     * @param bool $dimension
     *
     * @return array|Collection
     */
    protected function eagerLoad(
        $data,
        string $relation,
        string $foreignKeyName,
        callable $loader,
        $resultKeyName = 'id',
        $dimension = false
    ) {
        return BatchLoader::eagerLoad($data, $relation, $foreignKeyName, $loader, $resultKeyName, $dimension);
    }

}