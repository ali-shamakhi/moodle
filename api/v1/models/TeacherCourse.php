<?php
/**
 * @SWG\Definition()
 */

class TeacherCourse
{
    /**
     * @var int
     * @SWG\Property(
     *     example=2
     * )
     */
    public $course_id;

    /**
     * @var string
     * @SWG\Property(
     *     example="Computer Science 3962"
     * )
     */
    public $course_name;

    public function __construct($course_id, $course_name)
    {
        $this->course_id = $course_id;
        $this->course_name = $course_name;
    }
}
