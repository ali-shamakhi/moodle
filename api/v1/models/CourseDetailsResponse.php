<?php
/**
 * @SWG\Definition()
 */

class CourseDetailsResponse
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
     * @var int
     * @SWG\Property(
     *     example=2
     * )
     */
    public $course_id;

    /**
     * @var int
     * @SWG\Property(
     *     example=1528095600
     * )
     */
    public $session_start_timestamp;

    /**
     * @var int
     * @SWG\Property(
     *     example=1528101000
     * )
     */
    public $session_end_timestamp;

    /**
     * @var int
     * @SWG\Property(
     *     description="Number of past taken attendances",
     *     example=10
     * )
     */
    public $past_attendance_count;

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

    /**
     * @var array
     * @SWG\Property(
     *     @SWG\Items(
     *         ref="#/definitions/StudentDetails"
     *     )
     * )
     */
    public $students_details;

}
