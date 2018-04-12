<?php

namespace ap\embedder;

use ap\embedder\variables\EmbedderVariable;
use ap\embedder\fields\EmbedderFieldType;

use Craft;
use craft\services\Fields;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterComponentTypesEvent;

use yii\base\Event;

/**
 * Class Embedder
 *
 * @package   Embedder
 * @since     2.0.0
 */
class Embedder extends \craft\base\Plugin
{
    /**
     * @var Embedder
     */
    public static $plugin;

    /**
    * @inheritdoc
    */
    public $schemaVersion = '1.0.1';

    /**
    * @inheritdoc
    */
    public $hasCpSettings = false;

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
