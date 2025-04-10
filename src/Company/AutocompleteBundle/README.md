# CompanyAutocompleteBundle

## Introduction

This is a [Symfony](https://symfony.com/) Bundle that provides an autocomplete Form Type. This was designed for
Symfony v4+.
Currently, this widget uses [Select2](https://select2.org/) as its base for autocomplete entity selections and jQuery UI autocomplete for autocomplete text selections.

### Features

* Supports Entity and Non-Entity lookups.
* Easily add the javascript code with jQuery **companyEntityAutocomplete()** or **.companyTextAutocomplete()** methods or just with using **.select2-entity-autocomplete-widget** and **.ui-text-autocomplete-widget** classes added by default by form type of this bundle.
* Supports entity creation from entity autocomplete type - The ability of adding new entities or items.
* Support single and multiple selections with transformation validation.
* Allow customizing display and selection list queries for choice options.
* Make possible to query more options next to **id** and **text** fields and allow to use it programatically
* Let us add extra parameters to AJAX queries which can be handled to give approptiate select result.

### Requirements

* This bundle requires the [Select2](https://select2.org/) or [jQuery UI autocomplete](https://jqueryui.com/autocomplete/)
  javascript and CSS code, but does not provide it directly. You must include it yourself,
  eg: `yarn add select2`, and add it to your webpack or other bundler configuration.

* `Select2` requires [jQuery](https://jquery.com/) and is not actually provided in this bundle.
  You must provide it yourself, eg: `yarn add jquery`

### Examples

A simple select box that allows a single entity `User` to be selected from an AJAX request.


```php
use Company\AutocompleteBundle\Form\Type\EntityAutocompleteType;

$builder->add('user', EntityAutocompleteType::class, [
    'descriptor'           => 'users',
    'minimum_input_length' => 1,
    'placeholder'          => 'Search for user ...',
]);
```

A simple select box that allows a multiple `User` entities to be selected from an AJAX request.

```php
use Company\AutocompleteBundle\Form\Type\EntityAutocompleteType;

$builder->add('users', EntityAutocompleteType::class, [
    'descriptor'           => 'users',
    'minimum_input_length' => 1,
    'multiple'             => true,
    'placeholder'          => 'Search for user ...',
]);
```

A simple text type field with autocomplete selection advision.

```php
use Company\AutocompleteBundle\Form\Type\TextAutocompleteType;

$builder->add('username', TextAutocompleteType::class, [
    'descriptor'           => 'users',
    'minimum_input_length' => 1,
    'items_per_page'       => 15,
    'placeholder'          => 'Search for user ...',
]);
```

We have to register a tagged autocomplete desctiptor service for background support of user selections from User::class entity datasource.

```yaml
    app.autocomplete_descriptor.users:
        class: Company\AutocompleteBundle\Autocomplete\BaseAutocompleteDescriptor
        tags:
            - { name: 'company.autocomplete.descriptor', id: 'users'}
        arguments:
            $class: App\Entity\User
            $property: username
```

## Configuration

There are a lot of options to customize the autocomplete types. Defaults are shown.
Note: A `descriptor` must be specified for types for background support of data source and validation check and entity hydration.

### EntityAutocompleteType

Option|   Default    | Description                                                                                                                                                                            
:------------------------:|:------------:|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
**descriptor**            |     None     | It adds reference to autocomplete descriptor for background support. It is required and it hasn't default value.                                                                       
**multiple**              |   `false`    | If true, multiple items can be selected.                                                                                                                                               
**placeholder**           |    `null`    | Placeholder of widget                                                                                                                                                                  
**is_creation_allowed**   |   `false`    | It is possible to turn on creation feature of widget. You have to implement descriptor's creation interface in this case.                                                              
**params**                |    `null`    | It set custom parameters statically as an associative array for AJAX requests. It can be modified on client if needed. You can use this parameter in autocomplete descriptor services. 
**minimum_input_length**  |     `0`      | It sets minimum typed character size for autocomplete listing. It is a client settings it has no background protection in descriptor (yet).                                            
**width**                 |    `100%`    | CSS formatted size of autocomplete widget.                                                                                                                                             
**items_per_page**        |     `20`     | Set the pagination size of autocomplete widget. It is basicly not limited but you can handle it in autocomplete descriptor.                                                            
**quiet_millis**          |    `200`     | It sets the timeout in milliseconds to wait from character hit to the ajax request.                                                                                                    
**cache**                 |   `false`    | It enables cache function of Select2 AJAX queries. If you set to true the current timestamp will be removed from AJAX requests.                                                        
**container_css_class**   |    `null`    | It adds extra CSS class to form type's widget container if needed.                                                                                                                     
**dropdown_css_class**    |    `null`    | It sets CSS class of autocomplete dropdown box.                                                                                                                                        
**allow_clear**           | `false/true` | It adds ability to remove currently selected value of widget. Its default value depends on "required" option, but you can customize for your needs.

### TextAutocompleteType

Option|   Default    | Description                                                                                                                                                                            
:------------------------:|:------------:|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
**descriptor**            |     None     | It adds reference to autocomplete descriptor for background support. It is required and it hasn't default value.                                                                       
**params**                |    `null`    | It set custom parameters statically as an associative array for AJAX requests. It can be modified on client if needed. You can use this parameter in autocomplete descriptor services. 
**minimum_input_length**  |     `0`      | It sets minimum typed character size for autocomplete listing. It is a client settings it has no background protection in descriptor (yet).                                            
**items_per_page**        |     `20`     | Set the pagination size of autocomplete widget. It is basicly not limited but you can handle it in autocomplete descriptor.                                                            
**quiet_millis**          |    `200`     | It sets the timeout in milliseconds to wait from character hit to the ajax request.                                                                                                    

## Descriptor implementation

You can use basicly BaseAutocompleteDescriptor to register autocomplete descriptors easily. It is good enough for most cases, but it can occure you to want more specific solutions. In this case you must to implement an own descriptor interface from zero or extending BaseAutocompleteDescriptor class.
```php

interface AutocompleteDescriptorInterface
{
    /**
      * @return string  It sets datasource entity.
      */
    public function getClass(): string;

    /**
      * @return string  It sets entity property to display in input 
      *                 or autocomplete selection list.
      */
    public function getProperty(): string;

    /**
      * @return string  It customizes property display, 
      *                 if it is necessary.
      */
    public function getDisplayMethod(): ?\Closure;

    /**
      * @return string  It occupies search fields of database query.
      */
    public function getSearchFields(): array;

    /**
      * It customizes database query.
      *
      * @param QueryBuilder $queryBuilder
      */
    public function buildQuery(QueryBuilder $queryBuilder): void;

    /**
      * It can handle new entity creation. 
      * @param object $entity   The given entity is persisted and filled with added autocomplete text.
      */
    public function updateCreatedEntity(object $entity): void;

    /**
      * It sets additional fields of autocomplete result set next to 'id' and 'text' fields. 
      */
    public function getAdditionalRecordValues(object $entity): array;
}
```