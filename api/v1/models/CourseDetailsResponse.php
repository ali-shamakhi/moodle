<?php
/**
 * @SWG\Definition()
 */

class CourseDetailsResponse
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

    public function __construct($error, $course_id, $session_start_timestamp, $session_end_timestamp, $past_attendance_count, $teachers_full_name, $students_details)
    {
        $this->error = $error;
        $this->course_id = $course_id;
        $this->session_start_timestamp = $session_start_timestamp;
        $this->session_end_timestamp = $session_end_timestamp;
        $this->past_attendance_count = $past_attendance_count;
        $this->teachers_full_names = $teachers_full_name;
        $this->students_details = $students_details;
    }
}
