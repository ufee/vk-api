<?php
/**
 * Created by PhpStorm.
 * User: getju_000
 * Date: 02.05.14
 * Time: 16:25.
 */
namespace getjump\Vk\Response;

use Closure;

/**
 * Class Response.
 */
class Response implements \ArrayAccess, \Countable, \Iterator
{
    /**
     * @var bool|array
     */
    public $items = false;
    public $count = false;

    /**
     * They can return just an response array.
     *
     * @var bool|array
     */
    public $data = false;

    private int $pointer = 0;
    private array $extendedFields = [];

    /**
     * Response constructor.
     *
     * @param array|object $data
     * @param Closure|bool|null $callback
     */
    public function __construct(array|object $data, $callback = null)
    {
        $data = (array) $data;

        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                continue;
            }

            $this->{$key} = $value;
            $this->extendedFields[] = $key;
        }

        if ($callback && isset($data['items'])) {
            foreach ($data['items'] as $d) {
                $this->items[] = call_user_func($callback, $d);
            }
        } else {
            $this->items = $data['items'] ?? false;
        }

        $this->count = $data['count'] ?? count($data);

        if (is_array($data) && !$this->items) {
            if ($callback) {
                foreach ($data as $d) {
                    $this->data[] = call_user_func($callback, $d);
                }
            } else {
                $this->data = $data;
            }
        }

        // TODO: Avoid hack
        if ($this->data) {
            $this->items = &$this->data;
        }
        if ($this->items) {
            $this->data = &$this->items;
        }
        if (is_object($data) && $callback) {
            $this->data = call_user_func($callback, $data);
        }
    }

    /**
     * This method takes Closure as argument, so every element from
     * response will go into this Closure.
     *
     * @param Closure $callback
     */
    public function each(Closure $callback): void
    {
        $data = &$this->items ?: $this->data ?: [];

        foreach ($data as $k => $v) {
            call_user_func($callback, $k, $v);
        }
    }

    /**
     * This method will return one element if id is not specified or element of array otherwise.
     *
     * @param bool|int $id
     *
     * @return mixed
     */
    public function get($id = false)
    {
        if (!$id) {
            if (is_array($this->data)) {
                return $this->data[0];
            } elseif (isset($this->items) && $this->items !== false) {
                return $this->items[0];
            } else {
                return $this->data;
            }
        } else {
            return $this->data[$id];
        }
    }

    public function extended()
    {
        $temp = [];

        foreach ($this->extendedFields as $key) {
            $temp[$key] = $this->{$key};
        }

        return (object) $temp;
    }

    /**
     * This magic method try to return field from response.
     *
     * @param $name
     *
     * @return bool
     */
    public function __get($name)
    {
        if (!is_array($this->data)) {
            return $this->data->{$name};
        } elseif (count($this->data) == 0 && is_object($this->data[0])) {
            return $this->data[0]->{$name};
        }

        return false;
    }

    /**
     * Just wrap over Response->get().
     *
     * @return mixed
     */
    public function one()
    {
        return $this->get();
    }

    /**
     * This method return raw Response->data.
     *
     * @return array|bool
     */
    public function getResponse()
    {
        return $this->data;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->itmes[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function rewind(): void
    {
        $this->pointer = 0;
    }

    public function current(): mixed
    {
        return $this->items[$this->pointer];
    }

    public function key(): int
    {
        return $this->pointer;
    }

    public function next(): void
    {
        $this->pointer++;
    }

    public function valid(): bool
    {
        return isset($this->items[$this->pointer]);
    }
}
