<?php
/**
 * @SWG\Definition()
 */

class CheckLoginResponse
{

    /**
     * @var boolean
     * @SWG\Property(
     *     example="true"
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

    public function __construct($logged_in, $full_name)
    {
        $this->logged_in = $logged_in;
        $this->full_name = $full_name;
    }
}
