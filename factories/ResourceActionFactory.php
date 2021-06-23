<?php

/** @var Factory $factory */

use EvansKim\Resourcery\ResourceAction;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;


/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(ResourceAction::class, function (Faker $faker) {
    return [
        'resource_id' => '',
        'function_name' => '',
        'auth_type' => '',
    ];
});
