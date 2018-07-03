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

    public function __construct($redirect_url)
    {
        $this->redirect_url = $redirect_url;
    }
}
