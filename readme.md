## Global fallback response

The fallback is returned to the client when the Super Response instance cannot negotiate a suitable response to meet the clients request. Any response that can be returned by a Controller (e.g. a string, an array, a redirect, or a response instance) may be returned as a fallback.

### Out of the box

The fallback is applied to all Super Response's in your application. You can tailor how it works to fit your needs, however without any customisation the fallback will first look for an optional implicit contract on the class that has failed to provide a suitable response and use its return value as the response to the client.

You can implement the `unsupportedResponse` method on any class you wish to have a local customised fallback for.

```php
<?php

namespace App\Http\Responses;

use App\Models\Post;

class PostResponse
{
    private Post $post;

    // ...

    /**
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function unsupportedResponse($request)
    {
        // If we don't support the content type they are asking for, we will
        // redirect them to the main HTML version.
        return redirect()->route('posts.show', $this->post);
    }
}
```

If the Super Response does not implement this implicit contract the fallback will then return a [`406 Not Acceptable`](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/406) response.

### Customisation

The fallback can be completely customised by re-binding the `FallbackResponse` contract to the container in a Service Provider. Here are a few examples of fallback customisations to give you an idea of how it works.

If you would just like to provide a basic response instance, you can just return the desired response from the closure...

```php
<?php

use TiMacDonald\SuperResponse\Contracts\FallbackResponse;

public function boot()
{
    // Redirect to the homepage...

    $this->app->bind(FallbackResponse::class, fn () => redirect()->to('/'));
}
```

If you would like to be able to access the `$request` and `$response` the determine the fallback functionality, you may return a closure (also known as currying) that will receive the current `$request` and the failing Super Response as the `$response` argument.

```php
<?php

use TiMacDonald\SuperResponse\Contracts\FallbackResponse;

public function boot()
{
    // Change the implicit contract method name...

    $this->app->bind(FallbackResponse::class, function () {
        return function (Request $request, object $response) {
            if (method_exists($response, 'myCustomMethodName')) {
                return $response->myCustomMethodName($request);
            }

            return new Response(null, 406);
        };
    });
}
```

## Local fallback response

Aside from the global fallback and implicit contract, you may also define a local fallback when returning a Super Response. This is useful if for a particular controller or nested Super Response you would like the fallback to operate in a different manner than the rest of the places you use the Super Response. The local fallback will take precedence over the global fallback.

```php
<?php

class PostController
{
    public function show(Post $post)
    {
        return PostResponse::new($post)
            ->withFallbackResponse(fn () => redirect()->route('posts.show', $post));
    }
}
```

You can also receive the `$request` and Super Response instance as the `$response` argument if you would like to access them when determining the fallback.

```php
<?php

class PostController
{
    public function show(Post $post)
    {
        return PostResponse::new($post)
            ->withFallbackResponse(function (Request $request, PostResponse $response) {
                //
            });
    }
}
```
