<?php

/** @var Factory $factory */

use EvansKim\Resourcery\ResourceManager;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Str;


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

$factory->define(ResourceManager::class, function (Faker $faker) {
    $name = $faker->name;
    return [
        "title" => $name,
        "class" => 'App\\Resource\\' . Str::studly($name),
        "label" => $name,
        "uses" => 1,
    ];
});
