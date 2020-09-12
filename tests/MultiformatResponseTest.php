<?php

namespace Tests;

use Exception;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use TiMacDonald\Multiformat\BaseMultiformatResponse;
use TiMacDonald\Multiformat\MimeExtension;
use TiMacDonald\Multiformat\MimeTypes;
use TiMacDonald\Multiformat\Multiformat;
use TiMacDonald\Multiformat\MultiformatResponseServiceProvider;
use TiMacDonald\Multiformat\Response;
use stdClass;

class MultiformatResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    protected function getPackageProviders($app)
    {
        return [
            MultiformatResponseServiceProvider::class,
        ];
    }

    public function test_can_instantiate_instance_with_make_and_data_is_available(): void
    {
        $instance = TestResponse::make(['property' => 'expected']);

        $this->assertSame('expected', $instance->property);
    }

    public function test_can_add_data_using_with_and_retrieve_with_magic_get(): void
    {
        $instance = (new TestResponse)->with(['property' => 'expected value']);

        $this->assertSame('expected value', $instance->property);
    }

    public function test_access_to_non_existent_attribute_throws_exception(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Accessing undefined attribute Tests\TestResponse::not_set');

        /**
         * @psalm-suppress UndefinedMagicPropertyFetch
         * @phpstan-ignore-next-line
         */
        (new TestResponse)->not_set;
    }

    public function test_with_merges_data(): void
    {
        $instance = new TestResponse;
        $instance->with(['property_1' => 'expected value 1']);
        $instance->with(['property_2' => 'expected value 2']);

        $this->assertSame('expected value 1', $instance->property_1);
        $this->assertSame('expected value 2', $instance->property_2);
    }

    public function test_with_overrides_when_passing_duplicate_key(): void
    {
        $instance = new TestResponse;
        $instance->with(['property' => 'first']);
        $instance->with(['property' => 'second']);

        $this->assertSame('second', $instance->property);
    }

    public function test_is_defaults_to_html_format(): void
    {
        Route::get('location', function (): Responsable {
            return new TestResponse;
        });

        $response = $this->get('location');

        $response->assertOk();
        $this->assertSame('expected html response', $response->content());
    }

    public function test_responds_to_extension_in_the_route(): void
    {
        Route::get('location.csv', function (): Responsable {
            return new TestResponse;
        });

        $response = $this->get('location.csv');

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function test_responds_to_accept_header(): void
    {
        Route::get('location', function (): Responsable {
            return new TestResponse;
        });

        $response = $this->get('location', [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $this->assertSame('expected json response', $response->content());
    }

    public function test_responds_to_first_matching_accepts_header(): void
    {
        Route::get('location', function (): Responsable {
            return new TestResponse;
        });

        $response = $this->get('location', [
            'Accept' => 'text/csv, text/css',
        ]);

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function test_responds_to_a_more_obscure_accept_header(): void
    {
        Route::get('location', function (): Responsable {
            return new TestResponse;
        });

        $response = $this->get('location', [
            'Accept' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        $response->assertOk();
        $this->assertSame('expected xlsx response', $response->content());
    }

    public function test_last_dot_segement_is_used_as_the_extension_type(): void
    {
        Route::get('websites/{domain}{format}', function (): Responsable {
            return new TestResponse;
        })->where('format', '.json');

        $response = $this->get('websites/timacdonald.me.json');

        $response->assertOk();
        $this->assertSame('expected json response', $response->content());
    }

    public function test_file_extension_takes_precendence_over_accept_header(): void
    {
        Route::get('location{format}', function (): Responsable {
            return new TestResponse;
        });

        $response = $this->get('location.csv', [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function test_root_domain_returns_html_by_default(): void
    {
        $this->config()->set('app.url', 'http://timacdonald.me');
        Route::get('', function (): Responsable {
            return new TestResponse;
        });

        $response = $this->get('');

        $response->assertOk();
        $this->assertSame('expected html response', $response->content());
    }

    public function test_root_domain_response_to_other_formats(): void
    {
        $this->config()->set('app.url', 'http://timacdonald.me');
        Route::get('.csv', function (): Responsable {
            return new TestResponse;
        });

        $response = $this->get('.csv');

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function test_query_string_has_no_impact(): void
    {
        Route::get('location', function (): Responsable {
            return new TestResponse;
        });

        $response = $this->get('location?format=.csv');

        $response->assertOk();
        $this->assertSame('expected html response', $response->content());
    }

    public function test_container_passes_request_into_format_methods(): void
    {
        Route::get('location.csv', function (): Responsable {
            return new class() extends BaseMultiformatResponse {
                public function toCsvResponse(Request $request): string {
                    $query = $request->query('parameter');

                    assert(is_string($query));

                    return $query;
                }
            };
        });

        $response = $this->get('location.csv?parameter=expected%20value');

        $response->assertOk();
        $this->assertSame('expected value', $response->content());
    }

    public function test_container_resolves_dependencies_in_format_methods(): void
    {
        $this->app->bind(stdClass::class, function () {
            $instance = new stdClass;
            $instance->property = 'expected value';
            return $instance;
        });
        Route::get('location.csv', function (): Responsable {
            return new class() extends BaseMultiformatResponse {
                public function toCsvResponse(stdClass $stdClass): string {
                    assert(is_string($stdClass->property));

                    return $stdClass->property;
                }
            };
        });

        $response = $this->get('location.csv');

        $response->assertOk();
        $this->assertSame('expected value', $response->content());
    }

    public function test_can_set_default_response_format(): void
    {
        Route::get('location', function (): Responsable {
            return TestResponse::make()->withFallbackExtension('csv');
        });

        $response = $this->get('location', ['Accept' => null]);

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function test_exception_is_throw_if_no_response_method_exists(): void
    {
        $this->expectExceptionMessage('Method Tests\TestResponse::toMp3Response() does not exist');

        Route::get('location{format}', function (): Responsable {
            return new TestResponse;
        });

        $this->get('location.mp3');
    }

    public function test_url_html_format_is_used_when_the_default_has_another_value(): void
    {
        Route::get('location{format}', function (): Responsable {
            return (new TestResponse)->withFallbackExtension('csv');
        });

        $response = $this->get('location.html');

        $response->assertOk();
        $this->assertSame('expected html response', $response->content());
    }

    public function test_can_override_formats(): void
    {
        $this->app->singleton(MimeTypes::class, function (Application $app): MimeTypes {
            return new MimeTypes(['text/csv' => 'json']);
        });
        Route::get('location', function (): Responsable {
            return (new TestResponse);
        });

        $response = $this->get('location', ['Accept' => 'text/csv']);

        $response->assertOk();
        $this->assertSame('expected json response', $response->content());
    }

    public function test_untyped_request_variable_is_passed_through(): void
    {
        Route::get('location.csv', function (): Responsable {
            return new class() extends BaseMultiformatResponse {
                use Multiformat;

                public function toCsvResponse($response): string {
                    return $response->input('query');
                }
            };
        });

        $response = $this->get('location.csv?query=expected query');

        $this->assertSame('expected query', $response->content());
    }

    private function config(): Repository
    {
        $config = $this->app->make('config');

        assert($config instanceof Repository);

        return $config;
    }
}

