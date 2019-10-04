# What is this
This package provides additional directived for Laravel Blade template engingine. 
Among directives are fully-functional partials, translations and [dot.js](http://olado.github.io/doT/index.html) 
constructions.

## Installation 

Add require to `composer.json` and run `composer update`

    "require": {
        "swayok/laravel-blade-directives": "~1.0",
    }

## Configuration

### Service provider for Laravel 5.6+

Automatically added via package auto-discovery.

### Service provider for Laravel < 5.6

Add `\LaravelBladeDirectives\LaravelBladeDirectivesServiceProvider::class` to your `config/app.php` into `providers` array

## Shortcuts

    @breakpoint 
    // add breakpoint to view (allows you to debug compiled views)
    
    @string($id, $parameters = [], $locale = null)
    @trans($id, $parameters = [], $locale = null)
    // {{ trans($id, $parameters, $locale) }}
    
    @html($id, $parameters = [], $locale = null)
    @unescaped($id, $parameters = [], $locale = null)
    // {!! trans($id, $parameters, $locale) !!}
    
    @key($array, $key, $default, $escaped = true)
    // {{ array_get($array, $key, $default) }} - when $escaped === true
    // {!! array_get($array, $key, $default) !!} - when $escaped === false
    
    Examples:
    
    @string('frontend.footer', ['year' => 2019], 'en')
    @trans('frontend.footer', ['year' => 2019])
    @html('frontend.lang.' . app()->locale())
    @unescaped('frontend.app_name')
    
    @key($settings, 'language', 'en') 
    // {{ array_get($settings, 'language', 'en') }}
    @key($settings, 'language', 'en', false)
    // {!! array_get($settings, 'language', 'en') !!}

## Partials

The idea behind partials is possibility to use sub-template several times in a single blade template 
without using a `@view()` directive that require a view file to be created. Sometimes you do not 
need separate view file for simple template like table row. 
Partials also useful to make big template a bit cleaner. 

Partials declared and used like sections with exception that partials receive only data
you explicitely pass into `@yieldPartial` and some shared data provided by `$__env->getShared()` (`\Illuminate\View\Factory->getShared()`).
Under the hood partial is a closure that renders some blade template using passed data. 
It does not create files and all code is placed inside current blade template.

### Declare partial
    
    @partial('name')
        <div>{{ $var1 }}</div>
    @endPartial
    
### Yield partial

    @yieldPartial('name', ['var1' => 'this is partial'])
    @yieldpartial('name', ['var1' => 'this is another partial'])
    
### Result
    
    <div>this is partial</div>
    <div>this is another partial</div>
    
Note: all directives have both cameCase and lowercase versions

## [dot.js](http://olado.github.io/doT/index.html) directives
It is a pain using dot.js inserts inside blade templates. 
You need to add `@` before each dot.js insert, also you cannot insert php code inside such inserts.
Template becomes a real mess sometimes. 

The idea with this directives is to make blade templates that contain dot.js inserts 
more clean and visually better. In addition there must be a possibility to insert
some php code inside directive.

### Simple inserts

    @jsEcho(it.value)
    // {{= it.value }}
    @jsecho(it.value || 'default')
    // {{= it.value || 'default' }}
    
    @jsEchoEncoded(it.value)
    // {{! it.value }}
    @jsechoencoded(it.value || "default")
    // {{! it.value || "default" }}
    
    @jsEval(it._some_name = 'value')
    // {{ it._some_name = 'value' }}
    @jseval(it._some_name = "value")
    // {{ it._some_name = "value" }}
    
    @jsIf(it.value)
    // {{? it.value }}
    @jsif(it.value === 'example')
    // {{? it.value === 'example' }}
    
    @jsIfSimple(it.value)
    // {{? it.value }}
    @jsifsimple(it.value === 'example')
    // {{? it.value === 'example' }}
    
    @jsElseIf(it.value)
    // {{?? it.value }}
    @jselseif(it.value === 'example')
    // {{?? it.value === 'example' }}
    
    @jsElseIfSimple(it.value)
    // {{?? it.value }}
    @jselseifsimple(it.value === 'example')
    // {{?? it.value === 'example' }}
    
    @jsElse
    @jselse
    // {{??}}
    
    @jsEndIf
    @jsendif
    // {{?}}
    
    @jsForEach(it.array as key => value)
    // {{~ it.array :value:key }}
    @jsforeach(it.array as value)
    // {{~ it.array :value }}
    
    @jsEndForEach
    @jsendforeach
    // {{~}}
    
    @jsPartial(partial_name_without_spaces)
    @jspartial(partial_name_without_spaces)
    // {{##def.partial_name_without_spaces:
    
    @jsEndPartial
    @jsendpartial
    // #}}
    
    @jsEchoPartial(partial_name_without_spaces)
    @jsechopartial(partial_name_without_spaces)
    // {{#def.partial_name_without_spaces}}
    
    
### Complex inserts with PHP code
PHP code inside dot.js directive must begin with `` `. `` and end with `` .` `` 
(the quote is the one that placed on tilda `~` button on keyboard and is called `backtick`).

Using backticks allows using normal quotes and double quotes without problems caused by incorrect quotes escaping.
Also if you're using PHP Storm you can configure Code Style for backticks that differs from other quotes and 
shows dot.js directives in unique way (configs are in the next section). 
 
Examples:

    $phpVar = 'var_string'
    SOME_CONSTANT = 'constant_string'
    $phpNumber = 5

    @jsIf(it.value === "` . $phpVar . `")
    // {{? it.value === "var_string" }}
    // Resulting php code in compiled template is: <?php echo '@{{? it.value === "' . ($phpVar) . '" }}'; ?>

    @jsIf(it.value === '` . $phpVar . `')
    // {{? it.value === 'var_string' }}
    // Resulting php code in compiled template is: <?php echo '@{{? it.value === \'' . ($phpVar) . '\' }}'; ?>
    
    @jsif(it.value === "` . SOME_CONSTANT . `")
    // {{? it.value === "constant_string" }}

    @jsIf(it.value >= ` . ($phpNumber - 1) . ` && it.value <= ` . ($phpNumber + 1) . `)
    // {{? it.value >= 4 && it.value <= 6}}
    
#### Directives that support PHP code:

- `jsEcho`/`jsecho`
- `jsEchoEncoded`/`jsechoencoded`
- `jsEval`/`jseval`
- `jsIf`/`jsif`
- `jsElseIf`/`jselseif`

#### Directives that does not support PHP code:

- `jsIfSimple`/`jsifsimple`
- `jsElseIfSimple`/`jselseifsimple`
- `jsForEach`/`jsforeach`
- `jsPartial`/`jspartial`
- `jsEchoPartial`/`jsechopartial`
- Other directives that does not allow parameters (like `jsEndIf`, `jsEndForEach`)

## PHP Storm Configuration (Blade directives)
Open `Settings > Languages & Frameworks > PHP > Blade`. Untick `Use default settings`.
Open `Directives` tab.

Each directive have next inputs:
- Name 
- Has parameter
- Prefix
- Suffix

Note: directives with same configs are grouped in Names - you will need to create a
record for each directive name.

    Names: string, trans, html, unescaped
    Has parameter: true
    Prefix: <?php echo trans(
    Suffix: ); ?>
    
    Names: key
    Has parameter: true
    Prefix: <?php $fn = function(array $array, $key, $default = null, bool $escaped = true) {} ; $fn(
    Suffix: ); ?>
    
    Names: partial
    Has parameter: true
    Prefix: <?php $str =
    Suffix: ); ?>
    
    Names: yieldPartial, yieldpartial
    Has parameter: true
    Prefix: <?php $fn = function(string $partialName, array $partialData = []) {}; $fn(
    Suffix: ); ?>
    
    Names: breakpoint, endPartial, endpartial
    Has parameter: false
    
    Names: jsEcho, jsEchoEncoded, jsEval, jsForEach, jsIf, jsElseIf, jsIfSimple, jsElseIfSimple, jsPartial, jsEchoPartial 
    Has parameter: true
    Prefix: <?php echo `
    Suffix: `; ?>
    
    Names: jsecho, jsechoencoded, jseval, jsforeach, jsif, jselseif, jsifsimple, jselseifsimple, jspartial, jsechopartial
    Has parameter: true
    Prefix: <?php echo `
    Suffix: `; ?>
    
    Names: jsElse, jsEndIf, jsEndForEach, jselse, jsendif, jsendforeach, jsEndPartial, jsendpartial
    Has parameter: false

## PHP Storm Configuration (colors for directives)

Open `Settings > Editor > Color Scheme > Blade`.
Interesting parameters here are:
- `Directive`
- `Text block delimiter`

I added background to both so that directives are clearly visible in Blade template.

Open `Settings > Editor > Color Scheme > PHP`.

Interesting parameter here is `Shell command`. This is rarely used feature in PHP but it
will be useful for showing dot.js directives differently from usual Blade directives. 
Just add some background color. This will display parameters within dot.js directive with 
specific background color. I personally like this possibility because it makes 
normal and dot.js directives visually different.




