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
}
