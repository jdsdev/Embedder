<?php

namespace jdsdev\embedder\migrations;

use craft\db\Migration;
use jdsdev\embedder\fields\EmbedderFieldType;

/**
 * m180412_205549_update_embedder_fields migration.
 */
class m180412_205549_update_embedder_fields extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Auto-convert old Embedder fields
        $this->update('{{%fields}}', [
            'type' => EmbedderFieldType::class
        ], [
            'type' => 'Embedder'
        ], [], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180412_205549_update_embedder_fields cannot be reverted.\n";
        return false;
    }
}
