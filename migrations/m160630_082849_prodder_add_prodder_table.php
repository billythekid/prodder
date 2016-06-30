<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160630_082849_prodder_add_prodder_table extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
        // Create the craft_prodder table
        craft()->db->createCommand()->createTable('prodder', array(
            'channel'  => array('default' => ''),
            'lastProd' => array('column' => 'datetime'),
        ), null, true);
    }
}
