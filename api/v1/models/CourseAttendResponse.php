<?php
/**
 * @SWG\Definition()
 */

class CourseAttendResponse
{
    /**
     * @var int
     * @SWG\Property(
     *     description="1 is successful",
     *     example=1
     * )
     */
    public $result;

    /**
     * @var string
     * @SWG\Property(
     *     description="[Optional field] Error message (if there is error)",
     *     example="Unknown Error"
     * )
     */
    public $message;

    public function __construct($result, $message)
    {
        $this->result = $result;
        $this->message = $message;
    }
}
