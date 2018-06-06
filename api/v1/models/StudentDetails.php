<?php
/**
 * @SWG\Definition()
 */

class StudentDetails
{
    /**
     * @var int
     * @SWG\Property(
     *     example=7
     * )
     */
    public $id;

    /**
     * @var string
     * @SWG\Property(
     *     description="Student's Full Name",
     *     example="Masoud Moharrami"
     * )
     */
    public $full_name;

    /**
     * @var string
     * @SWG\Property(
     *     description="URL of the Student's Profile Picture",
     *     example="{DOMAIN}/user/pix.php/7/f1.jpg"
     * )
     */
    public $pic_url;

    /**
     * @var int
     * @SWG\Property(
     *     example=3
     * )
     */
    public $absence_count;

    public function __construct($id, $full_name, $pic_url, $absence_count)
    {
        $this->id = $id;
        $this->full_name = $full_name;
        $this->pic_url = $pic_url;
        $this->absence_count = $absence_count;
    }
}
