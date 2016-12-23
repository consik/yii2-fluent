# Yii2 Fluent Interface Behavior

Behavior that implements fluent interface methods for component attributes.

[![Latest Stable Version](https://poser.pugx.org/consik/yii2-fluent/v/stable)](https://packagist.org/packages/consik/yii2-fluent)
[![Total Downloads](https://poser.pugx.org/consik/yii2-fluent/downloads)](https://packagist.org/packages/consik/yii2-fluent)
[![License](https://poser.pugx.org/consik/yii2-fluent/license)](https://packagist.org/packages/consik/yii2-fluent)

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require consik/yii2-fluent
```

or add

```json
"consik/yii2-fluent": "^1.0"
```

## FluentComponentBehavior class description

### Properties

* ``` array $attributes = []```

Associative or simple array of attributes that can be changed using fluent interface.
For associative definition `key` is alias for attributes methods;
Value is always component attribute name;

* ``` string $initArraysIfEmpty = true```

Defines need bahavior to initialize property as array if it's empty and !is_array() when calling array-access fluent methods(like ```add*Property*($item)```)

### Public methods

Universal fluent methods for owner component

* ``` $this setProperty(string $property, mixed $value)```

Sets value to component property

* ``` $this unsetProperty(string $property)```

Unsets component property

* ``` $this addItemTo(string $arrName, mixed $value, $initOnEmpty = true)```

Adds ```$item``` to array property with name ```$arrName```;

Throws exception if ```$component->{$arrName}``` is ```!empty() && !is_array()```;

initializes ```$component->{$arrName}``` as empty array if ```($initOnEmpty && empty($component->{$arrName}) && !is_array($component->{$arrName}))``` ;

## Examples

### Short definition of behavior. Behavior will implement all available fluent methods for ALL component attributes
```php
<?php
use consik\yii2fluent\FluentComponentBehavior;

class Test extends \yii\base\Component
{
    public $isNew;
    public $comments;

    public function behaviors()
     {
         return [
            FluentComponentBehavior::className()
         ];
     }
}
```
Available fluent methods for this definition:
* (new Test())
* ->setProperty($name, $value)
* ->unsetProperty($name)
* ->addItemTo($arrName, $arrayItem)
* ->setIsNew($value)
* ->unsetIsNew()
* ->addIsNew($arrayItem)
* ->setComments($value)
* ->unsetComments()
* ->addComments($arrayItem)

### Extended definition of behavior, for enumerated properties, with alias for one of property.
```php
<?php
use consik\yii2fluent\FluentComponentBehavior;

class Test extends \yii\base\Component
{
    public $isNew;
    public $comments;
    public $fluentUnaccessable;

    public function behaviors()
     {
         return [
            [
                'class' => FluentComponentBehavior::className(),
                'attributes' => [
                    'new' => 'isNew',
                    'comments'
                ]
         ];
     }
}
```
Available fluent methods for this definition:
* (new Test())
* ->setProperty($name, $value)
* ->unsetProperty($name)
* ->addItemTo($arrName, $arrayItem)
* ->setNew($value)
* ->unsetNew()
* ->addNew($arrayItem)
* ->setComments($value)
* ->unsetComments()
* ->addComments($arrayItem)

## Be helpful!

Don't forget about other developers and write comments for your classes!

Basic comment for all components with attached FluentInterfaceBehavior
```
@method $this setProperty(string $name, $value)
@method $this unsetProperty(string $name)
@method $this addItemTo(string $arrName, mixed $item)
```
```php
<?php
/*
 * Class YourClass
 * @method $this setProperty(string $name, $value)
 * @method $this unsetProperty(string $name)
 * @method $this addItemTo(string $arrName, mixed $item)
 */
 class YourClass extends \yii\base\components { ... }
```

And, please, don't forget writing comments about defined fluent methods for your component properties!!!

Best regards,
Sergey Poltaranin.
