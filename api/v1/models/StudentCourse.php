<?php
/**
 * @SWG\Definition()
 */

class StudentCourse
{
    /**
     * @var int
     * @SWG\Property(
     *     example=1
     * )
     */
    public $course_id;

    /**
     * @var string
     * @SWG\Property(
     *     description="Course's Full Name",
     *     example="Computer Engineering 3962"
     * )
     */
    public $course_name;

    /**
     * @var int
     * @SWG\Property(
     *     example=3
     * )
     */
    public $absence_count;

    /**
     * @var array
     * @SWG\Property(
     *     @SWG\Items(
     *         ref="#/definitions/TeacherDetails"
     *     )
     * )
     */
    public $teacher_details;

    public function __construct($course_id, $course_name, $absence_count, $teacher_details)
    {
        $this->course_id = $course_id;
        $this->course_name = $course_name;
        $this->absence_count = $absence_count;
        $this->teacher_details = $teacher_details;
    }
}
