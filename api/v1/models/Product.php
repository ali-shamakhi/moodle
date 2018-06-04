<?php
/**
 * @SWG\Definition()
 */

class Product
{
    /**
     * @var int
     * @SWG\Property()
     */
    public $id;

    /**
     * @var string
     * @SWG\Property()
     */
    public $name;

    /**
     * @var string
     * @SWG\Property()
     */
    public $description;

    /**
     * @var int
     * @SWG\Property()
     */
    public $price;

}
