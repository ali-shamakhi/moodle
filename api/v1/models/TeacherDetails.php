<?php
/**
 * @SWG\Definition()
 */

class TeacherDetails
{
    /**
     * @var string
     * @SWG\Property(
     * )
     */
    public $full_name;

    /**
     * @var string
     * @SWG\Property(
     * )
     */
    public $pic_url;

    public function __construct($full_name, $pic_url)
    {
        $this->full_name = $full_name;
        $this->pic_url = $pic_url;
    }
}
