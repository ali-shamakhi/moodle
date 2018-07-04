<?php
/**
 * @SWG\Definition()
 */

class LoginDetailsResponse
{

    /**
     * @var int
     * @SWG\Property(
     *     example=1
     * )
     */
    public $id;

    /**
     * @var boolean
     * @SWG\Property(
     *     example=true
     * )
     */
    public $logged_in;

    /**
     * @var string
     * @SWG\Property(
     *     example="Ali Shamakhi"
     * )
     */
    public $full_name;

    public function __construct($id, $logged_in, $full_name)
    {
        $this->id = $id;
        $this->logged_in = $logged_in;
        $this->full_name = $full_name;
    }
}
