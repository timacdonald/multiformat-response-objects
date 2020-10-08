<?php

declare(strict_types=1);

namespace Tests;

use function assert;
use Closure;
use Exception;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use function is_string;
use Orchestra\Testbench\TestCase;
use function response;
use stdClass;
use function TestResponse;
use TiMacDonald\Multiformat\BaseSuperResponse;
use TiMacDonald\Multiformat\Checkers\UrlContentType;
use TiMacDonald\Multiformat\Contracts\ApiFallback;
use TiMacDonald\Multiformat\MimeMap;
use TiMacDonald\Multiformat\SuperResponse;
use TiMacDonald\Multiformat\SuperResponseServiceProvider;

/**
 * @small
 */
class SuperResponseTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    protected function getPackageProviders($app)
    {
        return [
            SuperResponseServiceProvider::class,
        ];
    }

    public function testItInstantiatesAnInstanceWithMakeAndDataIsAvailable(): void
    {
        $instance = TestResponse::make(['property' => 'expected']);

        $this->assertSame('expected', $instance->property);
    }

    public function testItAddsDataUsingWithAndRetrieveWithMagicGet(): void
    {
        $instance = (new TestResponse())->with(['property' => 'expected value']);

        $this->assertSame('expected value', $instance->property);
    }

    public function testItThrowsExceptionAccessingNonExistentAttributes(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Accessing undefined attribute Tests\\TestResponse::not_set');

        /**
         * @psalm-suppress UndefinedMagicPropertyFetch
         * @phpstan-ignore-next-line
         */
        (new TestResponse())->not_set;
    }

    public function testItMergesDataUsingWith(): void
    {
        $instance = new TestResponse();
        $instance->with(['property_1' => 'expected value 1']);
        $instance->with(['property_2' => 'expected value 2']);

        $this->assertSame('expected value 1', $instance->property_1);
        $this->assertSame('expected value 2', $instance->property_2);
    }

    public function testItOverridesWhenPassingDuplicateKeyToWith(): void
    {
        $instance = new TestResponse();
        $instance->with(['property' => 'first']);
        $instance->with(['property' => 'second']);

        $this->assertSame('second', $instance->property);
    }

    public function testItInjectsArgumentsThroughContructorUsingNew(): void
    {
        $instance = TestResponse::new('expected');

        $this->assertSame('expected', $instance->constructorArg);
    }

    public function testItFallbacksToFallbackMethod(): void
    {
        Route::get('location', static function (): Responsable {
            return new TestResponse();
        });

        $response = $this->get('location', ['Accept' => null]);

        $response->assertOk();
        $this->assertSame('expected fallback response', $response->content());
    }

    public function testItAllowsRebindingOfFallback(): void
    {
        $this->app->bind(ApiFallback::class, static function (): Closure {
            return static function ($response): array {
                return [$response, 'toCsvResponse'];
            };
        });
        Route::get('location', static function (): Responsable {
            return new TestResponse();
        });

        $response = $this->get('location', ['Accept' => null]);

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function testItCanRebindFallbackToAnythingUnrelatedToTheResponseObject(): void
    {
        $this->app->bind(ApiFallback::class, static function (): Closure {
            return static function (): Closure {
                return static function () {
                    return 'expected random response';
                };
            };
        });
        Route::get('location', static function (): Responsable {
            return new TestResponse();
        });

        $response = $this->get('location', ['Accept' => null]);

        $response->assertOk();
        $this->assertSame('expected random response', $response->content());
    }

    public function testItRespondsToExtensionInTheRoute(): void
    {
        Route::get('location.csv', static function (): Responsable {
            return TestResponse::make()->withTypeCheckers([UrlContentType::class]);
        });

        $response = $this->get('location.csv');

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function testRespondsToAcceptHeader(): void
    {
        Route::get('location', static function (): Responsable {
            return new TestResponse();
        });

        $response = $this->get('location', [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $this->assertSame('expected json response', $response->content());
    }

    public function testRespondsToFirstMatchingAcceptsHeader(): void
    {
        Route::get('location', static function (): Responsable {
            return new TestResponse();
        });

        $response = $this->get('location', [
            'Accept' => 'text/csv, text/css',
        ]);

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function testRespondsToAMoreObscureAcceptHeader(): void
    {
        Route::get('location', static function (): Responsable {
            return new TestResponse();
        });

        $response = $this->get('location', [
            'Accept' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        $response->assertOk();
        $this->assertSame('expected xlsx response', $response->content());
    }

    public function testLastDotSegementIsUsedAsTheExtensionType(): void
    {
        Route::get('websites/{domain}{format}', static function (): Responsable {
            return new TestResponse();
        })->where('format', '.json');

        $response = $this->get('websites/timacdonald.me.json');

        $response->assertOk();
        $this->assertSame('expected json response', $response->content());
    }

    public function testFileExtensionTakesPrecendenceOverAcceptHeader(): void
    {
        Route::get('location{format}', static function (): Responsable {
            return new TestResponse();
        });

        $response = $this->get('location.csv', [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function testRootDomainReturnsHtmlByDefault(): void
    {
        $this->config()->set('app.url', 'http://timacdonald.me');
        Route::get('', static function (): Responsable {
            return new TestResponse();
        });

        $response = $this->get('');

        $response->assertOk();
        $this->assertSame('expected html response', $response->content());
    }

    public function testRootDomainResponseToOtherFormats(): void
    {
        $this->config()->set('app.url', 'http://timacdonald.me');
        Route::get('.csv', static function (): Responsable {
            return new TestResponse();
        });

        $response = $this->get('.csv');

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function testQueryStringHasNoImpact(): void
    {
        Route::get('location', static function (): Responsable {
            return new TestResponse();
        });

        $response = $this->get('location?format=.csv');

        $response->assertOk();
        $this->assertSame('expected html response', $response->content());
    }

    public function testContainerPassesRequestIntoFormatMethodsWithoutTypehiting(): void
    {
        Route::get('location.csv', static function (): Responsable {
            return new class() extends BaseSuperResponse {
                /**
                 * @param \Illuminate\Http\Request $request
                 */
                public function toCsvResponse($request): string
                {
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

    public function testContainerResolvesDependenciesInFormatMethods(): void
    {
        $this->app->bind(stdClass::class, static function () {
            $instance = new stdClass();
            $instance->property = 'expected value';

            return $instance;
        });
        Route::get('location.csv', static function (): Responsable {
            return new class() extends BaseSuperResponse {
                public function toCsvResponse(stdClass $stdClass): string
                {
                    assert(is_string($stdClass->property));

                    return $stdClass->property;
                }
            };
        });

        $response = $this->get('location.csv');

        $response->assertOk();
        $this->assertSame('expected value', $response->content());
    }

    public function testCanSetDefaultResponseFormatForApis(): void
    {
        Route::get('location', static function (): Responsable {
            return TestResponse::make()->withApiFallback('csv');
        });

        $response = $this->get('location', ['Accept' => null]);

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    public function testExceptionIsThrowIfNoResponseMethodExists(): void
    {
        $this->expectExceptionMessage('Method Tests\\TestResponse::toMp3Response() does not exist');

        Route::get('location{format}', static function (): Responsable {
            return new TestResponse();
        });

        $this->get('location.mp3');
    }

    public function testUrlHtmlFormatIsUsedWhenTheDefaultHasAnotherValueForApis(): void
    {
        Route::get('location{format}', static function (): Responsable {
            return (new TestResponse())->withApiFallback('csv');
        });

        $response = $this->get('location.html', ['Accept' => null]);

        $response->assertOk();
        $this->assertSame('expected html response', $response->content());
    }

    public function testCanOverrideFormats(): void
    {
        $this->app->bind(MimeMap::class, static function (): MimeMap {
            return new MimeMap(['text/csv' => 'json']);
        });
        Route::get('location', static function (): Responsable {
            return new TestResponse();
        });

        $response = $this->get('location', ['Accept' => 'text/csv']);

        $response->assertOk();
        $this->assertSame('expected json response', $response->content());
    }

    public function testUntypedRequestVariableIsPassedThrough(): void
    {
        Route::get('location.csv', static function (): Responsable {
            return new class() extends BaseSuperResponse {
                use SuperResponse;

                public function toCsvResponse(Request $request): string
                {
                    $query = $request->input('query');

                    assert(is_string($query));

                    return $query;
                }
            };
        });

        $response = $this->get('location.csv?query=expected query');

        $this->assertSame('expected query', $response->content());
    }

    public function testOverridingFallbackExtensionGloballyForApis(): void
    {
        $this->app->bind(ApiFallback::class, static function (): Closure {
            return static function (object $response) {
                return [$response, 'toJsonResponse'];
            };
        });
        Route::get('location', static function (): Responsable {
            return new TestResponse();
        });

        $response = $this->get('location', ['Accept' => null]);

        $response->assertOk();
        $this->assertSame('expected json response', $response->content());
    }

    public function testItCanReturnNestedResponsables(): void
    {
        Route::get('location', static function (): Responsable {
            return new class() implements Responsable {
                use SuperResponse;

                public function toHtmlResponse(): Responsable
                {
                    return new class() implements Responsable {
                        /**
                         * @param \Illuminate\Http\Request $request
                         */
                        public function toResponse($request): \Symfony\Component\HttpFoundation\Response
                        {
                            return new Response('expected from nexted');
                        }
                    };
                }
            };
        });

        $response = $this->get('location');

        $response->assertOk();
        $this->assertSame('expected from nexted', $response->content());
    }

    public function testUnknownMimeTypesFallback(): void
    {
        $this->app->bind(ApiFallback::class, static function (): Closure {
            return static function (object $response): callable {
                return [$response, 'toCsvResponse'];
            };
        });
        Route::get('location', static function () {
            return new TestResponse();
        });

        $response = $this->get('location', ['Accept' => 'unknown/mime']);

        $response->assertOk();
        $this->assertSame('expected csv response', $response->content());
    }

    private function config(): Repository
    {
        $config = $this->app->make('config');

        assert($config instanceof Repository);

        return $config;
    }
}
