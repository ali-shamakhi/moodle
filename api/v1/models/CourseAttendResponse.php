<?php
/**
 * @SWG\Definition()
 */

class CourseAttendResponse
{

    /**
     * @var string
     * @SWG\Property(
     *     description="[Optional field] Error message (if there is error)",
     *     example="Unknown Error"
     * )
     */
    public $error;

    public function __construct($error)
    {
        $this->error = $error;
    }
}
