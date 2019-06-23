<?php

namespace Tests;

use Exception;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use TiMacDonald\MultiFormat\Response;
use stdClass;

class MultFormatResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    public function test_can_instantiate_instance_with_make_and_data_is_available()
    {
        $instance = TestResponse::make(['property' => 'expected']);

        $this->assertSame('expected', $instance->property);
    }

    public function test_can_add_data_using_with_and_retrieve_with_magic_get()
    {
        $instance = (new TestResponse)->with(['property' => 'expected value']);

        $this->assertSame('expected value', $instance->property);
    }

    public function test_access_to_non_existent_attribute_throws_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Accessing undefined attribute Tests\TestResponse::not_set');

        (new TestResponse)->not_set;
    }

    public function test_with_merges_data()
    {
        $instance = new TestResponse;
        $instance->with(['property_1' => 'expected value 1']);
        $instance->with(['property_2' => 'expected value 2']);

        $this->assertSame('expected value 1', $instance->property_1);
        $this->assertSame('expected value 2', $instance->property_2);
    }

    public function test_with_overrides_when_passing_duplicate_key()
    {
        $instance = new TestResponse;
        $instance->with(['property' => 1]);
        $instance->with(['property' => 2]);

        $this->assertSame(2, $instance->property);
    }

    public function test_is_defaults_to_html_format()
    {
        Route::get('location', function () {
            return new TestResponse;
        });

        $response = $this->get('location');

        $response->assertOk();
        $this->assertSame('expected html response', $response->content());
    }

    public function test_responds_to_extension_in_the_route()
    {
        Route::get('location.csv', function () {
            return new TestResponse;
        });

        $response = $this->get('location.csv');

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function test_responds_to_accept_header()
    {
        Route::get('location', function () {
            return new TestResponse;
        });

        $response = $this->get('location', [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $this->assertSame('expected json response', $response->content());
    }

    public function test_last_dot_segement_is_used_as_the_extension_type()
    {
        Route::get('websites/{domain}{format}', function () {
            return new TestResponse;
        })->where('format', '.json');

        $response = $this->get('websites/timacdonald.me.json');

        $response->assertOk();
        $this->assertSame('expected json response', $response->content());
    }

    public function test_file_extension_takes_precendence_over_accept_header()
    {
        Route::get('location{format}', function () {
            return new TestResponse;
        });

        $response = $this->get('location.csv', [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function test_root_domain_returns_html_by_default()
    {
        $this->app->config->set('app.url', 'http://timacdonald.me');
        Route::get('', function () {
            return new TestResponse;
        });

        $response = $this->get('');

        $response->assertOk();
        $this->assertSame('expected html response', $response->content());
    }

    public function test_root_domain_response_to_other_formats()
    {
        $this->app->config->set('app.url', 'http://timacdonald.me');
        Route::get('.csv', function () {
            return new TestResponse;
        });

        $response = $this->get('.csv');

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function test_query_string_has_no_impact()
    {
        Route::get('location', function () {
            return new TestResponse;
        });

        $response = $this->get('location?format=.csv');

        $response->assertOk();
        $this->assertSame('expected html response', $response->content());
    }

    public function test_container_passes_request_into_format_methods()
    {
        Route::get('location.csv', function () {
            return new class extends Response {
                public function toCsvResponse($request) {
                    return $request->query('parameter');
                }
            };
        });

        $response = $this->get('location.csv?parameter=expected%20value');

        $response->assertOk();
        $this->assertSame('expected value', $response->content());
    }

    public function test_container_resolves_dependencies_in_format_methods()
    {
        $this->app->bind(stdClass::class, function () {
            $instance = new stdClass;
            $instance->property = 'expected value';
            return $instance;
        });
        Route::get('location.csv', function () {
            return new class extends Response {
                public function toCsvResponse(stdClass $stdClass) {
                    return $stdClass->property;
                }
            };
        });

        $response = $this->get('location.csv');

        $response->assertOk();
        $this->assertSame('expected value', $response->content());
    }

    public function test_can_set_default_response_format()
    {
        Route::get('location', function () {
            return TestResponse::make()->withDefaultFormat('csv');
        });

        $response = $this->get('location');

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function test_exception_is_throw_if_no_response_method_exists()
    {
        $this->expectExceptionMessage('Method Tests\TestResponse::toXlsxResponse() does not exist');

        Route::get('location{format}', function () {
            return new TestResponse;
        });

        $response = $this->get('location.xlsx');
    }

    public function test_url_html_format_is_used_when_the_default_has_another_value()
    {
        Route::get('location{format}', function () {
            return (new TestResponse)->withDefaultFormat('csv');
        });

        $response = $this->get('location.html');

        $response->assertOk();
        $this->assertSame('expected html response', $response->content());
    }
}

class TestResponse extends Response
{
    public function toHtmlResponse()
    {
        return 'expected html response';
    }

    public function toJsonResponse()
    {
        return 'expected json response';
    }

    public function toCsvResponse()
    {
        return 'expected csv response';
    }
}

