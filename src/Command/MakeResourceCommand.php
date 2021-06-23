<?php

namespace EvansKim\Resourcery\Command;

use EvansKim\Resourcery\ResourceManager;
use EvansKim\Resourcery\ResourceModel;
use EvansKim\Resourcery\ValidationRules;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class MakeResourceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resourcery:make {name} {--table=} {--label=} {--only=*} {--vue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Resource Model, Controller, Route, VueGridComponent, VueRouter';

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
        $title = Str::snake( $this->argument('name') , '-' );
        // 영문자만 가능합니다.

        $preg_match = preg_match("/([a-z]+-?)+/", $title);
        if (!$preg_match) {
            $this->error('first argument should be snake case. ex) snake-case');
        }

        $studly = Str::studly($title);
        $class = Config::get('resourcery.model_namespace') .'\\'. $studly;

        $data = [
            'title' => $title,
            'class' => $class,
            'table_name' => $this->option('table') ?? null,
            'label' => $this->option('label') ?? null,
            'uses' => 0
        ];
        $this->loadOrCreateManager($data);

        if ($this->option('vue')) {
            $this->createVue();
            $this->info("프론트가 생성되었습니다.");
            return true;
        }
    }

    /**
     * @param $studly
     */
    protected function createVue()
    {
        $fields = $this->manager->getCompiledFields();
        foreach ($fields as $field => $type) {
            if ($type === 'primary') {
                $primaryField = $field;
            }
        }

        $list = implode(",\n", array_keys($fields));
        $this->info(" [n] = no show , write down label name for field ::  " . $list);
        $labels = [];
        foreach ($fields as $field => $type) {
            $name = trim($this->anticipate("Field [{$field}], Label:", [$field]));
            if (!in_array($name, ['n', 'N'])) {
                $labels[$field] = $name;
            } else {
                unset($fields[$field]);
            }
        }

        $DummyColumns = [];
        $DummyFormItems = [];
        $formItemTemplate = file_get_contents(__DIR__ . "/../../stubs/components/inputs/formitem.stub");
        foreach ($fields as $field => $type) {
            if ($type === 'primary') {
                $input = file_get_contents(__DIR__ . "/../../stubs/components/primary.stub");
                $formItem = null;
            }
            if (is_array($type)) {
                $input = file_get_contents(__DIR__ . "/../../stubs/components/select.stub");
                $formItem = file_get_contents(__DIR__ . "/../../stubs/components/inputs/select.stub");
                $option = file_get_contents(__DIR__ . "/../../stubs/components/select.option.stub");
                $options = [];
                foreach ($type[1] as $optVal) {
                    $options[] = preg_replace("/DummyValue/", $optVal, $option);
                }
                $formItem = preg_replace("/DummyOptions/", implode("\n", $options), $formItem);
                $input = preg_replace("/DummyOptions/", implode("\n", $options), $input);
            }

            {
                if ($type === 'date' || $type === 'datetime') {
                    $input = file_get_contents(__DIR__ . "/../../stubs/components/date.stub");
                    $input = preg_replace("/DummyType/", $type, $input);
                    $formItem = file_get_contents(__DIR__ . "/../../stubs/components/inputs/date.stub");
                    $formItem = preg_replace("/DummyType/", $type, $formItem);
                } else {
                    if ($type === 'boolean') {
                        $input = file_get_contents(__DIR__ . "/../../stubs/components/boolean.stub");
                        $formItem = file_get_contents(__DIR__ . "/../../stubs/components/inputs/boolean.stub");
                    } else {
                        if (is_array($type)) {
                            $input = file_get_contents(__DIR__ . "/../../stubs/components/select.stub");
                            $formItem = file_get_contents(__DIR__ . "/../../stubs/components/inputs/select.stub");
                            $option = file_get_contents(__DIR__ . "/../../stubs/components/select.option.stub");
                            $options = [];
                            foreach ($type[1] as $optVal) {
                                $options[] = preg_replace("/DummyValue/", $optVal, $option);
                            }
                            $formItem = preg_replace("/DummyOptions/", implode("\n", $options), $formItem);
                            $input = preg_replace("/DummyOptions/", implode("\n", $options), $input);
                        } else {
                            $input = file_get_contents(__DIR__ . "/../../stubs/components/string.stub");
                            $formItem = file_get_contents(__DIR__ . "/../../stubs/components/inputs/string.stub");
                        }
                    }
                }
            }

            $input = preg_replace("/DummyField/", $field, $input);
            $input = preg_replace("/DummyLabel/", $labels[$field], $input);
            $DummyColumns[] = $input;
            if (is_null($formItem)) {
                continue;
            }
            $formItem = preg_replace("/DummyInput/", $formItem, $formItemTemplate);
            $formItem = preg_replace("/DummyField/", $field, $formItem);
            $formItem = preg_replace("/DummyLabel/", $labels[$field], $formItem);
            $DummyFormItems[] = $formItem;
        }
        //Vue Component
        $content = $this->manager->replaceDummy(file_get_contents(__DIR__ . "/../../stubs/vue.grid.stub"));

        $content = preg_replace("/DummyColumns/", implode("\n", $DummyColumns), $content);
        $content = preg_replace("/DummyPrimary/", $primaryField, $content);
        $content = preg_replace("/DummyForm/", implode("\n", $DummyFormItems), $content);
        $studly = Str::studly($this->manager->studly);
        file_put_contents(resource_path("/assets/js/components/Resource/{$studly}ResourceGrid.vue"), $content);
        $this->info("Vue Grid Component :" . "/assets/js/components/Resource/{$studly}ResourceGrid.vue");
        //Vue Page
        $content = $this->manager->replaceDummy(file_get_contents(__DIR__ . "/../../stubs/vue.page.stub"));
        file_put_contents(resource_path("/assets/js/pages/resource/{$studly}ResourcePage.vue"), $content);
        $this->info("Vue Page :" . "/assets/js/pages/resource/{$studly}ResourcePage.vue");
        //Vue Router
        $this->manager->createVuePage();
    }

    /**
     * @param $studly
     * @return array|string
     */
    protected function getTableName()
    {
        $table = $this->option('table');
        if (!$table) {
            $table = $this->manager->default_table;
        }
        return $table;
    }

    /**
     *
     * @return ResourceManager|Model|null
     */
    protected function loadOrCreateManager(array $data)
    {
        if ($this->manager) {
            return $this->manager;
        }

        $this->manager = ResourceManager::where('title', $data['title'])->first();

        if (!$this->manager) {
            $this->manager = ResourceManager::create($data);
            $this->info("Manager Created");
        }

        return $this->manager;
    }
}
