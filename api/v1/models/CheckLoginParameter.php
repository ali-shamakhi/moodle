<?php
/**
 * @SWG\Definition()
 */

class CheckLoginParameter
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
