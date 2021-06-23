<?php


namespace EvansKim\Resourcery\Command;


use EvansKim\Resourcery\ResourceAction;
use EvansKim\Resourcery\ResourceManager;
use EvansKim\Resourcery\ResourceModel;
use Illuminate\Console\Command;

class InstallResourceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resourcery:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Resourcery default models and actions.';

    /**
     * @var ResourceManager
     */
    protected $manager;
    /**
     * @var ResourceModel
     */
    protected $resourceClass;
    public function handle()
    {
        $defaults = [
            ['title'=>'user','table_name'=>'users','uses'=>1,'label'=>'사용자','class'=>'EvansKim\\Resourcery\\Owner'],
            ['title'=>'role','table_name'=>'roles','uses'=>1,'label'=>'권한','class'=>'EvansKim\\Resourcery\\Role'],
            ['title'=>'resource-manager','table_name'=>'resource_managers','uses'=>1,'label'=>'리소스관리자','class'=>'EvansKim\\Resourcery\\ResourceManager'],
            ['title'=>'resource-action','table_name'=>'resource_actions','uses'=>1,'label'=>'리소스액션','class'=>'EvansKim\\Resourcery\\ResourceAction']
        ];
        foreach ($defaults as $default){
            $model = ResourceManager::create($default);
            $model->actions->map(function( ResourceAction $action ){
                $action->auth_type = 'admin';
                $action->save();
            });
        }
        $this->info("Done!");
    }
}
