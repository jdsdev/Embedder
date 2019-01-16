<?php

namespace jdsdev\embedder\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use yii\db\Schema;

class EmbedderFieldType extends Field
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('embedder', 'Embedder');
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $variables = [
          'name' => $this->handle,
          'value' => $value,
          'field' => $this,
        ];

        return Craft::$app->getView()->renderTemplate(
            'embedder/_fields/input',
            $variables
        );
    }

}
