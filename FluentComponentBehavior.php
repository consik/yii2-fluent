<?php
/**
 * @link https://github.com/consik/yii2-fluent
 *
 * @author Sergey Poltaranin <consigliere.kz@gmail.com>
 * @copyright Copyright (c) 2016
 */

namespace consik\yii2fluent;

use yii\base\Behavior;
use yii\base\Component;
use yii\base\Event;
use yii\base\InvalidCallException;
use yii\base\UnknownPropertyException;
use yii\caching\Cache;

/**
 * Class FluentComponentBehavior
 * Behavior implements fluent interface methods for any yii2 Component
 *
 * Fluent methods:
 * add*Property*(mixed $item) - adds item to array property
 * set*Property*(mixed $val) - sets property value
 * unset*Property*() - unsets property
 *
 * universal fluent methods, can be accessed through component object:
 * unsetProperty(string $property)
 * setProperty(string $property)
 * addItemTo(string $arrProperty, mixed $val, $initOnEmpty = true)
 *
 * @property array $attributes - enumeration of attributes which will be available through fluent methods. You can define alias of attribute for fluent interface, using associative array
 * @property bool $initArraysIfEmpty - init empty properties as array using fluent method add*AttributeName* if they are not defined as array.
 *
 * Your component code:
 * ```
 * public function behaviors()
 * {
 *  return [
 *      FluentComponentBehavior::className(), //simple definition. Behavior will implement all fluent methods for all attributes
 *      [ //extended definition of behavior
 *          'class' => FluentComponentBehavior::className(),
 *          'attributes' => ['new' => 'isNewRecord', 'id'] //behavior will implement fluent methods only for 'isNewRecord' and 'id'.
 *          //fluent methods for attribute isNewRecords will be implemented using alias 'new'. for example: $component->setNew($value) equals $component->isNewRecord = $value;
 *          'initArraysIfEmpty' => true
 *      ]
 * ];
 * }
 * ```
 *
 * @package consik\yii2fluent
 */
class FluentComponentBehavior extends Behavior
{
    /**
     * @var Component
     */
    public $owner;

    /**
     * Array of attributes that can be changed using fluent interface.
     * It can be associative, where `key` is method suffix and `value` is attributeName
     * Or simple enumeration of attributes name
     *
     * Example 1:
     * [ 'new' => 'isNewRecord' ]
     * calling $component->setNew(false) changes $component->isNewRecord attribute and returns $component object
     *
     * Example 2. You can combine associative and simple syntax:
     * [ 'new' => 'isNewRecord', 'id', ...]
     * available methods: $component->setNew($value)->setId($id)
     *
     * @var array
     */
    public $attributes = [];

    /**
     * @var bool initialize property as array or not if property is empty and !is_array
     */
    public $initArraysIfEmpty = true;

    /**
     * Return associative array of fluent interface methods, key = fluent method name, value - behavior method name
     * @return array
     */
    protected function getMethodsMap()
    {
        return [
            'add' => 'addItemTo',
            'set' => 'setProperty',
            'unset' => 'unsetProperty'
        ];
    }

    /**
     * @param string $name
     * @param array $params
     * @return Component|mixed
     * @throws UnknownPropertyException
     * @throws InvalidCallException
     */
    public function __call($name, $params)
    {
        $action = $this->getActionFromMethod($name);
        $property = $this->getPropertyFromMethod($name);
        if ($action && $property && $this->owner->canSetProperty($property)) {
            $method = $this->getMethodsMap()[$action];
            switch ($action) {
                case 'unset':
                    return $this->unsetProperty($property);
                case 'add':
                    return $this->addItemTo($property, $params[0], $this->initArraysIfEmpty);
                default:
                    return $this->{$method}($property, ...$params);
            }
        }
        return parent::__call($name, $params);
    }

    /**
     * Sets value to component property and returns component object
     *
     * @param $name
     * @param $value
     * @return Component
     * @throws UnknownPropertyException
     * @throws InvalidCallException
     */
    public function setProperty($name, $value)
    {
        if ($this->owner->canSetProperty($name)) {
            $this->owner->{$name} = $value;
            return $this->owner;
        }
        if ($this->owner->canGetProperty($name)) {
            throw new InvalidCallException('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new UnknownPropertyException('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Sets component property to null and returns component object. Equal for setProperty($name, null)
     *
     * @param $name
     * @return Component
     * @throws UnknownPropertyException
     * @throws InvalidCallException
     */
    public function unsetProperty($name)
    {
        unset($this->owner->{$name});
        return $this->owner;
    }

    /**
     * Adds $item to the component property $arrName and return component object
     *
     * @param $arrName
     * @param $item
     * @param bool $initOnEmpty initialize property as array or not if property is empty and !is_array
     * @return Component
     * @throws UnknownPropertyException
     * @throws InvalidCallException
     */
    public function addItemTo($arrName, $item, $initOnEmpty = true)
    {
        if ($this->owner->canSetProperty($arrName)) {
            if (!is_array($this->owner->{$arrName})) {
                if (empty($this->owner->{$arrName}) && $initOnEmpty) {
                    $this->owner->{$arrName} = [];
                } else {
                    throw new InvalidCallException('Cannot add item to the non-array property: ' . get_class($this) . '::' . $arrName);
                }
            };
            $array = $this->owner->{$arrName};
            $array[] = $item;
            $this->owner->{$arrName} = $array;
            return $this->owner;
        }
        if ($this->owner->canGetProperty($arrName)) {
            throw new InvalidCallException('Setting read-only property: ' . get_class($this) . '::' . $arrName);
        } else {
            throw new UnknownPropertyException('Setting unknown property: ' . get_class($this) . '::' . $arrName);
        }
    }

    /**
     * @param $name - name of the called method
     * @return string - name of the action if behavior has this fluent method
     */
    protected function getActionFromMethod($name)
    {
        foreach ($this->methodsMap as $action => $method) {
            if (strncmp($name, $action, strlen($action)) === 0) {
                return $action;
            }
        };
        return '';
    }

    /**
     * @param $name - name of the called method
     * @return string - name of the property if behavior has fluent method for the property
     */
    protected function getPropertyFromMethod($name)
    {
        $property = '';
        $property = '';
        if ($action = $this->getActionFromMethod($name)) {
            $property = lcfirst(substr($name, strlen($action)));
            if (!empty($this->attributes)) {
                if (array_key_exists($property, $this->attributes)) {
                    $property = $this->attributes[$property];
                } elseif (in_array($property, $this->attributes)) {
                    $key = array_search($property, $this->attributes);
                    if (is_string($key)) {
                        $property = '';
                    }
                } else {
                    $property = '';
                }
            }
        }
        return $property;
    }

    /**
     * @inheritdoc
     */
    public function hasMethod($name)
    {
        return $this->getPropertyFromMethod($name) ? true : parent::hasMethod($name);
    }
}