<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Utils;

/**
 * Collection, an util to conventionally store a key-value pair.
 */
class Collection implements \Iterator {
    protected $data = array();
    
    /**
     * I think you are supposed to know what this does.
     * @param array|null $data
     */
    function __construct(array $data = null) {
        if(!empty($data)) {
            $this->data = $data;
        }
    }
    
    /**
     * @internal
     */
    function __debugInfo() {
        return $this->data;
    }
    
    /**
     * @internal
     */
    function current() {
        return \current($this->data);
    }
    
    /**
     * @internal
     */
    function key() {
        return \key($this->data);
    }
    
    /**
     * @internal
     */
    function next() {
        return \next($this->data);
    }
    
    /**
     * @internal
     */
    function rewind() {
        return \reset($this->data);
    }
    
    /**
     * @internal
     */
    function valid() {
        return (\current($this->data) !== false);
    }
    
    /**
     * Sets a key-value pair.
     * @param mixed  $key
     * @param mixed  $value
     * @return $this
     */
    function set($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Removes an item from the collection by its key.
     * @param  mixed  $key
     * @return $this
    */
    function delete($key) {
        $this->data[$key] = null;
        unset($this->data[$key]);
        return $this;
    }
    
    /**
     * Clears the Collection.
     * @return $this
     */
    function clear() {
        $this->data = array();
        return $this;
    }
    
    /**
     * Returns all items.
     * @return mixed[]
     */
    function all() {
        return $this->data;
    }
    
    /**
     * Gets the average of all items.
     * @param  callable|null  $closure
     * @return mixed
     */
    function avg(callable $closure = null) {
        $count = $this->count();
        if($count > 0) {
            return ($this->sum($closure) / $count);
        }
        
        return $this;
    }
    
    /**
     * Breaks the collection into multiple, smaller collections of a given size.
     * @param  int  $numitems
     * @param  bool $preserve_keys
     * @return Collection
    */
    function chunk(int $numitems, bool $preserve_keys = false) {
        return (new self(\array_chunk($this->data, $numitems, $preserve_keys)));
    }
    
    /**
     * Collapses a collection of arrays into a flat collection.
     * @return Collection
    */
    function collapse() {
        $new = array();
        
        foreach($this->data as $values) {
            if($values instanceof self) {
                $values = $values->all();
            } elseif(!\is_array($values)) {
                continue;
            }
            
            $new = \array_merge($new, $values);
        }
        
        return (new self($new));
    }
    
    /**
     * Combines the keys of the collection with the values of another array or collection.
     * @param  mixed  $values
     * @return Collection
    */
    function combine($values) {
        return (new self(\array_combine($this->data, $values)));
    }
    
    /**
     * Determines whether the collection contains a given item.
     * @param  callable|mixed   $item
     * @param  mixed            $value
     * @return bool
    */
    function contains($item, $value = "") {
        if(empty($item)) {
            return false;
        }
        
        foreach($this->data as $key => $val) {
            if($item instanceof \Closure) {
                $bool = (bool) $item($val, $key);
                if($bool) {
                    return true;
                }
            } else {
                if(!empty($value)) {
                    if($key == $item && $val == $value) {
                        return true;
                    }
                } else {
                    if($val == $item) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Returns the total number of items in the collection.
     * @return int
    */
    function count() {
        return \count($this->data);
    }
    
    /**
     * Returns a copy of itself.
     * @return Collection
     */
    function copy() {
        return (new self($this->data));
    }
    
    /**
     * Compares the collection against another collection or a plain PHP array based on its value.
     * @param  mixed[]|Collection  $arr
     * @return Collection
    */
    function diff($arr) {
        if($arr instanceof self) {
            $arr = $arr->all();
        }
        
        return (new self(\array_diff($this->data, $arr)));
    }
    
    /**
     * Compares the collection against another collection or a plain PHP array based on its key.
     * @param  mixed[]|Collection  $arr
     * @return Collection
    */
    function diffKeys($arr) {
        if($arr instanceof self) {
            $arr = $arr->all();
        }
        
        return (new self(\array_diff_key($this->data, $arr)));
    }
    
    /**
     * Iterates over the items in the collection and passes each item to a given callback.
     * @param  callable  $closure
     * @return $this
    */
    function each(callable $closure) {
        foreach($this->data as $key => $val) {
            $feed = $closure($val, $key);
            if($feed === false) {
                break;
            }
        }
        
        return $this;
    }
    
    /**
     * Creates a new collection consisting of every n-th element.
     * @param  int  $nth
     * @param  int  $offset
     * @return Collection
    */
    function every(int $nth, int $offset = 0) {
        $new = array();
        $size = \count($this->data);
        
        for($i = $offset; $i < $size; $i += $nth) {
            $new[] = $this->data[$i];
        }
        
        return (new self($new));
    }
    
    /**
     * Returns all items in the collection except for those with the specified keys.
     * @param  mixed[]  $keys
     * @return Collection
    */
    function except(array $keys) {
        $new = array();
        foreach($this->data as $key => $val) {
            if(!\in_array($key, $keys)) {
                $new[$key] = $val;
            }
        }
        
        return (new self($new));
    }
    
    /**
     * Filters the collection by a given callback, keeping only those items that pass a given truth test.
     * @param  callable  $closure
     * @return Collection
    */
    function filter(callable $closure) {
        $new = array();
        foreach($this->data as $key => $val) {
            $feed = (bool) $closure($val, $key);
            if($feed) {
                $new[$key] = $val;
            }
        }
        
        return (new self($new));
    }
    
    /**
     * Returns the first element in the collection that passes a given truth test.
     * @param  callable|null  $closure
     * @return mixed|null
    */
    function first(callable $closure = null) {
        foreach($this->data as $key => $val) {
            if($closure === null) {
                return $val;
            }
            
            $feed = (bool) $closure($val, $key);
            if($feed) {
                return $val;
            }
        }
        
        return null;
    }
    
    /**
     * Iterates through the collection and passes each value to the given callback. The callback is free to modify the item and return it, thus forming a new collection of modified items. Then, the array is flattened by a level.
     * @param  callable  $closure
     * @return Collection
    */
    function flatMap(callable $closure) {
        $data = $this->data;
        foreach($data as $key => $val) {
            $data[$key] = $closure($val, $key);
        }
        
        $data = $this->flattenDo($data, 1);
        return (new self($data));
    }
    
    /**
     * Flattens a multi-dimensional collection into a single dimension.
     * @param  int  $depth
     * @return Collection
    */
    function flatten($depth = 0) {
        $data = $this->flattenDo($this->data, $depth);
        return (new self($data));
    }
    
    /**
     * Swaps the collection's keys with their corresponding values.
     * @return Collection
    */
    function flip() {
        $data = @\array_flip($this->data);
        return (new self($data));
    }
    
    /**
     * Returns the item at a given key. If the key does not exist, null is returned.
     * @param  mixed  $key
     * @return mixed|null
    */
    function get($key) {
        if(isset($this->data[$key])) {
            return $this->data[$key];
        }
        
        return null;
    }
    
    /**
     * Groups the collection's items by a given key.
     * @param  mixed  $column
     * @return Collection
    */
    function groupBy($column) {
        if(empty($column)) {
            return $this;
        }
        
        $new = array();
        foreach($this->data as $key => $val) {
            if($column instanceof \Closure) {
                $key = $column($val, $key);
            } else {
                $key = $val[$column];
            }
            
            $new[$key][] = $val;
        }
        
        return (new self($new));
    }
    
    /**
     * Determines if a given key exists in the collection.
     * @param  mixed  $key
     * @return bool
    */
    function has($key) {
        return isset($this->data[$key]);
    }
    
    /**
     * Joins the items in a collection. Its arguments depend on the type of items in the collection. If the collection contains arrays or objects, you should pass the key of the attributes you wish to join, and the "glue" string you wish to place between the values.
     * @param  mixed  $col
     * @param  string $glue
     * @return string
    */
    function implode($col, string $glue = ', ') {
        $data = "";
        foreach($this->data as $key => $val) {
            if(\is_array($val)) {
                $data .= $glue.$val[$col];
            } elseif(\is_object($val)) {
                $data .= $glue.$val->$col;
            } else {
                $data .= $col.$val;
            }
        }
        
        return $data;
    }
    
    /**
     * Returns the position of the given value in the collection. Returns null if the given value couldn't be found.
     * @param  mixed  $value
     * @return int|null
     */
    function indexOf($value) {
        $i = 0;
        foreach($this->data as $val) {
            if($val === $value) {
                return $i;
            }
            
            $i++;
        }
        
        return null;
    }
    
    /**
     * Removes any values that are not present in the given array or collection.
     * @param  mixed[]|Collection  $arr
     * @return Collection
    */
    function intersect($arr) {
        if($arr instanceof self) {
            $arr = $arr->all();
        }
        
        return (new self(\array_intersect($this->data, $arr)));
    }
    
    /**
     * Returns true if the collection is empty; otherwise, false is returned.
     * @return bool
    */
    function isEmpty() {
        return (bool) empty($this->data);
    }
    
    /**
     * Keys the collection by the given key.
     * @param  mixed  $col
     * @return Collection
    */
    function keyBy($col) {
        $data = array();
        foreach($this->data as $key => $val) {
            if(!\is_array($val) && !\is_object($val)) {
                continue;
            }
            
            if($col instanceof \Closure) {
                $k = $col($val, $key);
            } else {
                if(\is_object($val)) {
                    $k = $val->$col;
                } else {
                    $k = $val[$col];
                }
            }
            
            $data[$k] = $val;
        }
        
        return (new self($data));
    }
    
    /**
     * Returns all of the collection's keys.
     * @return Collection
    */
    function keys() {
        return (new self(\array_keys($this->data)));
    }
    
    /**
     * Returns the last element in the collection that passes a given truth test.
     * @param  callable|null  $closure
     * @return mixed|null
    */
    function last(callable $closure = null) {
        $data = null;
        foreach($this->data as $key => $val) {
            if($closure === null) {
                $data = $val;
            } else {
                $feed = $closure($val, $key);
                if($feed) {
                    $data = $val;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Iterates through the collection and passes each value to the given callback. The callback is free to modify the item and return it, thus forming a new collection of modified items.
     * @param  callable  $closure
     * @return Collection
    */
    function map(callable $closure) {
        $keys = \array_keys($this->data);
        $items = \array_map($closure, $this->data, $keys);
        return (new self(\array_combine($keys, $items)));
    }
    
    /**
     * Return the maximum value of a given key.
     * @param  mixed  $key
     * @return int
    */
    function max($key = '') {
        if(!empty($key)) {
            $data = \array_column($this->data, $key);
        } else {
            $data = $this->data;
        }
        
        return \max($data);
    }
    
    /**
     * Merges the given array into the collection. Any string key in the array matching a string key in the collection will overwrite the value in the collection.
     * @param  string[]  $arr
     * @return Collection
    */
    function merge(array $arr) {
        return (new self(\array_merge($this->data, $arr)));
    }
    
    /**
     * Return the minimum value of a given key.
     * @param  mixed  $key
     * @return int
    */
    function min($key = '') {
        if(!empty($key)) {
            $data = \array_column($this->data, $key);
        } else {
            $data = $this->data;
        }
        
        return \min($data);
    }
    
    /**
     * Returns the items in the collection with the specified keys.
     * @param mixed[]  $keys
     * @return Collection
    */
    function only(array $keys) {
        $new = array();
        foreach($this->data as $key => $val) {
            if(\in_array($key, $keys)) {
                $new[$key] = $val;
            }
        }
        
        return (new self($new));
    }
    
    /**
     * Return the values from a single column in the input array.
     * @param  mixed    $key
     * @param  mixed    $index
     * @return Collection
    */
    function pluck($key, $index = null) {
        $data = array();
        
        foreach($this->data as $v) {
            $count = \count($data);
            $k = ($index ? (\is_array($v) ? (\array_key_exists($index, $v) ? $v[$index] : $count) : (\is_object($v) ? (\property_exists($v, $index) ? $v->$index : $count) : $count)) : $count);
            
            if(\is_array($v) && \array_key_exists($key, $v)) {
                $data[$k] = $v[$key];
            } elseif(\is_object($v) && \property_exists($v, $key)) {
                $data[$k] = $v->$key;
            }
        }
        
        return (new self($data));
    }
    
    /**
     * Removes and returns the last item from the collection.
     * @return mixed
    */
    function pop() {
        return \array_pop($this->data);
    }
    
    /**
     * Adds an item to the beginning of the collection.
     * @param  mixed       $value
     * @param  mixed|null  $key
     * @return Collection
    */
    function prepend($value, $key = null) {
        if(!empty($key) && !\is_int($key)) {
            $data = \array_unshift($this->data, $value);
        } else {
            $data = \array_merge(array($key => $value), $this->data);
        }
        
        return (new self($data));
    }
    
    /**
     * Removes and returns an item from the collection by its key.
     * @param  mixed  $key
     * @return mixed
    */
    function pull($key) {
        $value = $this->data[$key];
        $this->data[$key] = null;
        unset($this->data[$key]);
        
        return $value;
    }
    
    /**
     * Appends an item to the end of the collection.
     * @param  mixed  $value
     * @param  mixed  $key
     * @return $this
    */
    function push($value, $key = null) {
        if(!empty($key) && !\is_int($key)) {
            \array_push($this->data, $value);
        } else {
            $this->data = \array_merge($this->data, array($key => $value));
        }
        
        return $this;
    }
    
    /**
     * Returns one random item, or multiple random items inside a Collection, from the Collection.
     * @param  int  $num
     * @return mixed|Collection
    */
    function random(int $num = 1) {
        $rand = \array_rand($this->data, $num);
        if(\is_array($rand)) {
            return (new self($rand));
        }
        
        return $rand;
    }
    
    /**
     * Reduces the collection to a single value, passing the result of each iteration into the subsequent iteration.
     * @param  callable   $closure
     * @param  mixed|null $carry
     * @return mixed|null|void
    */
    function reduce(callable $closure, $carry = null) {
        foreach($this->data as $val) {
            $carry = $closure($carry, $val);
        }
        
        return $carry;
    }
    
    /**
     * Reverses the order of the collection's items.
     * @param  bool $preserve_keys
     * @return Collection
    */
    function reverse(bool $preserve_keys = false) {
        return (new self(\array_reverse($this->data, $preserve_keys)));
    }
    
    /**
     * Searches the collection for the given value and returns its key if found. If the item is not found, false is returned.
     * @param  callable|mixed   $needle
     * @param  bool             $strict
     * @return mixed|bool
    */
    function search($needle, bool $strict = false) {
        if($needle instanceof \Closure) {
            foreach($this->data as $key => $val) {
                $feed = (bool) $needle($val, $key);
                if($feed) {
                    return $key;
                }
            }
        } else {
            return \array_search($needle, $this->data, $strict);
        }
        
        return false;
    }
    
    /**
     * Removes and returns the first item from the collection.
     * @return mixed
    */
    function shift() {
        return \array_shift($this->data);
    }
    
    /**
     * Randomly shuffles the items in the collection.
     * @return Collection
    */
    function shuffle() {
        $data = $this->data;
        \shuffle($data);
        
        return (new self($data));
    }
    
    /**
     * Returns a slice of the collection starting at the given index.
     * @param  int      $offset
     * @param  int      $limit
     * @param  bool     $preserve_keys
     * @return Collection
    */
    function slice(int $offset, int $limit = null, bool $preserve_keys = false) {
        $data = $this->data;
        return (new self(\array_slice($data, $offset, $limit, $preserve_keys)));
    }
    
    /**
     * Sorts the collection.
     * @param  callable    $closure
     * @param  int         $options
     * @return Collection
    */
    function sort(callable $closure = null, $options = SORT_REGULAR) {
        $data = $this->data;
        
        if($closure instanceof \Closure) {
            \uasort($data, $closure);
        } else {
            \asort($data, $options);
        }
        
        return (new self($data));
    }
    
    /**
     * Sorts the collection by the given key.
     * @param  mixed|\Closure  $sortkey
     * @param  int             $options
     * @param  bool            $descending
     * @return Collection
    */
    function sortBy($sortkey, $options = SORT_REGULAR, bool $descending = false) {
        $sortkey = $this->valueRetriever($sortkey);
        
        $new = array();
        foreach($this->data as $key => $val) {
            $new[$key] = $sortkey($val, $key);
        }
        
        if($descending) {
            \arsort($new, $options);
        } else {
            \asort($new, $options);
        }
        
        $keys = \array_keys($new);
        foreach($keys as $key) {
            $new[$key] = $this->data[$key];
        }
        
        return (new self($new));
    }
    
    /**
     * Sorts the collection by the given key in descending order.
     * @param  mixed|\Closure  $sortkey
     * @param  int             $options
     * @return Collection
    */
    function sortByDesc($sortkey, $options = SORT_REGULAR) {
        return $this->sortBy($sortkey, $options, true);
    }
    
    /**
     * Removes and returns a slice of items starting at the specified index.
     * @param  int      $offset
     * @param  int      $length
     * @param  mixed[]  $replacement
     * @return Collection
    */
    function splice(int $offset, int $length = null, array $replacement = array()) {
        $data = $this->data;
        return (new self(\array_splice($data, $offset, $length, $replacement)));
    }
    
    /**
     * Returns the sum of all items in the collection.
     * @param  callable $closure
     * @return int
    */
    function sum(callable $closure = null) {
        if($closure === null) {
            return \array_sum($this->data);
        }
        
        $closure = $this->valueRetriever($closure);
        
        return $this->reduce(function ($result, $item) use ($closure) {
            return $result += $closure($item);
        }, 0);
    }
    
    /**
     * Returns a new collection with the specified number of items.
     * @param  int  $limit
     * @return Collection
    */
    function take(int $limit) {
        if($limit < 0) {
            return $this->slice($limit, \abs($limit));
        }
        
        return $this->slice(0, $limit);
    }
    
    /**
     * Returns all of the unique items in the collection.
     * @param  mixed  $key
     * @return Collection
    */
    function unique($key) {
        if($key === null) {
            return (new self(\array_unique($this->data, SORT_REGULAR)));
        }
        
        $key = $this->valueRetriever($key);
        
        $exists = array();
        return $this->filter(function ($item) use ($key, &$exists) {
            $id = $key($item);
            if(\in_array($id, $exists)) {
                return false;
            }
            
            $exists[] = $id;
            return true;
        });
    }
    
    /**
     * Returns a new collection with the keys reset to consecutive integers.
     * @return Collection
    */
    function values() {
        return (new self(\array_values($this->data)));
    }
    
    /**
     * Filters the collection by a given key / value pair.
     * @param  mixed  $key
     * @param  mixed  $value
     * @param  bool   $strict
     * @return Collection
    */
    function where($key, $value, bool $strict = false) {
        $data = array();
        
        foreach($this->data as $val) {
            if($strict) {
                $bool = ($val[$key] === $value);
            } else {
                $bool = ($val[$key] == $value);
            }
            
            if($bool) {
                $data[] = $val;
            }
        }
        
        return (new self($data));
    }
    
    /**
     * Filters the collection by a given key / value pair.
     * @param  mixed[]  $arr
     * @param  bool     $strict
     * @return Collection
    */
    function whereIn(array $arr, bool $strict = false) {
        $data = array();
        
        foreach($this->data as $val) {
            foreach($arr as $key => $value) {
                if($strict) {
                    $bool = ($val[$key] === $value);
                } else {
                    $bool = ($val[$key] == $value);
                }
                
                if($bool) {
                    $data[] = $val;
                }
            }
        }
        
        return (new self($data));
    }
    
    /**
     * Merges together the values of the given array with the values of the collection at the corresponding index.
     * @param  mixed[]  $arr
     * @return $this|Collection
    */
    function zip(array $arr) {
        $data = $this->data;
        foreach($arr as $key => $val) {
            if(isset($data[$key])) {
                $data[$key] = array($data[$key], $val);
            } else {
                $data[$key] = array($val);
            }
        }
        
        return (new self($data));
    }
    
    protected function dataGet($target, $key, $default = null) {
        if(\is_null($key)) {
            return $target;
        }
        
        if(!\is_array($key)) {
            $key = \explode('.', $key);
        }
        
        while(($segment = \array_shift($key)) !== null) {
            if($segment === '*') {
                if($target instanceof self) {
                    $target = $target->all();
                } elseif(!\is_array($target)) {
                    return $this->valueRetriever($default);
                }
                
                $result = \array_column($target, $key);
                if(\in_array('*', $key)) {
                    return (new self($result))->collapse();
                } else {
                    return $result;
                }
            }
            
            if(isset($target[$segment])) {
                $target = $target[$segment];
            } elseif(\is_object($target) && isset($target->$segment)) {
                $target = $target->$segment;
            } elseif($target instanceof \Closure) {
                return $target();
            } else {
                return $target;
            }
        }
        
        return $target;
    }
    
    protected function flattenDo(array $array, int $depth, int $in_depth = 0) {
        $data = array();
        foreach($array as $val) {
            if(\is_array($val) && ($depth == 0 || $depth > $in_depth)) {
                $data = \array_merge($data, $this->flattenDo($val, $depth, ($in_depth + 1)));
            } else {
                $data[] = $val;
            }
        }
        
        return $data;
    }
    
    protected function valueRetriever($value) {
        if($value instanceof \Closure) {
            return $value;
        }
        
        return function ($item) use ($value) {
            return $this->dataGet($item, $value);
        };
    }
}
