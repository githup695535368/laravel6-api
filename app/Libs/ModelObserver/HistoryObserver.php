<?php namespace ModelObserver;

use App\Models\History;
use Illuminate\Database\Eloquent\Model;

/**
 * 记录数据修改历史
 *
 * Class HistoryObserver
 * @package ModelObserver
 */
class HistoryObserver
{
    public function created(Model $model)
    {
        History::record($model, History::ACTION_CREATE);
    }

    public static function updated(Model $model)
    {
        History::record($model, History::ACTION_UPDATE);
    }

    public function deleted(Model $model)
    {
        History::record($model, History::ACTION_DELETE);
    }

}
