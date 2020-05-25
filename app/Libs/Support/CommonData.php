<?php

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;

/**
 * 通用数据对象
 *
 * 推荐用于封装调用三方 API 返回的响应体，替代数组
 *
 * Class CommonData
 */
class CommonData implements ArrayAccess, Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    public static function create($data)
    {
        return new static($data);
    }

    public static function createFromJson($json)
    {
        $data = json_decode($json, JSON_OBJECT_AS_ARRAY);
        return new static($data);
    }

    /**
     * @return Collection|static[]
     */
    public static function createList($dataList): Collection
    {
        $list = new Collection();
        foreach ($dataList as $item) {
            $list->push(new static($item));
        }
        return $list;
    }

    protected $data;

    public function __construct($data = [])
    {
        $this->setData($data);
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function get($key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    /** 下面接口函数可以使此对象当做数组使用 */

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function count()
    {
        return count($this->data);
    }

    public function jsonSerialize()
    {
        return json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function toArray()
    {
        return $this->data;
    }

    public function toJson($options = 0)
    {
        return $this->jsonSerialize();
    }

    public function __toString()
    {
        return $this->jsonSerialize();
    }
}
