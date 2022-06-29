<?php

namespace craftsnippets\craftcatsmanager\models;

use Craft;
use craft\base\Model;

use craft\helpers\UrlHelper;

class Cat extends Model
{

    const JSON_PROPERTIES = ['catFood'];
    
    public $id;
    public $uid;
    public $name;
    public $jsonSettings;
    public $order;

    // json properties
    public $catFood;

    public function init(): void
    {
        $this->populateJsonSettings();
    }

    public function populateJsonSettings()
    {
        $jsonAttributes = json_decode($this->jsonSettings, true);
        if(!$jsonAttributes){
            return;
        }

        foreach ($jsonAttributes as $attributeKey => $attributeValue) {
            if(property_exists($this, $attributeKey) && in_array($attributeKey, self::JSON_PROPERTIES)){
                $this->{$attributeKey} = $attributeValue;
            }
        }
    }


    public function prepareJsonSettings()
    {
        $jsonSettings = [];
        foreach ($this::JSON_PROPERTIES as $property) {
            $jsonSettings[$property] = $this->{$property};
        }
        $jsonSettings = json_encode($jsonSettings);
        return $jsonSettings;
    }

    protected function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('cats-manager/' . $this->id);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'order'], 'number', 'integerOnly' => true];
        $rules[] = [['name'], 'required'];
        $rules[] = [['name'], 'string', 'max' => 255];
        return $rules;
    }

}
