<?php

namespace EvansKim\Resourcery;


use EvansKim\Resourcery\Command\CacheResourceCommand;
use EvansKim\Resourcery\Command\InstallResourceCommand;
use EvansKim\Resourcery\Command\MakeResourceCommand;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class ResourceryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../resourcery.php', 'resourcery');
        $this->loadMigrationsFrom(__DIR__."/../migrations");
        $this->loadRoutesFrom(__DIR__ . '/../route.php');
        $this->app->make('Illuminate\Database\Eloquent\Factory')->load(__DIR__ . '/../factories');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'resourcery');

        $this->publishes([
            __DIR__.'/../lang' => resource_path('lang/vendor/resourcery'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CacheResourceCommand::class,
                InstallResourceCommand::class,
                MakeResourceCommand::class,
            ]);
        }

        // auth 드라이버 추가
        Auth::viaRequest('owner-token', function (Request $request) {
            return $this->getOwner($request);
        });
        
        // 설정 추가
        if (! $this->app->configurationIsCached()) {
            // 기존 config.auth 값을 가져 옵니다.
            // 커스텀 값을 추가합니다.
            $config = $this->app['config']->get('auth', []);
            if( ! Arr::get($config, 'guards.owner')){
                Arr::set($config, 'guards.owner', 'owner-token');
                $this->app['config']->set('auth', $config);
            }
        }


    }
    /**
     * @param Request $request
     * @return Model|null
     */
    protected function getOwner(Request $request)
    {
        $token = null;

        $token = $request->query('owner_token');

        if (empty($token)) {
            $token = $request->input('owner_token');
        }

        if (empty($token)) {
            $token = $request->bearerToken();
        }

        if (empty($token)) {
            $token = $request->getPassword();
        }

        $owner = DB::table('owner_tokens')
            ->where('owner_type', Owner::class)
            ->where('token', $token)->first();
        return $owner;
    }
}
