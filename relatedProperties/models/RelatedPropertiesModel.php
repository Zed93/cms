<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.05.2015
 */
namespace skeeks\cms\relatedProperties\models;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\components\Cms;
use skeeks\cms\models\behaviors\HasDescriptionsBehavior;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\Implode;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\Core;
use skeeks\cms\relatedProperties\PropertyType;
use yii\base\DynamicModel;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\BaseHtml;

/**
 * Class RelatedPropertiesModel
 * @package skeeks\cms\relatedProperties\models
 */
class RelatedPropertiesModel extends DynamicModel
{
    /**
     * TODO: use protected
     * @var RelatedElementModel
     */
    public $relatedElementModel = null;

    /**
     * @var RelatedPropertyModel[]
     */
    private $_properties = [];
    private $_propertiesMap = [];

    public function init()
    {
        parent::init();

        if ($this->relatedElementModel->relatedProperties)
        {
            foreach ($this->relatedElementModel->relatedProperties as $property)
            {
                //TODO: default value
                $this->defineAttribute($property->code, $property->multiple == "Y" ? [] : null );
                $property->addRulesToDynamicModel($this);
                $this->_properties[$property->code] = $property;
            }
        }

        if ($relatedElementProperties = $this->relatedElementModel->relatedElementProperties)
        {
            foreach ($this->_properties as $code => $property)
            {
                if ($property->multiple == "Y")
                {
                    $values = [];
                    $valuesModels = [];

                    foreach ($relatedElementProperties as $propertyElementVal)
                    {
                        if ($propertyElementVal->property_id == $property->id)
                        {
                            $values[$propertyElementVal->id] = $propertyElementVal->value;
                            $valuesModels[$propertyElementVal->id] = $propertyElementVal;
                        }
                    }

                    $this->setAttribute($code, $values);
                    $this->_propertiesMap[$code] = $valuesModels;
                } else
                {
                    $value = null;
                    $valueModel = null;

                    foreach ($relatedElementProperties as $propertyElementVal)
                    {
                        if ($propertyElementVal->property_id == $property->id)
                        {
                            $value = $propertyElementVal->value;
                            $valueModel = $propertyElementVal;
                            break;
                        }
                    }

                    $this->setAttribute($code, $value);
                    $this->_propertiesMap[$code] = $valueModel;
                }
            }
        }

    }


    /**
     * @return $this
     */
    public function save()
    {
        foreach ($this->relatedElementModel->relatedProperties as $property)
        {
            $this->relatedElementModel->saveRelatedPropertyValue($property, $this->getAttribute($property->code));
        }

        return $this;
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $result = [];

        foreach ($this->relatedElementModel->relatedProperties as $property)
        {
            $result[$property->code] = $property->name;
        }

        return $result;
    }

    /**
     * @param string $name
     * @return RelatedPropertyModel
     */
    public function getRelatedProperty($name)
    {
        return ArrayHelper::getValue($this->_properties, $name);
    }

    /**
     * @param string $name
     * @return RelatedElementPropertyModel|RelatedElementPropertyModel[]
     */
    public function getRelatedElementProperties($name)
    {
        return ArrayHelper::getValue($this->_propertiesMap, $name);
    }





    /**
     * Returns a value indicating whether the model has an attribute with the specified name.
     * @param string $name the name of the attribute
     * @return boolean whether the model has an attribute with the specified name.
     */
    public function hasAttribute($name)
    {
        return in_array($name, $this->attributes());
    }

    /**
     * Returns the named attribute value.
     * If this record is the result of a query and the attribute is not loaded,
     * null will be returned.
     * @param string $name the attribute name
     * @return mixed the attribute value. Null if the attribute is not set or does not exist.
     * @see hasAttribute()
     */
    public function getAttribute($name)
    {
        if ($this->hasAttribute($name))
        {
            return $this->$name;
        }

        return null;
    }

    /**
     * Sets the named attribute value.
     * @param string $name the attribute name
     * @param mixed $value the attribute value.
     * @throws InvalidParamException if the named attribute does not exist.
     * @see hasAttribute()
     */
    public function setAttribute($name, $value)
    {
        if ($this->hasAttribute($name))
        {
            $this->$name = $value;
        } else
        {
            throw new InvalidParamException(get_class($this) . ' '.\Yii::t('app','has no attribute named "{name}".',['name' => $name]));
        }
    }



    /**
     * @param $name
     * @return array|mixed|string
     */
    public function getSmartAttribute($name)
    {
        /**
         * @var $property RelatedPropertyModel
         */
        $value          = $this->getAttribute($name);
        $property       = $this->getRelatedProperty($name);
        $propertyValue  = $this->getRelatedElementProperties($name);

        if ($property->property_type == PropertyType::CODE_LIST)
        {
            if ($property->multiple == Cms::BOOL_Y)
            {
                if ($property->enums)
                {
                    $result = [];

                    foreach ($property->enums as $enum)
                    {
                        if (in_array($enum->id, $value))
                        {
                            $result[$enum->code] = $enum->value;
                        }

                    }

                    return $result;
                }
            } else
            {
                if ($property->enums)
                {
                    $enum = array_shift($property->enums);

                    foreach ($property->enums as $enum)
                    {
                        if ($enum->id == $value)
                        {
                            return $enum->value;;
                        }
                    }
                }

                return "";
            }
        } else if ($property->property_type == PropertyType::CODE_ELEMENT)
        {
            if ($property->multiple == Cms::BOOL_Y)
            {
                return ArrayHelper::map(CmsContentElement::find()->where(['id' => $value])->all(), 'id', 'name');
            } else
            {
                if ($element = CmsContentElement::find()->where(['id' => $value])->one())
                {
                    return $element->name;
                }

                return "";
            }
        } else
        {
            return $value;
        }
    }

    public function getEnumByAttribute($name)
    {
        /**
         * @var $property RelatedPropertyModel
         */
        $value      = $this->getAttribute($name);
        $property   = $this->getRelatedProperty($name);

        if ($property->property_type == PropertyType::CODE_LIST)
        {
            if ($property->multiple == Cms::BOOL_Y)
            {
                if ($property->enums)
                {
                    $result = [];

                    foreach ($property->enums as $enum)
                    {
                        if (in_array($enum->id, $value))
                        {
                            $result[$enum->code] = $enum;
                        }

                    }

                    return $result;
                }
            } else
            {
                if ($property->enums)
                {
                    $enum = array_shift($property->enums);

                    foreach ($property->enums as $enum)
                    {
                        if ($enum->id == $value)
                        {
                            return $enum;;
                        }
                    }
                }

                return "";
            }
        }

        return null;
    }
}