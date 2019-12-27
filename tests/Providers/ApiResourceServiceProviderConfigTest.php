<?php
namespace W2w\Laravel\Apie\Tests\Providers;

use erasys\OpenApi\Spec\v3\Server;
use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;
use W2w\Laravel\Apie\Tests\Mocks\DomainObjectForFileStorage;
use W2w\Lib\Apie\ApiResourceFacade;
use W2w\Lib\Apie\ApiResources\ApplicationInfo;
use W2w\Lib\Apie\ApiResources\Status;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSpecGenerator;

class ApiResourceServiceProviderConfigTest extends AbstractLaravelTestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = $app->make('config');
        // Setup default database to use sqlite :memory:
        $config->set('database.default', 'testbench');
        $config->set(
            'database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            ]
        );
        $config->set(
            'apie',
            [
                'resources' => [ApplicationInfo::Class, Status::class, DomainObjectForFileStorage::class],
                'metadata'               => [
                    'title'            => 'Laravel REST api',
                    'version'          => '1.0',
                    'hash'             => '12345',
                    'description'      => 'OpenApi description',
                    'terms-of-service' => '',
                    'license'          => 'Apache 2.0',
                    'license-url'      => 'https://www.apache.org/licenses/LICENSE-2.0.html',
                    'contact-name'     => 'contact name',
                    'contact-url'      => 'example.com',
                    'contact-email'    => 'admin@example.com',
                ]
            ]
        );
    }

    public function testApiResourceFacade()
    {
        /** @var ApiResourceFacade $class */
        $class = $this->app->get(ApiResourceFacade::class);
        $this->assertInstanceOf(ApiResourceFacade::class, $class);
        $appResponse = $class->get(ApplicationInfo::class, 'name', null);
        /** @var App $resource */
        $resource = $appResponse->getResource();
        $expected = new ApplicationInfo(
            'Laravel',
            'testing',
            '12345',
            false
        );
        $this->assertEquals($expected, $resource);
    }

    public function testOpenApiSchema()
    {
        /** @var OpenApiSpecGenerator $class */
        $class = $this->app->get(OpenApiSpecGenerator::class);
        $this->assertInstanceOf(OpenApiSpecGenerator::class, $class);
        $spec = $class->getOpenApiSpec();
        $this->assertEquals('OpenApi description', $spec->info->description);
        $this->assertEquals('admin@example.com', $spec->info->contact->email);
        $this->assertEquals('https://example.com', $spec->info->contact->url);
        $this->assertCount(1, $spec->servers);
        $expected = new Server(
            'http://localhost/api',
            null,
            null
        );
        $this->assertEquals($expected, reset($spec->servers));
    }
}
