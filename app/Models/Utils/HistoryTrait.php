<?php namespace App\Models\Utils;

/**
 * Class HistoryTrait
 * @package General
 */
trait HistoryTrait
{
    public static function bootHistoryTrait()
    {
        static::observe(\ModelObserver\HistoryObserver::class);
    }

}
