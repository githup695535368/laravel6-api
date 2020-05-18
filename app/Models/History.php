<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-12-16
 * Time: 17:13
 */

namespace App\Models;

/**
 * History
 *
 * @property integer $id
 * @property integer $data_id
 * @property string $table_name
 * @property string $operator
 * @property string $type
 * @property array $diff
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */

use Dingo\Api\Auth\Auth;
use Illuminate\Database\Eloquent\Model;

class History extends BaseModel
{
    const ACTION_UPDATE = "更新";
    const ACTION_DELETE = "删除";
    const ACTION_CREATE = "创建";
    const DIFF_TEXT_EMPTY = "";

    protected $description = '数据修改记录';
    protected $table = 'model_histories';
    protected $casts = [
        'diff' => 'array',
    ];

    protected static $disabled = false;

    /**
     * 全局禁用 Model History
     */
    public static function disable($condition = true)
    {
        static::$disabled = $condition;
    }



    public static function getColumnTester(Model $model)
    {
        $data = config('model_history.models');

        // 是否需要侦听记录
        $clsName = get_class($model);
        if (array_key_exists($clsName, $data)) {
            $config = $data[$clsName];
            if (isset($config['field']) && is_array($config['field'])) {
                return function ($key) use ($config) {
                    return in_array($key, $config['field']);
                };
            } elseif (isset($config['exclude']) && is_array($config['exclude'])) {
                return function ($key) use ($config) {
                    return !in_array($key, $config['exclude']);
                };
            } else {
                return function ($key) {
                    return true;
                }; // 默认全部保存
            }
        }
        return false;
    }

    public static function record(Model $model, $action)
    {
        if (static::$disabled) {
            return;
        }

        if (get_class($model) === __CLASS__) {
            return; // 防止手残加了当前Model
        }

        $tester = self::getColumnTester($model);
        if ($tester === false) {
            return;
        }

        $diff = self::recordDiff($model, $action, $tester);
        if ($action === self::ACTION_UPDATE && !$diff) {
            return;
        }

        $record = new self();
        $record->data_id = $model->getKey();
        $record->table_name = $model->getTable();
        $record->operator = operatorName();
        $record->type = $action;
        $record->diff = $diff;
        $record->save();
        \Log::info('ModelHistoryContext ' . $record->id, ['url' => \URL::full()]);
    }

    private static function recordDiff(Model $model, $action, $tester)
    {
        $res = [];

        switch ($action) {
            case self::ACTION_CREATE:
                foreach ($model->getAttributes() as $key => $value) {
                    $res [$key] = ['old' => null, 'new' => $value];
                }
                break;

            case self::ACTION_UPDATE:
                if ($changedAttrList = $model->getDirty()) {
                    $newModel = $model->fresh();
                    foreach ($changedAttrList as $key => $value) {
                        if (!in_array($key, ['created_at', 'updated_at']) && $tester($key)) {
                            $old = $model->getOriginal($key);
                            $new = $newModel->getOriginal($key);
                            if ($old != $new) {
                                $res[$key] = ['old' => $old, 'new' => $new];
                            }
                        }
                    }
                }
                break;

            case self::ACTION_DELETE:
                foreach ($model->getAttributes() as $key => $value) {
                    $res [$key] = ['old' => $value, 'new' => null];
                }
                break;

            default:
                throw new \Exception("recordDiff异常: " . $action);
        }
        return $res;
    }


}
