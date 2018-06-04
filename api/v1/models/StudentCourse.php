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
     *         type="string",
     *         description="Teacher's Full Name",
     *         example="Ali Shamakhi"
     *     )
     * )
     */
    public $teachers_full_names;
}
