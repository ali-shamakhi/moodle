<?php
/**
 * @SWG\Definition()
 */

class AuthenticateResponse
{

    /**
     * @var string
     * @SWG\Property(
     *     description="If null, user is not logged in.",
     *     example="72d07ca72eaa1ee6848113d0b5a305ab"
     * )
     */
    public $access_token;

    /**
     * @var string
     * @SWG\Property(
     *     example="{DOMAIN}/login?redirect={DOMAIN}/attendance/"
     * )
     */
    public $login_url;

    public function __construct($access_token, $login_url)
    {
        $this->access_token = $access_token;
        $this->login_url = $login_url;
    }
}
