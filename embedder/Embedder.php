<?php

namespace jdsdev\embedder;

use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use craft\web\twig\variables\CraftVariable;

use jdsdev\embedder\fields\EmbedderFieldType;
use jdsdev\embedder\variables\EmbedderVariable;

use yii\base\Event;

/**
 * Class Embedder
 *
 * @package Embedder
 * @since   2.0.0
 */
class Embedder extends Plugin
{
    /**
     * @var Embedder
     */
    public static Plugin $plugin;

    /**
     * @inheritdoc
     */
    public string $schemaVersion = '1.0.1';

    /**
     * @inheritdoc
     */
    public bool $hasCpSettings = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register fields
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = EmbedderFieldType::class;
            }
        );

        // Register variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $variable = $event->sender;
                $variable->set('embedder', EmbedderVariable::class);
            }
        );
    }
}
