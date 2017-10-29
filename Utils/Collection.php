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
class Collection {
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
     * @access private
     */
    function __debugInfo() {
        return $this->data;
    }
    
    /**
     * Sets a new key-value pair (or overwrites an existing key-value pair).
     * @param string $key
     * @param mixed $value
     * @return this
     */
    function set($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Removes an item from the collection by its key.
     *
     * @param  mixed  $key
     * @return this
    */
    function delete($key) {
        $this->data[$key] = NULL;
        unset($this->data[$key]);
        return $this;
    }
    
    /**
     * Clears the Collection.
     * @return this
     */
    function clear() {
        $this->data = array();
        return $this;
    }
    
    /**
     * Returns all items.
     *
     * @return mixed[]
     */
    function all() {
        return $this->data;
    }
    
    /**
     * Gets the average of all items.
     *
     * @param  \Closure|NULL  $closure
     * @return mixed
     */
    function avg($closure = NULL) {
        $count = $this->count();
        if($count > 0) {
            return ($this->sum($closure) / $count);
        }
        
        return $this;
    }
    
    /**
     * Breaks the collection into multiple, smaller collections of a given size.
     *
     * @param  int  $numitems
     * @param  bool $preserve_keys
     * @return Collection
    */
    function chunk($numitems, $preserve_keys = false) {
        return (new self(\array_chunk($this->data, $numitems, $preserve_keys)));
    }
    
    /**
     * Collapses a collection of arrays into a flat collection.
     *
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
     *
     * @param  mixed  $values
     * @return Collection
    */
    function combine($values) {
        return (new self(\array_combine($this->data, $values)));
    }
    
    /**
     * Determines whether the collection contains a given item.
     *
     * @param  string  $item
     * @param  mixed   $value
     * @return this|bool
    */
    function contains($item, $value = "") {
        if(!empty($item)) {
            return $this;
        }
        
        foreach($this->data as $key => $val) {
            if($item instanceof Closure) {
                $bool = $item($val, $key);
                return $bool;
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
    }
    
    /**
     * Returns the total number of items in the collection.
     *
     * @return int
    */
    function count() {
        return \count($this->data);
    }
    
    /**
     * Compares the collection against another collection or a plain PHP array based on its value.
     *
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
     *
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
     *
     * @param  \Closure  $closure
     * @return this
    */
    function each(\Closure $closure) {
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
     *
     * @param  int  $nth
     * @param  int  $offset
     * @return Collection
    */
    function every($nth, $offset = 0) {
        if(!\is_int($nth)) {
            return $this;
        }
        
        $new = array();
        for($i = $offset; $i < \count($this->data); $i += $nth) {
            $new[] = $this->data[$i];
        }
        
        return (new self($new));
    }
    
    /**
     * Returns all items in the collection except for those with the specified keys.
     *
     * @param  mixed[]  $keys
     * @return Collection
    */
    function except($keys) {
        if(!\is_array($keys)) {
            $keys = array($keys);
        }
        
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
     *
     * @param  \Closure  $closure
     * @return Collection
    */
    function filter(\Closure $closure) {
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
     *
     * @param  \Closure  $closure
     * @return Collection
    */
    function first(\Closure $closure) {
        foreach($this->data as $key => $val) {
            $feed = (bool) $closure($val, $key);
            if($feed) {
                return $val;
            }
        }
        
        return false;
    }
    
    /**
     * Iterates through the collection and passes each value to the given callback. The callback is free to modify the item and return it, thus forming a new collection of modified items. Then, the array is flattened by a level.
     *
     * @param  \Closure  $closure
     * @return Collection
    */
    function flatMap(\Closure $closure) {
        $data = $this->data;
        foreach($data as $key => $val) {
            $data[$key] = $closure($val, $key);
        }
        
        $data = $this->flatten_do($data, 1);
        return (new self($data));
    }
    
    /**
     * Flattens a multi-dimensional collection into a single dimension.
     *
     * @param  int  $depth
     * @return Collection
    */
    function flatten($depth = 0) {
        $data = $this->flatten_do($this->data, $depth);
        return (new self($data));
    }
    
    /**
     * Swaps the collection's keys with their corresponding values.
     *
     * @return Collection
    */
    function flip() {
        $data = @\array_flip($this->data);
        return (new self($data));
    }
    
    /**
     * Returns a new collection containing the items that would be present on a given page number.
     *
     * @param  int  $page
     * @param  int  $numitems
     * @return Collection
    */
    function forPage($page, $numitems) {
        $start = ($page * $numitems) - $numitems - 1;
        
        $data = \array_values($this->data);
        $new = array();
        for($i = $start; $i <= $start + $numitems; $i++) {
            $new[] = $data[$i];
        }
        
        return (new self($new));
    }
    
    /**
     * Returns the item at a given key. If the key does not exist, $default is returned.
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return Collection
    */
    function get($key, $default = NULL) {
        if(isset($this->data[$key])) {
            return $this->data[$key];
        }
        
        if($default instanceof Closure) {
            return $default();
        } else {
            return $default;
        }
    }
    
    /**
     * Groups the collection's items by a given key.
     *
     * @param  mixed  $column
     * @return Collection
    */
    function groupBy($column) {
        if(empty($column)) {
            return $this;
        }
        
        $new = array();
        foreach($this->data as $key => $val) {
            if($column instanceof Closure) {
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
     *
     * @param  mixed  $key
     * @return bool
    */
    function has($key) {
        return (bool) isset($this->data[$key]);
    }
    
    /**
     * Joins the items in a collection. Its arguments depend on the type of items in the collection. If the collection contains arrays or objects, you should pass the key of the attributes you wish to join, and the "glue" string you wish to place between the values.
     *
     * @param  mixed  $col
     * @param  string $glue
     * @return string
    */
    function implode($col, $glue = ', ') {
        $data = "";
        foreach($this->data as $key => $val) {
            if(\is_array($val)) {
                $data .= $glue.$val[$col];
            } else {
                $data .= $col.$val;
            }
        }
        
        return $data;
    }
    
    /**
     * Removes any values that are not present in the given array or collection.
     *
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
     *
     * @return bool
    */
    function isEmpty() {
        return (bool) empty($this->data);
    }
    
    /**
     * Keys the collection by the given key.
     *
     * @param  mixed  $col
     * @return Collection
    */
    function keyBy($col) {
        $data = array();
        foreach($this->data as $key => $val) {
            if(!\is_array($val)) {
                continue;
            }
            
            if($col instanceof Closure) {
                $k = $col($val, $key);
            } else {
                $k = $val[$col];
            }
            
            $data[$k] = $val;
        }
        
        return (new self($data));
    }
    
    /**
     * Returns all of the collection's keys.
     *
     * @return Collection
    */
    function keys() {
        return (new self(\array_keys($this->data)));
    }
    
    /**
     * Returns the last element in the collection that passes a given truth test.
     *
     * @param  \Closure  $closure
     * @return Collection
    */
    function last(\Closure $closure) {
        $data = false;
        foreach($this->data as $key => $val) {
            $feed = $closure($val, $key);
            if($feed) {
                $data = $val;
            }
        }
        
        return $data;
    }
    
    /**
     * Iterates through the collection and passes each value to the given callback. The callback is free to modify the item and return it, thus forming a new collection of modified items.
     *
     * @param  \Closure  $closure
     * @return Collection
    */
    function map(\Closure $closure) {
        $keys = \array_keys($this->data);
        $items = \array_map(closure, $this->data, $keys);
        return (new self(\array_combine($keys, $items)));
    }
    
    /**
     * Return the maximum value of a given key.
     *
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
     *
     * @param  string[]  $arr
     * @return Collection
    */
    function merge(array $arr) {
        return (new self(\array_merge($this->data, $arr)));
    }
    
    /**
     * Return the minimum value of a given key.
     *
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
     *
     * @param mixed[]  $keys
     * @return Collection
    */
    function only($keys) {
        if(!\is_array($keys)) {
            $keys = array($keys);
        }
        
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
     *
     * @param  string      $key
     * @param  string|NULL $index
     * @return Collection
    */
    function pluck($key, $index = NULL) {
        return (new self(\array_column($this->data, $key, $index)));
    }
    
    /**
     * Removes and returns the last item from the collection.
     *
     * @return mixed
    */
    function pop() {
        return \array_pop($this->data);
    }
    
    /**
     * Adds an item to the beginning of the collection.
     *
     * @param  mixed       $value
     * @param  mixed|NULL  $key
     * @return Collection
    */
    function prepend($value, $key = NULL) {
        if(!empty($key) && !\is_int($key)) {
            $data = \array_unshift($this->data, $value);
        } else {
            $data = \array_merge(array($key => $value), $this->data);
        }
        
        return (new self($data));
    }
    
    /**
     * Removes and returns an item from the collection by its key.
     *
     * @param  mixed  $key
     * @return mixed
    */
    function pull($key) {
        $value = $this->data[$key];
        $this->data[$key] = NULL;
        unset($this->data[$key]);
        
        return $value;
    }
    
    /**
     * Appends an item to the end of the collection.
     *
     * @param  mixed  $value
     * @param  mixed  $key
     * @return this
    */
    function push($value, $key = NULL) {
        if(!empty($key) && !\is_int($key)) {
            $data = \array_push($this->data, $value);
        } else {
            $data = \array_merge($this->data, array($key => $value));
        }
        
        return $this;
    }
    
    /**
     * Sets the given key and value in the collection.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return this
    */
    function put($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Returns a random item from the collection.
     *
     * @param  int  $num
     * @return mixed|Collection
    */
    function random($num = 1) {
        $rand = \array_rand($this->data, $num);
        if(\is_array($rand)) {
            return (new self($rand));
        }
        
        return $rand;
    }
    
    /**
     * Reduces the collection to a single value, passing the result of each iteration into the subsequent iteration.
     *
     * @param  \Closure  $closure
     * @param  mixed|NULL $carry
     * @return mixed|NULL|void
    */
    function reduce(\Closure $closure, $carry = NULL) {
        foreach($this->data as $val) {
            $carry = $closure($carry, $val);
        }
        
        return $carry;
    }
    
    /**
     * Filters the collection using the given callback. The callback should return true for any items it wishes to remove from the resulting collection.
     *
     * @param  \Closure  $closure
     * @return Collection
    */
    function reject(\Closure $closure) {
        $new = array();
        foreach($this->data as $key => $val) {
            $feed = $closure($val, $key);
            if($feed !== true) {
                $new[$key] = $val;
            }
        }
        
        return (new self($new));
    }
    
    /**
     * Reverses the order of the collection's items.
     *
     * @param  bool $preserve_keys
     * @return Collection
    */
    function reverse($preserve_keys = false) {
        return (new self(\array_reverse($this->data, $preserve_keys)));
    }
    
    /**
     * Searches the collection for the given value and returns its key if found. If the item is not found, false is returned.
     *
     * @param  \Closure|mixed  $needle
     * @param  bool             $strict
     * @return mixed|bool
    */
    function search($needle, $strict = false) {
        if($needle instanceof Closure) {
            foreach($this->data as $key => $val) {
                $feed = $needle($val, $key);
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
     *
     * @return mixed
    */
    function shift() {
        return \array_shift($this->data);
    }
    
    /**
     * Randomly shuffles the items in the collection.
     *
     * @return Collection
    */
    function shuffle() {
        $data = $this->data;
        \shuffle($data);
        return (new self($data));
    }
    
    /**
     * Returns a slice of the collection starting at the given index.
     *
     * @param  int      $offset
     * @param  int|NULL $limit
     * @param  bool     $preserve_keys
     * @return Collection
    */
    function slice($offset, $limit = NULL, $preserve_keys = false) {
        $data = $this->data;
        return (new self(\array_slice($data, $offset, $limit, $preserve_keys)));
    }
    
    /**
     * Sorts the collection.
     *
     * @param  \Closure|NULL  $closure
     * @param  const           $options
     * @return Collection
    */
    function sort($closure = NULL, $options = SORT_REGULAR) {
        $data = $this->data;
        if($closure instanceof Closure) {
            \uasort($data, $closure);
        } else {
            \asort($data);
        }
        
        return (new self($data));
    }
    
    /**
     * Sorts the collection by the given key.
     *
     * @param  mixed  $sortkey
     * @param  const  $options
     * @param  bool   $descending
     * @return Collection
    */
    function sortBy($sortkey, $options = SORT_REGULAR, $descending = false) {
        $sortkey = $this->value_retriever($sortkey);
        
        $new = array();
        foreach($this->data as $key => $val) {
            $new[$key] = $sortkey($value, $key);
        }
        
        if($descending) {
            \arsort($new, $options);
        } else {
            \asort($new, $options);
        }
        
        foreach(\array_keys($new) as $key) {
            $new[$key] = $this->data[$key];
        }
        
        return (new self($new));
    }
    
    /**
     * Sorts the collection by the given key in descending order.
     *
     * @param  mixed  $sortkey
     * @param  const  $options
     * @return Collection
    */
    function sortByDesc($sortkey, $options = SORT_REGULAR) {
        return $this->sortBy($sortkey, $options, true);
    }
    
    /**
     * Removes and returns a slice of items starting at the specified index.
     *
     * @param  int      $offset
     * @param  int      $length
     * @param  mixed[]  $replacement
     * @return Collection
    */
    function splice($offset, $length = NULL, $replacement = array()) {
        return (new self(\array_splice($this->data, $offset, $length, $replacement)));
    }
    
    /**
     * Returns the sum of all items in the collection.
     *
     * @param  \Closure|NULL $closure
     * @return int
    */
    function sum($closure = NULL) {
        if($closure === NULL) {
            return \array_sum($this->data);
        }
        
        $closure = $this->value_retriever($closure);
        
        return $this->reduce(function ($result, $item) use ($closure) {
            return $result += $closure($item);
        }, 0);
    }
    
    /**
     * Returns a new collection with the specified number of items.
     *
     * @param  int  $limit
     * @return Collection
    */
    function take($limit) {
        if($limit < 0) {
            return $this->slice($limit, \abs($limit));
        }
        
        return $this->slice(0, $limit);
    }
    
    /**
     * Converts the collection into a plain PHP array.
     *
     * @return mixed[]
    */
    function toArray() {
        return \array_map(function ($value) {
            if($value instanceof Arrayable) {
                return $value->toArray();
            } else {
                return $value;
            }
        }, $this->data);
    }
    
    /**
     * Converts the collection into a JSON string.
     *
     * @param  mixed  $options
     * @return string
    */
    function toJSON($options = 0) {
        return \json_encode($this->json_serialize(), $options);
    }
    
    /**
     * Iterates over the collection and calls the given callback with each item in the collection. The items in the collection will be replaced by the values returned by the callback.
     *
     * @param  \Closure  $closure
     * @return this
    */
    function transform(\Closure $closure) {
        $this->data = $this->map($closure)->all();
        return $this;
    }
    
    /**
     * Returns all of the unique items in the collection.
     *
     * @param  mixed  $key
     * @return Collection
    */
    function unique($key) {
        if($key === NULL) {
            return (new self(\array_unique($this->data, SORT_REGULAR)));
        }
        
        $key = $this->valueRetriever($key);
        
        $exists = array();
        return $this->reject(function ($item) use ($key, &$exists) {
            $id = $key($item);
            if(\in_array($id, $exists)) {
                return true;
            }
            $exists[] = $id;
        });
    }
    
    /**
     * Returns a new collection with the keys reset to consecutive integers.
     *
     * @return Collection
    */
    function values() {
        return (new self(\array_values($this->data)));
    }
    
    /**
     * Filters the collection by a given key / value pair.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @param  bool   $strict
     * @return Collection
    */
    function where($key, $value, $strict = false) {
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
     *
     * @param  mixed[]  $arr
     * @param  bool     $strict
     * @return Collection
    */
    function whereIn($arr, $strict = false) {
        $data = array();
        foreach($this->data as $val) {
            foreach(arr as $key => $value) {
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
     *
     * @param  mixed[]  $arr
     * @return this|Collection
    */
    function zip($arr) {
        if(!\is_array($zip)) {
            return $this;
        }
        
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
    
    private function data_get($target, $key, $default = NULL) {
        if(\is_null($key)) {
            return $target;
        }
        
        if(!\is_array($key)) {
            $key = \explode('.', $key);
        }
        
        while(($segment = \array_shift($key)) !== null) {
            if($segment === '*') {
                if($target instanceof Collection) {
                    $target = $target->all();
                } elseif(!\is_array($target)) {
                    return value($default);
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
            } else {
                if($value instanceof Closure) {
                    return $value();
                } else {
                    return $value;
                }
            }
        }
        return $target;
    }
    
    private function flatten_do($array, $depth, $in_depth = 0) {
        $data = array();
        foreach($array as $val) {
            if(\is_array($val) && ($depth == 0 || $depth > $in_depth)) {
                $data = \array_merge($data, $this->flatten_do($val, $depth, ($in_depth + 1)));
            } else {
                $data[] = $val;
            }
        }
        
        return $data;
    }
    
    private function json_serialize() {
        return \array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->json_serialize();
            } else {
                return $value;
            }
        }, $this->data);
    }
    
    protected function value_retriever($value) {
        if($value instanceof Closure) {
            return $value;
        }
        
        return function ($item) use ($value) {
            return $this->data_get($item, $value);
        };
    }
}
