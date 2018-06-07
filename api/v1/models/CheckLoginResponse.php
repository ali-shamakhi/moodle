<?php
/**
 * @SWG\Definition()
 */

class CheckLoginResponse
{

    /**
     * @var boolean
     * @SWG\Property(
     *     example=0
     * )
     */
    public $logged_in;

    /**
     * @var string
     * @SWG\Property(
     *     example="{DOMAIN}/login?redirect={DOMAIN}/attendance/"
     * )
     */
    public $login_url;

    public function __construct($logged_in, $login_url)
    {
        $this->logged_in = $logged_in;
        $this->login_url = $login_url;
    }
}
