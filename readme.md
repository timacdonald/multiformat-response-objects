# Multi-format Response Object for Laravel

[![Latest Stable Version](https://poser.pugx.org/timacdonald/multiformat-response-objects/v/stable)](https://packagist.org/packages/timacdonald/multiformat-response-objects) [![Total Downloads](https://poser.pugx.org/timacdonald/multiformat-response-objects/downloads)](https://packagist.org/packages/timacdonald/multiformat-response-objects) [![License](https://poser.pugx.org/timacdonald/multiformat-response-objects/license)](https://packagist.org/packages/timacdonald/multiformat-response-objects)

In some situations you may want to support multiple return formats (HTML, JSON, CSV, XLSX) for the one endpoint and controller. This package gives you a base class that helps you return different formats of the same data. It supports specifying the return format as a file extension or as an `Accept` header. It also allows you to have shared and format specific logic, all while sharing the same route and controller.

## Installation

You can install using [composer](https://getcomposer.org/) from [Packagist](https://packagist.org/packages/timacdonald/multiformat-response-objects)

```
$ composer require timacdonald/multiformat-response-objects
```

## Getting started

This package is designed to help if you have ever created a controller that looks like this...

```php
class UserController
{
    public function index(Request $request, CsvWriter $csvWriter)
    {
        // some shared logic...

        $query = User::query()
            ->whereActive()
            ->whereStatus($request->query('status'));

        // format check(s) and format specific logic...

        if ($this->wantsCsv($request)) {

            // return a CSV...

            $query->each(function ($user) use ($csvWriter) {
                $csvWriter->addRow($user->only(['name', 'email']));
            });

            return response()->download($csvWriter->file(), "Users.csv", [
                'Content-type' => 'text/csv',
            ]);
       }

       // return a webpage...

        $memberships = Membership::all();

        return view('users.index', [
            'memberships' => $memberships,
            'users' => $this->query->paginate(),
        ]);
    }
}
```

You might notice a few things about the above controller:

1. There is some initial shared logic between all the formats, i.e. preparing the query.
2. If the user is requesting the webpage, the `CsvWriter` is never used.
3. As we add more formats, we are going to no doubt be injecting more dependencies that are not needed in the other response types.
4. More response types also mean more checks in the `if` chain.
5. The web page also has format specific logic, i.e. it requires the `$memberships` collection, which is used (perhaps) to populate a dropdown on the webpage, but is not needed in the CSV download.

This package cleans up this style of controller. Let me show you how...

### Cleaning up the controller

The first step to refactoring the controller is to replace the format specific logic with the response object. You will no doubt do this step last, but I think it is easier to demonstrate it this way.

```php
class UserController
{
    public function index(Request $request, CsvWriter $csvWriter, )
    {
        $query = User::query()
            ->whereActive()
            ->whereStatus($request->query('status'));

        return UserIndexResponse::make(['query' => $query]);
    }
}
```

You can pass values into the response object by passing an array of data to the static `make` method. This is similar to how you may already be sending view data `view('users.index', ['some' => 'data'])`.

### The response object

In order to support a particular response format, you need to add a corresponding response method. If you want to provide your blog posts in mp3 audio format, you would add a `toMp3Response` method to you response object.

You can type hint these methods and the dependencies will be resolved from the container. In our example we are supporting HTML and CSV formats.

```php
use TiMacDonald\MultiFormat\Response;

class UserResponse extends Response
{
    public function toCsvResponse(CsvWriter $writer)
    {
        $this->query->each(function ($user) use ($writer) {
            $writer->addRow($user->only(['name', 'email']));
        });

        return response()->download($writer->file(), "Users.csv", [
            'Content-type' => 'text/csv',
        ]);
    }

    public function toHtmlResponse()
    {
        $memberships = Membership::all();

        return view('users.index', [
            'memberships' => $memberships,
            'users' => $this->query->paginate(),
        ]);
    }
}
```

You can see the `toCsvResponse` method has type hinted the `CsvWriter`. This dependency is only resolved when the request format is CSV. You can also magically access any of the data you passed into the `make` method as an attribute on the object e.g. `$this->query`.

That is all there is to it really. Below are some more detailed docs and features.

## Detecting response format

The response object will automatically detect the requested response format by checking for a file extension on the request's url and will fallback to the `Accept` header if no extension is found. Under the hood we are using Symfony's `MimeTypes` class to detect the extension. We then fallback to Laravel's `Request::format()` method. The first matching mime type and first matching extension will be used.

You do not *have* to support file extensions. This is entirely in your control. If you only want to support the `Accept` header than set up your routing to not supportextensions.

### Why file extensions?

It is pretty standard for an API to handle content negotiation with the `Accept` header. However it is often handy to be able to specify the response format with a file extension as well. This is probably most handy from a web interface where you can link the the same url but provide an extension to tell the server what format you want.

```html
<h2>Downloads</h2>
<ul>
    <li><a href="/users.csv">CSV</a></li>
    <li><a href="/users.pdf">PDF</a></li>
</ul>
```

This pattern is used in a lot of places. A good example of this is Reddit. Append `.json` to any url on reddit and you will get a JSON formatted response.

See for yourself:

- [https://www.reddit.com/r/laravel](https://www.reddit.com/r/laravel)
- [https://www.reddit.com/r/laravel.json](https://www.reddit.com/r/laravel.json)

## Response format methods

In order to support a format, you create a `to{Format}Response` method, where `{Format}` is the formats file extension. e.g.

- CSV: `toCsvResponse()`
- JSON: `toJsonResponse()`
- HTML: `toHtmlResponse()`
- XLSX: `toXlsxResponse()`

### Dependency Injection

As mentioned previously, the format method will be called by the container, allowing you to resolve **format specific dependencies** from the container. As seen in the basic usage example, the html format has no dependencies, however the csv format has a `CsvWriter` dependency.

## Default response format

It is possible to set a default response format, either from the calling controller, or from within the response object itself. This default format will be used if the url and the `Accept` header have no set value, or if no matches are found against existing `Accept` types.

### In the controller

```php
class UserController
{
    public function index()
    {
        //...

        return UserResponse::make(['query' => $query])
            ->withDefaultFormat('csv');
    }
}
```

### In the response object

```php
class UserResponse extends Response
{
    protected $defaultFormat = 'csv';

    // ...
}
```

## Overriding formats

If there is a situation where the mime type you want to support is not being converted to the correct extension, either because it doesn't exist in the underlying libraries, or because it is matching the first extension and you want to use another, it is possible for you to manually specify overrides.

Look at `audio/mpeg` for example. There are several extensions associated with this content type.

```php
'audio/mpeg' => ['mpga', 'mp2', 'mp2a', 'mp3', 'm2a', 'm3a'],
```

This package will resolve the first match, i.e. `mpga` as the format type. If you want to override this extension, you can do the following...

### In the controller

```php
class UserController
{
    public function index()
    {
        //...

        return UserResponse::make(['query' => $query])
            ->withFormatOverrides([
                'audio/mpeg' => 'mp3',
            ]);
    }
}
```

### In the response object

```php
class UserResponse extends Response
{
    protected $formatOverrides = [
        'audio/mpeg' => 'mp3',
    ];

    // ...
}
```

The above would result in `toMp3Response` being called if the Accept header is `audio/mpeg`.

## Routing

If you are wanting to embrace file extensions as a way of specifying response formats, you should explicilty specify the allowed formats in your routes file. This package does not provide any routing helpers (yet), but here is an example of how you can do it currently.

```php
Route::get('users{extension?}', [
    'as' => 'users.index',
    'uses' => 'UserController@index',
    // this is what we need to add...
    'where' => [
        'extension' => '^\.(pdf|csv|xlsx)$',
    ],
]);
```

This route will be able to respond to the following urls and formats in the response object...

- http://example.com/users [HTML]
- http://example.com/users.pdf [PDF]
- http://example.com/users.csv [CSV]
- http://example.com/users.xlsx [XLSX]

## I hate magic

That's cool. Not everyone loves it. You don't have to use the `make` method. Just add your own contructor and set your class attributes as you like!

```php
class UserResponse extends Response
{
    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    private $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }
}

//...

return new UserResponse($query);
```

## The Journey

You've read the readme, you've seen the code, now read the journey. If you wanna see how I came to this solution, you can read my blog post: https://timacdonald.me/versatile-response-objects-laravel/. Warning: it's a bit of a rant.

tl;dr; DHH and Adam Wathan are awesome.

## Thanksware

You are free to use this package, but I ask that you reach out to someone (not me) who has previously, or is currently, maintaining or contributing to an open source library you are using in your project and thank them for their work. Consider your entire tech stack: packages, frameworks, languages, databases, operating systems, frontend, backend, etc.

