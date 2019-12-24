<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Repository;

use Collections\Vector;

class BaseObjectRepository
{
    protected Vector $collection;
    protected string $primaryKey = 'id';

    protected array $functions = [];

    public function __construct(array $collection = [])
    {
        $this->collection = new Vector($collection);
        $this->functions = [
            'in' => static function ($value, ...$params) {
                return in_array($value, $params, true);
            },
            '>' => static function ($value, ...$params) {
                return $value > array_unshift($params);
            },
            '>=' => static function ($value, ...$params) {
                return $value >= array_unshift($params);
            },
            '<' => static function ($value, ...$params) {
                return $value < array_unshift($params);
            },
            '<=' => static function ($value, ...$params) {
                return $value <= array_unshift($params);
            },
        ];
    }

    public function find($id): object
    {
        $pk = $this->primaryKey;
        return $this->collection->filter(function ($el) use (&$id, &$pk) { return $el->$pk === $id; })->first();
    }

    public function findAll(): Vector
    {
        return $this->collection->values();
    }

    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): Vector
    {
        $collection = $this->collection->filter(static function (object $object) use (&$criteria) {
            foreach ($criteria as $key => $value) {
                $field = null;
                $function = null;
                $params = null;
                if (is_integer($key) && is_array($value) && count($value) >= 2) {
                    $cnt = count($value);
                    if ($cnt === 2) {
                        [$field, $params] = $value;
                        $function = '=';
                    } else {
                        $field = array_unshift($value);
                        $function = array_unshift($value);
                        $params = $value;
                    }
                } else {
                    $field = $key;
                    $params = is_array($value) ? $value : [$value];
                    $function = 'in';
                }
                # matching
                if ($function === '=') {
                    $function = 'in';
                    $params = [$params];
                }
                if (!$this->functions[$function]($object->$field, ...$params)) {
                    return false;
                }
            }
            return true;
        });
        if ($limit !== null || $offset !== null) {
            $collection->splice($offset, $limit);
        }

        return $collection;
    }

    // public function findOneBy(array $criteria) {
    //     $criteriaObj = new Criteria($criteria);
    //     return $this->collection->matching($criteriaObj)->first();
    // }

    public function getRandom(): ?object
    {
        $count = $this->collection->count();
        if ($count === 0) {
            return null;
        }
        $key = mt_rand(0, $count - 1);
        return $this->collection->at($key);
    }
}
