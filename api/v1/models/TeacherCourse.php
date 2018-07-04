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

    /**
     * @var array
     * @SWG\Property(
     *     @SWG\Items(
     *         ref="#/definitions/TeacherDetails"
     *     )
     * )
     */
    public $teacher_details;

    public function __construct($course_id, $course_name, $teacher_details)
    {
        $this->course_id = $course_id;
        $this->course_name = $course_name;
        $this->teacher_details = $teacher_details;
    }
}
