<?php


namespace EvansKim\Resourcery\Command;


use EvansKim\Resourcery\ResourceManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CacheResourceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resourcery:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caching all routes.';

    public function handle()
    {
        $managers = ResourceManager::whereNotIn(
            'title',
            [
                'resource-manager',
                'resource-action',
                'user',
                'role',
            ]
        )->get();
        $contents = "<?php\n\nuse Illuminate\Support\Facades\Route;\nuse Illuminate\Support\Facades\Gate;\n\n";
        foreach ($managers as $manager) {
            $contents .= $manager->routesToString();
        }

        File::put(
            config('resourcery.cache_path'),
            $contents
        );
    }
}
