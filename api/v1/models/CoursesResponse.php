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
     * @var string
     * @SWG\Property(
     *     description="First Teacher's picture URL"
     * )
     */
    public $pic_url;

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

    public function __construct($error, $pic_url, $teachers_courses=null, $student_courses=null)
    {
        $this->error = $error;
        $this->pic_url = $pic_url;
        $this->teachers_courses = $teachers_courses;
        $this->student_courses = $student_courses;
    }
}
