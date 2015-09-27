<?php
namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\Writers\Traits
 */
trait ClassicWriter
{
    /**
     * @var bool
     */
    public $is_classic_writer = false;

    /**
     * Call when object is created
     */
    public function ActiveCollabDatabaseObjectTestFixturesWritersTraitsClassicWriter()
    {
        $this->is_classic_writer = true;
    }
}