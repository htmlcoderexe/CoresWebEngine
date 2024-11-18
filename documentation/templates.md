## Template documentation

The template engine uses several kinds of tokens that are then filled out by the template processor.

A template is used like this:

```php
// create a new instance
$tpl = new TemplateProcessor("folder/templatename");
// add some params
$tpl->tokens['something'] = "some value";
// can do different kinds of arrays, too
$tpl->tokens['data'] = ["some","test","strings"]; // this one is a list
$tpl->tokens['results'] = $database_results; // data as returned by a PDO
$tpl->tokens['onerow'] = ["foo"=>"bar","marco"=>"polo"]; // can accept an associative array
// process template and get its output as string
$output = $tpl->process(true);
```

It is also possible to use a shorthand to pre-fill params to the template:

```php
$tpl = new TemplateProcessor("folder/templatename,foo=bar");
```

Each token consists of an opening tag, a name, optional arguments separated by `|`, followed by a closing tag.

The current types are as follows:

### `{%variables%}`

These are parameters passed to the TemplateProcessor, by setting an element in the `$tokens` array of the template.
Those can be of multiple types, but the most common type is string. Passing an object will neatly convert it to an associative array with properties.
If the template parameter with that name is missing, an empty string is returned, and a warning is written to debug.

### `{%variables|with default value%}`

These only support strings, and the default value will be used if the corresponding template parameter is missing.

### `{:bound_variables:}`

These are used with non-string template parameters and are mapped to the keys in the current such parameter in use.

### `{$template_functions$}`

These execute a user-defined function in the corresponding `.tpl.php` file, if one exists. Such functions may return the same datatypes as supported by the template params, with the same effects.

### `{{templates|with=parameters}}`

It is possible to include another template by using this token, optionally specifying parameters as key-value pairs. The shorthand syntax also works here but is not necessary.

### `{#builtins#}`

These are built-in functions - some of them are part of the processor and others are external functions called by the processor.

These include:

* `{#if|varname|truepart|falsepart#}`

* `{#ifset|varname|truepart|falsepart#}`

* `{#foreach|optional data source|code to perform for each item#}`