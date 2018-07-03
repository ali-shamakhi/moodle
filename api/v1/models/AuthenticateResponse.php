<?php
/**
 * @SWG\Definition()
 */

class AuthenticateResponse
{

    /**
     * @var string
     * @SWG\Property(
     *     example="{DOMAIN}/login?redirect={DOMAIN}/attendance/"
     * )
     */
    public $login_url;

    public function __construct($login_url)
    {
        $this->login_url = $login_url;
    }
}
