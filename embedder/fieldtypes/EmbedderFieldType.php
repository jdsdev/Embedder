<?php
namespace Craft;

/**
 * Entries field type
 */
class EmbedderFieldType extends BaseFieldType
{
    /**
     * Fieldtype name
     *
     * @return string
     */
    public function getName()
    {
        return Craft::t('Embedder');
    }
    /**
     * Define database column
     *
     * @return AttributeType::String
     */
    public function defineContentAttribute()
    {
        return array(AttributeType::String);
    }
    /**
     * Returns the field's input HTML.
     *
     * @param string $name
     * @param mixed  $value
     * @return string
     */
    public function getInputHtml($name, $value)
    {
        $inputId = craft()->templates->formatInputId($name);
        $namespaceInputId = craft()->templates->namespaceInputId($inputId);

        return craft()->templates->render('embedder/_fields/input', array(
            'id' => $namespaceInputId,
            'name'  => $name,
            'value' => $value
        ));
    }

}
