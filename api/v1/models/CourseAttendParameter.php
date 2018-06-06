<?php
/**
 * @SWG\Definition()
 */

class CourseAttendParameter
{
    /**
     * @var int
     * @SWG\Property(
     *     example=2
     * )
     */
    public $course_id;

    /**
     * @var array
     * @SWG\Property(
     *     @SWG\Items(
     *         type="int",
     *         description="Student ID",
     *         example="7"
     *     )
     * )
     */
    public $attend_students;

    public function __construct($course_id, $attend_students)
    {
        $this->course_id = $course_id;
        $this->attend_students = $attend_students;
    }
}
