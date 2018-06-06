<?php
/**
 * @SWG\Definition()
 */

class CourseIdParameter
{
    /**
     * @var int
     * @SWG\Property(
     *     example=2
     * )
     */
    public $course_id;

    public function __construct($course_id)
    {
        $this->course_id = $course_id;
    }
}
