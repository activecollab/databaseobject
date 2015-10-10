<?php

namespace ActiveCollab\DatabaseObject\Test\Fixtures\Writers;

use ActiveCollab\DatabaseObject\Object;
use ActiveCollab\DatabaseObject\ObjectInterface;
use ActiveCollab\DateValue\DateValueInterface;
use InvalidArgumentException;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures\Writers
 */
abstract class BaseWriter extends Object implements ObjectInterface
{
    /**
     * Name of the table where records are stored
     *
     * @var string
     */
    protected $table_name = 'writers';

    /**
     * All table fields
     *
     * @var array
     */
    protected $fields = ['id', 'name', 'birthday'];

    /**
     * List of default field values
     *
     * @var array
     */
    protected $default_field_values = ['name' => 'Unknown Writer'];

    /**
     * Name of AI field (if any)
     *
     * @var string
     */
    protected $auto_increment = 'id';

    /**
     * @var string[]
     */
    protected $order_by = ['!id'];

    /**
     * Return value of name field
     *
     * @return string
     */
    public function getName()
    {
        return $this->getFieldValue('name');
    }

    /**
     * Set value of name field
     *
     * @param  string $value
     * @return $this
     */
    public function &setName($value)
    {
        $this->setFieldValue('name', $value);

        return $this;
    }

    /**
     * Return value of birthday field
     *
     * @return DateValueInterface
     */
    public function getBirthday()
    {
        return $this->getFieldValue('birthday');
    }

    /**
     * Set value of birthday field
     *
     * @param  DateValueInterface $value
     * @return $this
     */
    public function &setBirthday($value)
    {
        $this->setFieldValue('birthday', $value);

        return $this;
    }

    /**
     * Set value of specific field
     *
     * @param  string                   $name
     * @param  mixed                    $value
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function &setFieldValue($name, $value)
    {
        if ($value === null) {
            parent::setFieldValue($name, null);
        } else {
            switch ($name) {
                case 'id':
                    parent::setFieldValue($name, (integer) $value);
                    break;
                case 'name':
                    parent::setFieldValue($name, (string) $value);
                    break;
                case 'birthday':
                    parent::setFieldValue($name, $this->getDateValueInstaceFrom($value));
                    break;
                default:
                    throw new InvalidArgumentException("'$name' is not a known field");
            }
        }

        return $this;
    }
}