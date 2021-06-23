<?php

namespace EvansKim\Resourcery;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Trait Searchable
 * @package EvansKim\Resourcery
 *
 * @method static Builder search(Request $request)
 */
trait Searchable
{
    public function getCustomSort()
    {
        return [
            //'name' => 'order'
        ];
    }

    public function getSearchable()
    {
        return [
            ['name', 'like', '%{value}%']
        ];
    }

    /**
     * 데이터 조회 기능을 추상화
     * @param $query
     * @param Request $request
     * @param bool $or
     * @return Builder
     */
    public function scopeSearch($query, Request $request, $or = false)
    {

        /**
         * @var $query Builder
         */
        $this->setSearchQuery($query, $request);

        $this->setSortingQuery($query, $request);

        return $query;
    }

    /**
     * @param Builder $query
     * @param Request $request
     */
    private function setSortingQuery($query, Request $request)
    {
        // 데이터 정렬
        if ($request->has('sort') && $request->has('order')) {
            if (Arr::has($this->getCustomSort(), $request->sort)) {
                $orderField = $this->getCustomSort()[$request->sort];
                $query->orderBy($orderField, ($request->order == 'descending') ? 'desc' : 'asc');
            } else {
                $query->orderBy($request->sort, ($request->order == 'descending') ? 'desc' : 'asc');
            }
        }
    }

    /**
     * @param Builder $query
     * @param Request $request
     */
    private function setSearchQuery($query, Request $request)
    {

        if (empty($this->getSearchable())) {
            return false;
        }
        // 받은 쿼리스트링을 기준에 해당 하는 것만 찾습니다.
        foreach ($request->query as $field => $val) {

            // 정렬 쿼리는 제외 합니다.
            if (in_array($query, ['order', 'sort']))
                continue;

            // 필드명만 지정된 경우, 빈 값은 통과합니다.
            if (in_array($field, $this->getSearchable())) {
                if (!empty($val))
                    $query->where($field, $val);

                continue;
            }

            // 클로저는 키 값이 있는 배열입니다.
            $scope = isset($this->getSearchable()[$field]) ? $this->getSearchable()[$field] : null;

            // 클로저가 등록된 경우
            if (is_callable($scope)) {
                $query->where(function ($query) use ($scope, $val) {
                    $scope($query, $val);
                });
                continue;
            }
            // 그룹핑되는 스코프
            if (is_array($scope)) {
                $query->where(...$this->parseValue($scope, $val));
                continue;
            }

            if (is_null($scope)) {
                $scope = array_values(array_filter(array_filter($this->getSearchable(), function ($item) {
                    return is_array($item) && !is_callable($item);
                }), function ($item) use ($field) {
                    return Arr::first($item) === $field;
                }));

                if ($scope) {
                    $query->where(...$this->parseValue($scope, $val));
                }

            }


        }
    }

    protected function parseValue(array &$array, $val)
    {

        if (isset($array[0]) && is_array($array[0])) {

            return $this->parseValue($array[0], $val);
        }

        if (isset($array[2]) && is_string($array[2]) && preg_match('/{value}/i', $array[2])) {
            $array[2] = str_replace("{value}", $val, $array[2]);
        }
        if (isset($array[1]) && is_string($array[1]) && preg_match('/{value}/i', $array[1])) {
            $array[2] = str_replace("{value}", $val, $array[1]);
            $array[1] = 'like';
        }

        return $array;

    }

    public static function getSearchRules()
    {
        $rules = [];
        $model = new self;
        foreach ($model->getSearchable() as $search) {
            $rules = array_merge($rules, $model->getRole($search));
        }
        return $rules;
    }

    private function getRole($search)
    {

        if (is_string($search)) {
            return [$search => 'nullable|string'];
        }

        if (is_array($search) && isset($search[0]) && is_string($search[0])) {

            return [$search[0] => 'nullable|string'];
        }
        if (is_array($search) && is_string($field = key($search))) {

            return [$field => 'nullable|string'];
        }

    }
}
