<?php

namespace Tests\Feature;

use EvansKim\Resourcery\Owner;
use EvansKim\Resourcery\ResourceAction;
use EvansKim\Resourcery\ResourceManager;
use EvansKim\Resourcery\ResourceModel;
use EvansKim\Resourcery\Role;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Schema;
use Mmo\Guzzle\Middleware\XdebugMiddleware;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

class ResourceryTest extends TestCase
{
    use DatabaseTransactions, WithFaker;
    /**
     * @var Owner
     */
    private $user;
    private $role;
    private $testData = [
        'title' => 'resource-test',
        'table_name' => 'resource_tests',
        'label' => '리소스 테스트',
        'uses' => true,
    ];
    private $xdebug = true;
    /**
     * @test
     */
    public function 리소스_메니저_생성_테스트()
    {

        $this->setupTestTable();
        $headers = $this->setupAuthUser(Owner::find(1));
        $response = $this->post('/api/v1/resource-manager', $this->testData, $headers);
        $response->assertStatus(201);
        $response->assertSee($this->testData['table_name']);

    }

    /**
     * Admin 은 관리자만 접근 가능 합니다.
     * @test
     */
    public function 리소스_hasRole_권한_테스트()
    {
        $this->setupTestTable();

        $client = $this->getClient();
        // 매니저 생성
        $response = $client->post('/api/v1/resource-manager', ['form_params' => $this->testData]);
        $this->assertStringContainsString($this->testData['title'], $response->getBody()->getContents());

        // 해당 리소스를 생성합니다.
        $model = $this->createResourceData();

        // 소유자는 접근할 수 없습니다.
        $response = $client->delete('/api/v1/' . $this->testData['title'] . '/' . $model->id);
        $this->assertTrue($response->getStatusCode() === 403);

        // 관리자로 지정되면 접근 할 수 있습니다.
        $this->user->setAsAdmin();

        $response = $client->delete('/api/v1/' . $this->testData['title'] . '/' . $model->id);
        $this->assertTrue($response->getStatusCode() === 204);


        $this->user->unsetAsAdmin();
    }

    /**
     * Admin 은 관리자만 접근 가능 합니다.
     * @test
     */
    public function 리소스_Admin_권한_테스트()
    {
        $this->setupTestTable();

        $client = $this->getClient();
        // 매니저 생성
        $response = $client->post('/api/v1/resource-manager', ['form_params' => $this->testData]);
        $this->assertStringContainsString($this->testData['title'], $response->getBody()->getContents());

        // 리소스를 생성합니다.
        $model = $this->createResourceData();

        // 소유자는 접근할 수 없습니다.
        $response = $client->delete('/api/v1/' . $this->testData['title'] . '/' . $model->id);
        $this->assertTrue($response->getStatusCode() === 403);

        // 관리자로 지정되면 접근 할 수 있습니다.
        $this->user->setAsAdmin();

        $response = $client->delete('/api/v1/' . $this->testData['title'] . '/' . $model->id);
        $this->assertTrue($response->getStatusCode() === 204);

        $this->user->unsetAsAdmin();

    }

    /**
     * Private 은 관리자 및 작성자만 접근 가능해야 합니다.
     * @test
     */
    public function 리소스_Private_권한_테스트()
    {
        $this->setupTestTable();

        $client = $this->getClient();
        // 매니저 생성
        $response = $client->post('/api/v1/resource-manager', ['form_params' => $this->testData]);
        $this->assertStringContainsString($this->testData['title'], $response->getBody()->getContents());

        // 해당 리소스를 생성합니다.
        $model = $this->createResourceData();

        // 소유자는 접근할 수 있습니다.
        $response = $client->get('/api/v1/' . $this->testData['title'] . '/' . $model->id);
        $this->assertStringContainsString($model->name, $response->getBody()->getContents());

        // 비소유자는 접근할 수 없습니다.
        $client = $this->getClient();
        $response = $client->get('/api/v1/' . $this->testData['title'] . '/' . $model->id);
        $this->assertStringContainsString('Not Authorized Resource Access', $response->getBody()->getContents());

        // 비소유자가 관리자로 지정되면 접근 할 수 있습니다.
        $this->user->setAsAdmin();

        $response = $client->get('/api/v1/' . $this->testData['title'] . '/' . $model->id);
        $this->assertStringContainsString($model->name, $response->getBody()->getContents());

        $this->user->unsetAsAdmin();
    }

    /**
     * @test
     */
    public function 부모값을_가진_라우트_처리_테스트()
    {
        $this->setupTestTable();

        $client = $this->getClient();
        // 매니저 생성
        $response = $client->post('/api/v1/resource-manager', ['form_params' => $this->testData]);
        $contents = $response->getBody()->getContents();
        $this->assertStringContainsString($this->testData['title'], $contents);

        // 해당 리소스를 생성합니다.
        $model = $this->createResourceData();
        $response = $client->get('/api/v1/' . $this->testData['title'] . '/' . $model->id);
        $this->assertStringContainsString($model->name, $response->getBody()->getContents());

        $manager = json_decode($contents);

        $actions = ResourceAction::where('resource_id', $manager->model->id)->get();
        $actions->map(function (ResourceAction $action) {
            $action->model_id = 'resource_test';
            $action->save();
        });
        // 해당 리소스를 생성합니다.
        $model = $this->createResourceData();
        // 소유자는 접근할 수 있습니다.
        $url = '/api/v1/user/' . $this->user->id . '/' . $this->testData['title'] . '/' . $model->id;
        $response = $client->get($url);
        $this->assertStringContainsString($model->name, $response->getBody()->getContents());

        $url = '/api/v1/user/' . $this->user->id . '/' . $this->testData['title'] ;
        $response = $client->get($url);
        $contents1 = $response->getBody()->getContents();

        $this->assertStringContainsString($model->name, $contents1);

    }

    /**
     * @test
     */
    public function 역할지정시_통과여부_테스트()
    {
        $this->setupTestTable();

        $role = Role::create(['title'=>$this->faker->jobTitle, 'level'=>2]);

        $client = $this->getClient();
        // 매니저 생성
        $response = $client->post('/api/v1/resource-manager', ['form_params' => $this->testData]);
        $contents = $response->getBody()->getContents();
        $this->assertStringContainsString($this->testData['title'], $contents);
        $manager = json_decode($contents)->model;
        // 해당 리소스를 생성합니다.
        $model = $this->createResourceData();
        $response = $client->get('/api/v1/' . $this->testData['title'] . '/' . $model->id);
        $this->assertStringContainsString($model->name, $response->getBody()->getContents());

        // 비소유자는 접근할 수 없습니다.
        $client = $this->getClient();
        $response = $client->get('/api/v1/' . $this->testData['title'] . '/' . $model->id);
        $this->assertStringContainsString('Not Authorized Resource Access', $response->getBody()->getContents());

        // 유저에 역할을 지정합니다.
        $this->user->roles()->attach($role->id);
        // 리소스 매니저에 역할을 지정합니다.
        ResourceManager::findOrFail($manager->id)->roles()->attach($role->id);

        // 비소유자라도 권한 설정이 되어 있다면 접근할 수 있습니다.
        $response = $client->get('/api/v1/' . $this->testData['title'] . '/' . $model->id);
        $this->assertStringContainsString($model->name, $response->getBody()->getContents());

        $role->delete();

    }

    /**
     * @param ResponseInterface $response
     * @return mixed
     */
    protected function getJsonData(ResponseInterface $response)
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    protected function setupTestTable(): void
    {
        $parentTest = $this->testData;
        $parentTest['title'] = 'parent-test';
        $parentTest['table_name'] = 'parent_tests';
        $parentTest['label'] = '부모 리소스 테스트';

        $this->setupManagerAndDB($this->testData);

    }

    /**
     * @return array
     */
    protected function setupAuthUser(Owner $user)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $user->api_token,
            'Accept' => 'application/json',
            //'Content-Type' => 'application/json',
        ];
        return $headers;
    }

    /**
     * @return Client
     */
    protected function getClient(Owner $user = null)
    {
        if (is_null($user)) {
            $this->user = factory(Owner::class)->create();
        } else {
            $this->user = $user;
        }
        $headers = $this->setupAuthUser($this->user);
        $options = [
            'base_uri' => 'http://pos.test',
            'timeout' => 1000.0,
            'headers' => $headers,
            //'cookies' => true,
            'http_errors' => false,
        ];
        if ($this->xdebug) {
            $xdebugMiddleware = XdebugMiddleware::create('phpstorm');
            $stack = HandlerStack::create();
            $stack->push($xdebugMiddleware);
            $options['handler'] = $stack;
        }
        return new Client($options);
    }

    /**
     * @return ResourceModel
     */
    protected function createResourceData(): ResourceModel
    {
        $manager = $this->getResourceManager();

        /** @var ResourceModel $model */
        $model = new $manager->class;
        $model->name = $this->faker->name;
        $model->user_id = $this->user->id;
        $model->save();

        return $model;
    }

    /**
     * @return ResourceManager|Builder|Model|object|null
     */
    protected function getResourceManager()
    {
        return ResourceManager::where('title', $this->testData['title'])->first();
    }

    protected function setupManagerAndDB($testData)
    {
        ResourceManager::where('table_name', $testData['table_name'])->delete();
        Schema::dropIfExists($testData['table_name']);
        Schema::create($testData['table_name'], function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        $this->beforeApplicationDestroyed(function () use ($testData) {
            //Schema::dropIfExists($testData['table_name']);
            ResourceManager::where('title', $testData['title'])->delete();
        });
    }
}
