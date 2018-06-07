<?php
/**
 * @SWG\Definition()
 */

class CoursesResponse
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

    /**
     * @var array
     * @SWG\Property(
     *     @SWG\Items(
     *         ref="#/definitions/TeacherCourse"
     *     )
     * )
     */
    public $teachers_courses;

    /**
     * @var array
     * @SWG\Property(
     *     @SWG\Items(
     *         ref="#/definitions/StudentCourse"
     *     )
     * )
     */
    public $student_courses;

    public function __construct($result, $message, $teachers_courses=null, $student_courses=null)
    {
        $this->result = $result;
        $this->message = $message;
        $this->teachers_courses = $teachers_courses;
        $this->student_courses = $student_courses;
    }
}
