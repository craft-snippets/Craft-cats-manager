<?php

namespace craftsnippets\craftcatsmanager;

use Craft;
use craft\base\Plugin;
use yii\base\Event;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;

class CraftCatsManager extends Plugin
{

    public static $plugin;

    public string $schemaVersion = '1.0.0';

    public bool $hasCpSettings = true;

    public bool $hasCpSection = true;

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // routes
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'cats-manager/' => 'craft-cats-manager/cats/cat-list',
                'cats-manager/new' => 'craft-cats-manager/cats/cat-edit',
                'cats-manager/<catId:\d+>' => 'craft-cats-manager/cats/cat-edit',
            ]);
        });

        // components
        $this->setComponents([
            'cats' => \craftsnippets\craftcatsmanager\services\CatsService::class,
        ]);
        
    }

    // navigation link
    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['url'] = 'cats-manager';
        $item['label'] = Craft::t('craft-cats-manager', 'Cats manager');
        return $item;
    }

}
