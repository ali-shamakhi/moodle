<?php
/**
 * @SWG\Definition()
 */

class CoursesResponse
{

    /**
     * @var string
     * @SWG\Property(
     *     description="[Optional field] Error message (if there is error)",
     *     example="Unknown Error"
     * )
     */
    public $error;

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

    public function __construct($error, $teachers_courses=null, $student_courses=null)
    {
        $this->error = $error;
        $this->teachers_courses = $teachers_courses;
        $this->student_courses = $student_courses;
    }
}
