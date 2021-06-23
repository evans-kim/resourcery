<?php


namespace EvansKim\Resourcery;


use Closure;
use Illuminate\Support\Facades\Schema;

class ValidationRules
{
    /**
     * @return Closure
     */
    public static function hasTable()
    {
        return function ($attribute, $value, $fail) {

            if (!Schema::hasTable($value)) {
                $msg = str_replace(':value', $value, trans('resourcery::validation.has_table'));
                $fail($msg);
            }
        };
    }

    /**
     * @return Closure
     */
    public static function snake()
    {
        return function ($attribute, $value, $fail) {

            $preg_match = preg_match("/^([a-z]+\-?)+$/", $value);
            if (!$preg_match) {
                $fail(trans('resourcery::validation.snake_case'));
            }
        };
    }
    /**
     * @return Closure
     */
    public static function snakeUnderBar()
    {
        return function ($attribute, $value, $fail) {

            $preg_match = preg_match("/^([a-z]+_?)+$/", $value);
            if (!$preg_match) {
                $fail(trans('resourcery::validation.snake_under_bar'));
            }
        };
    }

    /**
     * @return Closure
     */
    public static function studly()
    {
        return function ($attribute, $value, $fail) {

            if (!preg_match("/^[a-z]*([A-Z]{1}[a-z0-9]*$)*/i", $value)) {
                $fail(trans('resourcery::validation.studly'));
            }
        };
    }
}
