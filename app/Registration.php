<?php

namespace App;

use App\Models\District;
use App\Models\Experience;
use App\Models\Quota;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * @method static create(array $array)
 * @method static where(string $string, $key)
 */
class Registration extends Model
{
    protected $fillable = [
        'name_of_the_post',
        'name_of_the_post_bn',
        'applicants_name',
        'applicants_name_bn',
        'father_name',
        'father_name_bn',
        'mother_name',
        'mother_name_bn',
        'date_Of_birth',
        'place_of_birth',
        'gender',
        'nationality',
        'national_id',
        'birth_registration',
        'passport_id',
        'religion',
        'marital_status',
        'quota',
        'image',
        'signature_img',
        'present_care_of',
        'present_village',
        'present_district',
        'present_upazila',
        'present_post_office',
        'present_post_code',
        'same_as_present_address',
        'permanent_care_of',
        'permanent_village',
        'permanent_district',
        'permanent_upazila',
        'permanent_post_office',
        'permanent_post_code',
        'mobile_number',
        'email',
        'ssc_examination',
        'ssc_roll_no',
        'ssc_registration_no',
        'ssc_group_subject',
        'ssc_board',
        'ssc_result',
        'ssc_passing_year',
        'hsc_examination',
        'hsc_roll_no',
        'hsc_registration_no',
        'hsc_group_subject',
        'hsc_board',
        'hsc_result',
        'hsc_passing_year',
        'graduation_examination',
        'graduation_institute',
        'graduation_passing_year',
        'graduation_subject_degree',
        'graduation_result',
        'graduation_course_duration',
        'masters_examination',
        'masters_institute',
        'masters_passing_year',
        'masters_subject_degree',
        'masters_result',
        'masters_course_duration',
    ];

    public function experiences(){
        return $this->hasMany(Experience::class);
    }

    public function Quotas(){
        return $this->hasMany(Quota::class);
    }
}
