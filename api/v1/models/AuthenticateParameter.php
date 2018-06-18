<?php
/**
 * @SWG\Definition()
 */

class AuthenticateParameter
{
    /**
     * @var string
     * @SWG\Property(
     *     example="{DOMAIN}/attendance/"
     * )
     */
    public $redirect_url;

    /**
     * @var string
     * @SWG\Property(
     *     example="attendance"
     * )
     */
    public $access_domain;

    public function __construct($redirect_url, $access_domain)
    {
        $this->redirect_url = $redirect_url;
        $this->access_domain = $access_domain;
    }
}
