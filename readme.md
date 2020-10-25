## Table of contents

- [Passing data to a Super Response](#passing-data-to-a-super-response)
  - [Via static `make` constructor (magic)](#via-static-make-constructor-magic)
  - [Via static `new` constructor (slightly magic)](#via-static-new-constructor-slightly-magic)
  - [Via standard constructor (no magic)](#via-standard-constructor-no-magic)
- [Type checkers](#type-checkers)
  - [Type checkers out of the box](#type-checkers-out-of-the-box)
  - [Defining custom type checkers](#defining-custom-type-checkers)
- [Fallback response](#fallback-response)
  - [Global fallback out of the box](#global-fallback-out-of-the-box)
  - [Global fallback customisation](#global-fallback-customisation)
  - [Local fallback](#local-fallback)

## Passing data to a Super Response

There are a couple of ways to pass data to a Super Response. One that allows you to instantiate and pass data via a constructor with no magic and another that allows you to pass an array of key => value pairs. Let's dive in a take a look at both options.

### Via static `make` constructor (magic)

This method allows you to pass data as key => value pairs which is then made magically available via property access. This feels close to how Laravel allows you to pass data to a view.

```php
<?php

class PostController
{
    public function show(Post $post)
    {
        return PostResponse::make([
            'post' => $post,
            // ...
        ]);
    }
}
```

The data passed to this Super Response may now be accessed magically via standard property access.

```php
<?php

class PostResponse
{
    public function toHtmlResponse()
    {
        return view('posts.show', $this->post);
    }

    public function toJsonResponse()
    {
        return new PostResource($this->post);
    }
}
```

Although this uses PHP's magic `__get` method under the hood, it does have a strict requirement on accessing values you have actually passed through. If you try to access a property you have not passed through, an exception will be thrown.

```php
<?php

class PostResponse
{
    public function toJsonResponse()
    {
        // Throws an exception as we misspelt `post` as `postzzz` 
        return new PostResource($this->postzzz);
    }

    // ...
}
```

### Via static `new` constructor (slightly magic)

If you would like to declare the properties on your Super Response instead of accessing them via the magic `__get` method, you may do so and use the static `new` constructor to pass the parameters through to the constructor. This static constructor is useful if you would like to chain further calls to the Super Response after instantiation.

A super response with explicit constructor and properties...

```php
<?php

class PostResponse
{
    private Post $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function toJsonResponse()
    {
        return new PostResource($this->post);
    }

    // ...
}
```

You can instantiate this Super Response like so...

```php
<?php

class PostController
{
    public function show(Post $post)
    {
        return PostResponse::new($post);
    }
}
```

### Via standard constructor (no magic)

As a final option you can skip the magic all together and just create an instance using the constructor directly.

A super response with explicit constructor and properties...

```php
<?php

class PostResponse
{
    private Post $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function toJsonResponse()
    {
        return new PostResource($this->post);
    }

    // ...
}
```

You can instantiate this Super Response like so...

```php
<?php

class PostController
{
    public function show(Post $post)
    {
        return new PostResponse($post);
    }
}
```
### Passing additional data

With all of the above examples, you may also chain multiple `with` method calls to pass additional data which will be available via the magic property access described above...

```php
<?php

class PostController
{
    public function show(Post $post)
    {
        return PostResponse::make([
            'post' => $post,
        ])->with([
            'date' => now(),
        ]);
    }
}
```

## Type checkers

Type checkers perform the task of inspecting the incoming `Request` and determining what types are associated with it. Types may include, but are not limited too, the requested:

- Content type 
- Content version 
- Content language 
- Content character encodings 
- Content encoding / compression 

This package comes with some type checkers that detect some of these via commonly used conventions, however you can easily define your own type checkers that can detect your own conventions.

## Fallback response

The fallback is returned to the client when the Super Response instance cannot negotiate a suitable response to meet the clients request. Any response that can be returned by a Controller (e.g. a string, an array, a redirect, or a response instance) may be returned as a fallback.

You might like to use a fallback that returns a redirect, a default response (e.g. HTML), a `406 Not Acceptable` response, or a `300 Multiple Choices` response. 

### Global fallback out of the box

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

### Global fallback customisation

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

### Local fallback 

You may also define a local fallback when returning a Super Response. This is useful if for a particular controller or nested Super Response you would like the fallback to operate in a different manner than the rest of the places you use the Super Response. The local fallback will take precedence over the global fallback.

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
