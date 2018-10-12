<?php
/**
 * Created by PhpStorm.
 * User: kilo
 * Date: 2018/5/5
 * Time: 22:28
 */

namespace stan\helpers;

class ArrayHelper extends \yii\helpers\ArrayHelper
{
    /**
     * 函数rebuildDimension把数组的全部值并如一个一维数组返回,不保留原先的键值;
     *
     * @param arr   array   [必须]    源数组;
     *
     * @return array;
     */
    public static function rebuildDimension(array $array)
    {
        $tmpArray = [];
        foreach($array as $value){
            if (is_array($value)){
                $tmpArray = array_merge($tmpArray, call_user_func(__METHOD__, $value));
            }else{
                $tmpArray[] = $value;
            }
        }
        
        return array_values($tmpArray);
    }
    
    /**
     * 函数countValues,统计数组元素的个数;
     *
     * @param arr   array   [必须]    源数组;
     *
     * @return int;
     */
    public static function count(array $array)
    {
        $count = 0;
        foreach($array as $value){
            $count += (is_array($value) ? call_user_func(__METHOD__, $value) : 1);
        }
        
        return $count;
    }
    
    /**
     * 查找一个数组一个值第一次出现的地方，没有找到该元素返回false；
     *
     * @param  array $array 数组；
     * @param  [type]  $find          需要查找的元素；
     * @param  boolean $caseSensitive 是否严格的比对类型；
     *
     * @return [type]
     */
    public static function findValue(array $array, $find, $caseSensitive = false)
    {
        foreach($array as $key => $value){
            if (is_array($value)){
                $_key = call_user_func(__METHOD__, $value, $find, $caseSensitive);
                if (false !== $_key){
                    return $key . '.' . $_key;
                }
            }else{
                if (($find instanceof \Closure && $find($value))
                    || ($caseSensitive ? $value === $find : $value == $find)
                ){
                    return $key;
                }
            }
        }
        
        return false;
    }
    
    /**
     * 方法rand,随机在数组里面取一部分元素;
     *
     * @param arr array [必选]    传入的数组;
     * @param number int [必选] 返回的元素个数;
     *
     * @return array:
     */
    public static function randSlice(array $array, $number)
    {
        shuffle($array);
        
        return array_slice($array, 0, $number, true);
    }
    
    /**
     * 合并两个set, 组成一个新的set;
     *
     * @param  array $setA setA
     * @param  array $setB setB
     * @param  callback $filterFunction 过滤规则, 默认不过滤;
     *
     * @return array;
     */
    public static function mergeSet(array $sets1, array $sets2, $filterCallback = true)
    {
        $sets = array_unique(array_merge($sets1, $sets2));
        
        if (null !== $filterCallback){
            if (true === $filterCallback){
                $sets = array_filter($sets);
            }else{
                $sets = array_filter($sets, $filterCallback);
            }
        }
        
        return array_values($sets);
    }
    
    /**
     * array_map的升级版, 递归;
     *
     * @param  array $array 需要处理的数组
     * @param  callable $callback 回调方法
     *
     * @return array
     */
    public static function arrayMap(array $array, $callback)
    {
        foreach($array as $key => $value){
            if (is_array($value)){
                $array[$key] = call_user_func(__METHOD__, $value, $callback);
            }else{
                $array[$key] = call_user_func($callback, $value, $key);
            }
        }
        
        return $array;
    }
    
    
    public static function sortTree(array $tree, callable $callable, $childrenKey = 'items')
    {
        foreach($tree as $key => $item){
            if (isset($item[$childrenKey])){
                $tree[$key][$childrenKey] = call_user_func(__METHOD__, $item[$childrenKey], $callable);
            }
        }
        
        usort($tree, $callable);
        
        return $tree;
    }
    
    public static function getTree(array $items, $parentKey = 'parent', $childrenKey = 'items', $parent = null)
    {
        if (empty($items)){
            return $items;
        }
        
        $tree = [];
        
        if (null === $parent){
            foreach($items as $id => $item){
                if (!isset($items[$id][$childrenKey])){
                    $items[$id][$childrenKey] = [];
                }
                if (isset($items[$item[$parentKey]])){
                    $items[$item[$parentKey]][$childrenKey][] = &$items[$id];
                }else{
                    $tree[] = &$items[$id];
                }
            }
        }else{
            foreach($items as $id => $item){
                if ($item[$parentKey] == $parent){
                    $items[$id][$childrenKey] = call_user_func(__METHOD__, $items, $parentKey, $childrenKey, $id);
                    $tree[] = $items[$id];
                }
            }
        }
        
        return $tree;
    }
    
    public static function getChildren(array $items, $parent, $parentKey = 'parent', $level = 1)
    {
        if (empty($items)){
            return [];
        }
        
        $level++;
        
        $children = [];
        foreach($items as $id => $item){
            if ($parent == static::getValue($item, $parentKey)){
                $children[$id] = $level;
                $_children = call_user_func(__METHOD__, $items, $id, $parentKey, $level);
                foreach($_children as $_id => $_level){
                    $children[$_id] = $_level;
                }
            }
        }
        arsort($children);
        
        return $children;
    }
    
    public static function getParents(array $items, $child, $parentKey = 'parent', $level = 1)
    {
        if (empty($items) || !isset($items[$child])){
            return [];
        }
        
        $level--;
        
        $parents = [];
        foreach($items as $id => $item){
            if ($id == static::getValue($items[$child], $parentKey)){
                $parents[$id] = $level;
                $_parents = call_user_func(__METHOD__, $items, $id, $parentKey, $level);
                foreach($_parents as $_id => $_level){
                    $parents[$_id] = $_level;
                }
            }
        }
        asort($parents);
        
        return $parents;
    }
    
    public static function toString(array $data, $srotType = SORT_DESC)
    {
        if (SORT_DESC == $srotType){
            krsort($data, SORT_NATURAL);
        }elseif (SORT_ASC == $srotType){
            ksort($data, SORT_NATURAL);
        }
        
        $buff = '';
        foreach($data as $key => $value){
            if (is_array($value)){
                $value = call_user_func(__METHOD__, $value, $srotType);
            }
            $buff .= "{$key}={$value}&";
        }
        
        return '' !== $buff ? substr($buff, 0, -1) : $buff;
    }
    
    public static function getFields($array, $fields, $skipExists = false)
    {
        $result = [];
        
        if ($skipExists){
            foreach($fields as $field){
                if (array_key_exists($field, $array)){
                    $result[$field] = $array[$field];
                }
            }
        }else{
            foreach($fields as $field){
                $result[$field] = static::getValue($array, $field);
            }
        }
        
        return $result;
    }
}

