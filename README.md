# Pro Forma

This library allows other libraries to configure templates for auto-generated code, allowing for quick bootstrapping of 
projects, using a composer plugin.

Once the Pro Forma plugin is enabled, the templating process is seamless from an end-user perspective, so installing a
library that is configured with Pro Forma templates will automatically generate those files if they don't already exist.

## Project installation

To use Pro Forma in a project, enable the plugin in the `composer.json` file and allow PuzzleDI to use the library
containing the code templates:

```json
{
  "config": {
    "allow-plugins": {
      "lexide/pro-forma": true,
      "lexide/puzzle-di": true
    }
  },
  "extra": {
    "lexide/puzzle-di": {
      "whitelist": {
        "lexide/pro-forma": [
          "target/library"
        ]
      }
    }
  }
}
```

Running `install` or `update` Composer commands will invoke Pro Forma code generation. 
Composer may also ask if the Pro Forma plugin should be enabled, if it is not already. 

It is recommended that the auto-generated files be added to version control, however this is not required.

> IMPORTANT! Only whitelisted libraries will have their templates generated. This includes any other libraries that 
> whitelisted libraries have added to the whitelist trust chain. This is intended to prevent unintentional code 
> generation when adding a new library to a project. If Pro Forma does not generate the expected files, it is 
> advised to check that the library is correctly whitelisted.

## Template Providers

Pro Forma works by having libraries register template providers with it, using `lexide/puzzle-di`, then processing the template config provided
by those classes to convert template files into generated code.

Pro Forma uses `lexide/puzzle-di` to collect a list of `TemplateProviderInterface` class names.

### Composer configuration



Each library can register a single template provider, that implements the 
`Lexide\ProForm\Template\TemplateProviderInterface` interface, by adding the following configuration to the library's
`composer.json` file:

```json
{
  "extra": {
    "lexide/puzzle-di": {
      "files": {
        "lexide/pro-forma": {
          "class": "Fully\\Qualified\\Template\\Provider\\Class\\Name"
        }
      }
    }
  }
}
```

If this library uses dependencies that should also use Pro Forma to auto-generate code, those dependent libraries can be 
added to the whitelist chain for PuzzleDI in this library's `composer.json` file, much the same as when 
configuring a project:

```json
{
  "extra": {
    "lexide/puzzle-di": {
      "whitelist": {
        "lexide/pro-forma": [
          "dependent/library"
        ]
      }
    }
  }
}
```

### Templates

The purpose of a template provider is to return an array of instances of the `Lexide\ProForm\Template\Template` class. 
These instances need to contain all the information required to template the generated code.

In order to customise which templated files are returned, the providers are passed two config objects; a 
`Lexide\ProForm\Template\ProviderConfig\ProjectConfig` instance, which contains the project namespace and a list of 
installed packages, and a `Lexide\ProForm\Template\ProviderConfig\LibraryConfig` instance, which contains configuration 
for the current provider, taken from the project's `composer.json`. This allows the providers to make decisions such as 
adding a particular template if a package is installed or setting the value of a replacement based on project config 
options, for example.

The values contained in the `LibraryConfig` instance for a provider is defined only for its library and is configured as 
follows:

```json
{
  "extra": {
    "lexide/pro-forma": {
      "config": {
        "target/library": {
          "foo": "bar"
        }
      }
    }
  }
}
```

In this example, the `LibraryConfig` instance would contain the value `bar` under the key `foo`.

```php
$value = $libraryConfig->getValue("foo");    // value is set to 'bar'
```

### Template class

The template class, `Lexide\ProForma\Template\Template`, has four properties:

* name - the name of the template.
* templatePath - the path, relative to the _library root_, of the template file to process.
* outputPath - the path, relative to the _project root_, of the file to be generated.
* replacements - an array of key/value pairs to substitute into the template.

These properties can be set via setters on a manually created template instance, or via the factory method: 
`Lexide\ProForma\Template\TemplateFactory::create()`.

### Replacements

Template files can contain placeholder values that will be substituted when the output file is generated. The 
substitutions array is determined by the template providers and is custom per template. Pro Forma doesn't know beforehand
which placeholder values exist for a template, it will blindly attempt to replace them based on the key that the provider
supplies, therefore if placeholders are used, there must always be a replacement added to the template instance for each 
one.

Placeholder keys are wrapped in double curly braces, `{{ key }}`; spaces inside the braces are optional. When defining 
the replacements, only use the key name, no braces are required:

```php
$template->setReplacements(["key" => "value"]);
```

## Regenerating files

On occasion, it may be necessary to regenerate files if a library has had an update that changes a template's behaviour.

In order to do this, temporarily add the following value to the `lexide/pro-forma` section in `extra`:

```json
{
  "extra": {
    "lexide/pro-forma": {
      "overwrite": true
    }
  }
}
```

Please note that Pro Forma will continue to overwrite files while this value is set to true; it is not intended to be 
used permanently, to avoid unexpected changes in behaviour after a composer update or install.

Also, this setting tells Pro Forma to overwrite _all_ files, so any customisations that aren't in version control will 
be removed.

Finally, Pro Forma will _never_ delete files; if a library is uninstalled, it's auto-generated code will need deleting 
manually. 
