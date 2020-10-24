<?php

declare(strict_types=1);

namespace Tests;

use function assert;
use Closure;
use function dd;
use Exception;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use function is_string;
use function method_exists;
use Orchestra\Testbench\TestCase;
use function request;
use function response;
use stdClass;
use TiMacDonald\Multiformat\Checkers\UrlContentType;
use TiMacDonald\Multiformat\Checkers\UrlVersion;
use TiMacDonald\Multiformat\Contracts\FallbackResponse;
use TiMacDonald\Multiformat\MimeMap;
use TiMacDonald\Multiformat\ResponseType;
use TiMacDonald\Multiformat\SuperResponse;
use TiMacDonald\Multiformat\SuperResponseServiceProvider;

/**
 * @small
 */
class SuperResponseTest extends TestCase
{
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

    public function testItInstantiatesAnInstanceWithMakeAndDataIsAvailableViaMagicGet(): void
    {
        $instance = TestResponse::make(['property' => 'expected']);

        $this->assertSame('expected', $instance->property);
    }

    public function testItAddsDataUsingWithAndIsAvailableViaMagicGet(): void
    {
        $instance = (new TestResponse())->with(['property' => 'expected value']);

        $this->assertSame('expected value', $instance->property);
    }

    public function testItInjectsArgumentsThroughContructorUsingNew(): void
    {
        $instance = TestResponse::new('expected');

        $this->assertSame('expected', $instance->constructorArg);
    }

    public function testItMergesDataUsingWith(): void
    {
        $instance = new TestResponse();
        $instance->with(['property_1' => 'expected value 1'])
            ->with(['property_2' => 'expected value 2']);

        $this->assertSame('expected value 1', $instance->property_1);
        $this->assertSame('expected value 2', $instance->property_2);
    }

    public function testItThrowsExceptionAccessingNonExistentAttributes(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Undefined property: Tests\\TestResponse::not_set');

        /**
         * @psalm-suppress UndefinedMagicPropertyFetch
         * @phpstan-ignore-next-line
         */
        (new TestResponse())->not_set;
    }

    public function testItOverridesWhenPassingDuplicateKeyToWith(): void
    {
        $instance = new TestResponse();
        $instance->with(['property' => 'first']);
        $instance->with(['property' => 'second']);

        $this->assertSame('second', $instance->property);
    }

    public function testGlobalFallbackReturns406WhenNotImplementingImplicitContract(): void
    {
        Route::get('location', static function (): Responsable {
            return new class() implements Responsable {
                use SuperResponse;
            };
        });

        $response = $this->get('location', ['Accept' => null]);

        $response->assertStatus(406);
        $this->assertSame('', $response->content());
    }

    public function testGlobalFallbackReturnsImplicitContract(): void
    {
        Route::get('location', static function (): object {
            return new class() implements Responsable {
                use SuperResponse;

                public function unsupportedResponse(Request $request): array
                {
                    /** @var array */
                    return $request->query('response');
                }
            };
        });

        $response = $this->get('location?response[expected]=response', ['Accept' => null]);

        $response->assertStatus(200);
        $response->assertHeader('Content-type', 'application/json');
        $this->assertSame(['expected' => 'response'], $response->json());
    }

    public function testCanRebindGlobalFallbackWithCurrying(): void
    {
        $this->app->bind(FallbackResponse::class, static function (): Closure {
            return static function (Request $request, object $response): Closure {
                return static function () use ($request, $response): array {
                    /** @var array */
                    $value = $request->query('response');

                    assert(method_exists($response, 'someRandomMethod'));

                    /** @var array */
                    return $response->someRandomMethod($value);
                };
            };
        });
        Route::get('location', static function (): Responsable {
            return new class() implements Responsable {
                use SuperResponse;

                public function someRandomMethod(array $response): array
                {
                    return $response;
                }
            };
        });

        $response = $this->get('location?response[expected]=response', ['Accept' => null]);

        $response->assertStatus(200);
        $response->assertHeader('Content-type', 'application/json');
        $this->assertSame(['expected' => 'response'], $response->json());
    }

    public function testGlobalFallbackCanJustReturnAResponseDirectly(): void
    {
        $this->app->bind(FallbackResponse::class, static function (): object {
            return new class() implements Responsable {
                public function toResponse($request)
                {
                    return ['expected' => 'response'];
                }
            };
        });
        Route::get('location', static function (): object {
            return new class() implements Responsable {
                use SuperResponse;
            };
        });

        $response = $this->get('location', ['Accept' => null]);

        $response->assertStatus(200);
        $response->assertHeader('Content-type', 'application/json');
        $this->assertSame(['expected' => 'response'], $response->json());
    }

    public function testCanPassALocalFallback(): void
    {
        Route::get('location', static function (): TestResponse {
            return TestResponse::make()
                ->withFallbackResponse(static function (Request $request): array {
                    /** @var array */
                    return $request->query('response');
                });
        });

        $response = $this->get('location?response[expected]=response', ['Accept' => null]);

        $response->assertStatus(200);
        $response->assertHeader('Content-type', 'application/json');
        $this->assertSame(['expected' => 'response'], $response->json());
    }

    public function testPassedClosureCanCallMethodsOnResponseAndGetsRequest(): void
    {
        Route::get('location', static function (): object {
            return (new class() implements Responsable {
                use SuperResponse;

                public function someRandomMethod(string $response): string
                {
                    return $response;
                }
            })->withFallbackResponse(static function (Request $request, object $response): string {
                /** @var string */
                $value = $request->query('response');

                assert(method_exists($response, 'someRandomMethod'));

                /** @var string */
                return $response->someRandomMethod($value);
            });
        });

        $response = $this->get('location?response=expected response', ['Accept' => null]);

        $response->assertStatus(200);
        $this->assertSame('expected response', $response->content());
    }

    public function testGlobalFallbackKicksInWhenCheckersFindUnsupportedType(): void
    {
        Route::get('location.csv', static function (): object {
            return (new class() implements Responsable {
                use SuperResponse;
            })->withTypeCheckers([
                UrlContentType::class,
            ]);
        });

        $response = $this->get('location.csv', ['Accept' => null]);

        $response->assertStatus(406);
        $this->assertSame('', $response->content());
    }

    public function testLocalFallbackKicksInWhenCheckersFindUnsupportedType(): void
    {
        Route::get('location.csv', static function (): object {
            return (new class() implements Responsable {
                use SuperResponse;
            })->withTypeCheckers([
                UrlContentType::class,
            ])->withFallbackResponse(static function (): string {
                return 'expected response';
            });
        });

        $response = $this->get('location.csv', ['Accept' => null]);

        $response->assertStatus(200);
        $this->assertSame('expected response', $response->content());
    }

    public function testCanPassMutlpleCheckers(): void
    {
        Route::get('{version}/location.csv', static function (): object {
            return (new class() implements Responsable {
                use SuperResponse;

                public function toCsvVersion5Response(): string
                {
                    return 'expected response';
                }
            })->withTypeCheckers([
                UrlContentType::class,
                UrlVersion::class,
            ]);
        });

        $response = $this->get('v5/location.csv', ['Accept' => null]);

        $response->assertStatus(200);
        $this->assertSame('expected response', $response->content());
    }

    public function testCanRespondToSecondaryTypes(): void
    {
        // what about different versions of different types?
        $this->markTestIncomplete();
        Route::get('location', static function (): object {
            return (new class() implements Responsable {
                use SuperResponse;

                public function toXmlVersion_10_5_Response(): string
                {
                    return 'expected response';
                }
            })->withTypeCheckers([
                static function (): ResponseType {
                    return new ResponseType(['Json', 'Xml']);
                },
                static function (): ResponseType {
                    return new ResponseType(['Version_10_5_2_', 'Version_10_5_']);
                },
            ]);
        });

        $response = $this->get('location', ['Accept' => null]);

        $response->assertStatus(200);
        $this->assertSame('expected response', $response->content());
    }

    public function testCanSupportMulitpleAcceptTypes(): void
    {
        $this->markTestIncomplete();

        Route::get('location', static function (): void {
            dd(request()->getAcceptableContentTypes());
        });

        $this->get('location', ['Accept' => 'application/json, application/xsss']);
    }

    public function testReturningArrayFromFallback(): void
    {
        Route::get('location.csv', static function (): object {
            return (new class() implements Responsable {
                use SuperResponse;
            })->withTypeCheckers([
                UrlContentType::class,
            ])->withFallbackResponse(static function (): callable {
                return [new class() implements Responsable {
                    use SuperResponse;

                    public function someRandomMethod(): string
                    {
                        return 'expected response';
                    }
                }, 'someRandomMethod'];
            });
        });

        $response = $this->get('location.csv', ['Accept' => null]);

        $response->assertStatus(200);
        $this->assertSame('expected response', $response->content());
    }

    // ----------------------------------------
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
            return new class() implements Responsable {
                use SuperResponse;

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
        $this->app->bind(stdClass::class, static function (): stdClass {
            $instance = new stdClass();
            $instance->property = 'expected value';

            return $instance;
        });
        Route::get('location.csv', static function (): Responsable {
            return new class() implements Responsable {
                use SuperResponse;

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
            return TestResponse::make()->withFallbackResponse('csv');
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
            return (new TestResponse())->withFallbackResponse('csv');
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
            return new class() implements Responsable {
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
        $this->app->bind(FallbackResponse::class, static function (): Closure {
            return static function (object $response): callable {
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
        $this->app->bind(FallbackResponse::class, static function (): Closure {
            return static function (object $response): callable {
                return [$response, 'toCsvResponse'];
            };
        });
        Route::get('location', static function (): TestResponse {
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
