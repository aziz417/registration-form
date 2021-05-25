<?php

namespace App\Http\Controllers;


use App\Models\Board;
use App\Models\District;
use App\Models\Experience;
use App\Models\Institute;
use App\Models\Quota;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Upazila;
use App\Registration;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade;
use Illuminate\Support\Str;
use Session;


class RegistrationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $districts = District::all();
        $boards = Board::all();
        $ssc = Section::where('class_type_id', 3)->get();
        $hsc = Section::where('class_type_id', 4)->get();
        $graduations = Section::where('class_type_id', 5)->get();
        $masters = Section::where('class_type_id', 6)->get();
        $graduation_institutes = Institute::where('class_type_id', 5)->get();
        $master_institutes = Institute::where('class_type_id', 6)->get();
        $quotas = Quota::where('quota_type', 'base_quota')->get();

        return view('registration-form', compact('quotas', 'districts', 'ssc', 'hsc', 'graduations', 'masters', 'boards', 'graduation_institutes', 'master_institutes'));

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $class = ['ssc', 'hsc', 'graduation', 'masters'];
        foreach ($class as $className){
            $other_result = $className.'_other_result';
            $result = $className.'_result';

            if($request->$other_result){
                if($request->$result === 'CGPA(Out of 5)'){
                    $request->$result = $request->$other_result.' (Out of 5)';
                }elseif($request->$result === 'CGPA(Out of 4)'){
                    $request->$result = $request->$other_result.' (Out of 4)';
                }else{
                    $request->$result = $request->$other_result;
                }
            }
        }

        if ($request->input('ssc_group_subject') == "other") {
            $addSubject = $request->input('ssc_add_other_subject');
            $addSubject = trim($addSubject);
            $sectionName = explode(',', $request->input('ssc_examination'))[0];
            $classTypeId = 3;
            $addNewSubject = $this->storeOtherSubject($addSubject, $sectionName, $classTypeId);
            if ($addNewSubject == false) {
                return redirect('registration/form')->with('errorMsg', $addSubject.' subject/department already exist');
            } else {
                $request->ssc_group_subject = $addSubject;
            }
        }

        if ($request->input('hsc_group_subject') == "other") {
            $addSubject = $request->input('hsc_add_other_subject');
            $addSubject = trim($addSubject);
            $sectionName = explode(',', $request->input('hsc_examination'))[0];
            $classTypeId = 4;
            $addNewSubject = $this->storeOtherSubject($addSubject, $sectionName, $classTypeId);
            if ($addNewSubject == false) {
                return redirect('registration/form')->with('errorMsg', $addSubject.' subject/department already exist');
            } else {
                $request->hsc_group_subject = $addSubject;
            }
        }

        if ($request->input('graduation_subject_degree') == "other") {
            $addSubject = $request->input('graduation_add_other_subject');
            $addSubject = trim($addSubject);
            $sectionName = explode(',', $request->input('graduation_examination'))[0];
            $classTypeId = 5;
            $addNewSubject = $this->storeOtherSubject($addSubject, $sectionName, $classTypeId);
            if ($addNewSubject == false) {
                return redirect('registration/form')->with('errorMsg', $addSubject.' subject/department already exist');
            } else {
                $request->graduation_subject_degree = $addSubject;
            }
        }

        if ($request->input('masters_subject_degree') == "other") {
            $addSubject = $request->input('masters_add_other_subject');
            $addSubject = trim($addSubject);
            $sectionName = explode(',', $request->input('masters_examination'))[0];
            $classTypeId = 6;
            $addNewSubject = $this->storeOtherSubject($addSubject, $sectionName, $classTypeId);
            if ($addNewSubject == false) {
                return redirect('registration/form')->with('errorMsg', $addSubject.' subject/department already exist');
            } else {
                $request->masters_subject_degree = $addSubject;
            }
        }

        $image_name = '';
        $slug = Str::slug($request->name_of_the_post) . '-' . 'profile';

        if ($request->hasFile('photo')) {

            $image = $request->file('photo');
            $image_name = CommonController::fileUploaded(
                $slug, false, $image, 'applications', ['width' => '160', 'height' => '160']
            );
        }

        $signature_img = '';
        $slug = Str::slug($request->name_of_the_post) . '-' . 'signature';

        if ($request->hasFile('signature')) {

            $image = $request->file('signature');
            $signature_img = CommonController::fileUploaded(
                $slug, false, $image, 'applications', ['width' => '160', 'height' => '160']
            );
        }

        // if marital_status Married adjust spouse name
        if ($request->marital_status === 'Married'){
            $request->marital_status = 'Married'.' (Spouse Name: '.ucfirst($request->spouse_name).')';
        }

        if($request->same_as_present_address === 'true'){
            $request->permanent_care_of = $request->present_care_of;
            $request->permanent_village = $request->present_village;
            $request->permanent_district = $request->present_district;
            $request->permanent_upazila = $request->present_upazila;
            $request->permanent_post_office = $request->present_post_office;
            $request->permanent_post_code = $request->present_post_code;
        }

        $registration = Registration::create([
            'name_of_the_post' => $request->name_of_the_post,
            'name_of_the_post_bn' => $request->name_of_the_post_bn,
            'applicants_name' => $request->applicants_name,
            'applicants_name_bn' => $request->applicants_name_bn,
            'father_name' => $request->father_name,
            'father_name_bn' => $request->father_name_bn,
            'mother_name' => $request->mother_name,
            'mother_name_bn' => $request->mother_name_bn,
            'date_Of_birth' => $request->date_Of_birth,
            'place_of_birth' => $request->place_of_birth,
            'gender' => $request->gender,
            'nationality' => $request->nationality,
            'national_id' => $request->national_id,
            'birth_registration' => $request->birth_registration,
            'passport_id' => $request->passport_id,
            'religion' => $request->religion,
            'marital_status' => $request->marital_status,
            'image' => $image_name ? $image_name : '',
            'signature_img' => $signature_img ? $signature_img : '',
            'present_care_of' => $request->present_care_of,
            'present_village' => $request->present_village,
            'present_district' => explode(',', $request->present_district)[0],
            'present_upazila' => $request->present_upazila,
            'present_post_office' => $request->present_post_office,
            'present_post_code' => $request->present_post_code,
            'same_as_present_address' => $request->same_as_present_address ? 'true' : '',
            'permanent_care_of' => $request->permanent_care_of,
            'permanent_village' => $request->permanent_village,
            'permanent_district' => explode(',', $request->permanent_district)[0],
            'permanent_upazila' => $request->permanent_upazila,
            'permanent_post_office' => $request->permanent_post_office,
            'permanent_post_code' => $request->permanent_post_code,
            'mobile_number' => $request->mobile_number,
            'email' => $request->email,
            'ssc_examination' => explode(',', $request->ssc_examination)[0],
            'ssc_roll_no' => $request->ssc_roll_no,
            'ssc_registration_no' => $request->ssc_roll_no,
            'ssc_group_subject' => $request->ssc_group_subject,
            'ssc_board' => $request->ssc_board,
            'ssc_result' => $request->ssc_result,
            'ssc_passing_year' => $request->ssc_passing_year,
            'hsc_examination' => explode(',', $request->hsc_examination)[0],
            'hsc_roll_no' => $request->hsc_roll_no,
            'hsc_registration_no' => $request->hsc_roll_no,
            'hsc_group_subject' => $request->hsc_group_subject,
            'hsc_board' => $request->hsc_board,
            'hsc_result' => $request->hsc_result,
            'hsc_passing_year' => $request->hsc_passing_year,
            'graduation_examination' => explode(',', $request->graduation_examination)[0],
            'graduation_institute' => $request->graduation_institute,
            'graduation_passing_year' => $request->graduation_passing_year,
            'graduation_subject_degree' => $request->graduation_subject_degree,
            'graduation_result' => $request->graduation_result,
            'graduation_course_duration' => $request->graduation_course_duration,
            'masters_examination' => explode(',', $request->masters_examination)[0],
            'masters_institute' => $request->masters_institute,
            'masters_passing_year' => $request->masters_passing_year,
            'masters_subject_degree' => $request->masters_subject_degree,
            'masters_result' => $request->masters_result,
            'masters_course_duration' => $request->masters_course_duration,
        ]);

        if ($request->quota) {
            foreach ($request->quota as $key => $row) {
                Quota::create([
                    'registration_id' => $registration->id,
                    'quota' => $request->quota[$key],
                ]);
            }
        }

        if ($request->designation) {
            foreach ($request->experienceRows as $key => $row) {
                Experience::create([
                    'registration_id' => $registration->id,
                    'designation' => $request->designation[$key],
                    'start_date' => $request->start_date[$key],
                    'responsibilities' => $request->responsibilities[$key],
                    'organization_name' => $request->organization_name[$key],
                    'end_date' => $request->end_date[$key],
                    'till_now' => $request->till_now[$key],
                ]);
            }
        }

        if ($registration) {
            return redirect('registration/form')->with('success', 'Registration successfully');

        } else {
            return redirect('registration/form')->with('errorMsg', 'Something Wrong');
        }
    }

    public function storeOtherSubject($addSubject, $sectionName, $classTypeId)
    {
        $sectionId = Section::where('section_name', $sectionName)->pluck('id')->first();
        $existSubject = Subject::where(['subject_name' => $addSubject, 'section_id' => $sectionId, 'class_id' => $classTypeId])->first();
        if ($existSubject) {
            return false;
        } else {
            Subject::create([
                'section_id' => $sectionId,
                'subject_name' => $addSubject,
                'class_id' => $classTypeId,
            ]);
            return true;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Registration $registration
     * @return \Illuminate\Http\Response
     */
    public function show(Registration $registration)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Registration $registration
     * @return \Illuminate\Http\Response
     */
    public function edit(Registration $registration)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Registration $registration
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Registration $registration)
    {
        $class = ['ssc', 'hsc', 'graduation', 'masters'];
        foreach ($class as $className){
            $other_result = $className.'_other_result';
            $result = $className.'_result';

            if($request->$other_result){
                if($request->$result === 'CGPA(Out of 5)'){
                    $request->$result = $request->$other_result.' (Out of 5)';
                }elseif($request->$result === 'CGPA(Out of 4)'){
                    $request->$result = $request->$other_result.' (Out of 4)';
                }else{
                    $request->$result = $request->$other_result;
                }
            }
        }

        if ($request->input('ssc_group_subject') == "other") {
            $addSubject = $request->input('ssc_add_other_subject');
            $sectionName = explode(',', $request->input('ssc_examination'))[0];
            $classTypeId = 3;
            $addNewSubject = $this->storeOtherSubject($addSubject, $sectionName, $classTypeId);
            if ($addNewSubject == false) {
                return redirect('registration/form')->with('errorMsg', $addSubject.' subject/department already exist');
            } else {
                $request->ssc_group_subject = $addSubject;
            }
        }

        if ($request->input('hsc_group_subject') == "other") {
            $addSubject = $request->input('hsc_add_other_subject');
            $addSubject = trim($addSubject);
            $sectionName = explode(',', $request->input('hsc_examination'))[0];
            $classTypeId = 4;
            $addNewSubject = $this->storeOtherSubject($addSubject, $sectionName, $classTypeId);
            if ($addNewSubject == false) {
                return redirect('registration/form')->with('errorMsg', $addSubject.' subject/department already exist');
            } else {
                $request->hsc_group_subject = $addSubject;
            }
        }

        if ($request->input('graduation_subject_degree') == "other") {
            $addSubject = $request->input('graduation_add_other_subject');
            $addSubject = trim($addSubject);
            $sectionName = explode(',', $request->input('graduation_examination'))[0];
            $classTypeId = 5;
            $addNewSubject = $this->storeOtherSubject($addSubject, $sectionName, $classTypeId);
            if ($addNewSubject == false) {
                return redirect('registration/form')->with('errorMsg', $addSubject.' subject/department already exist');
            } else {
                $request->graduation_subject_degree = $addSubject;
            }
        }

        if ($request->input('masters_subject_degree') == "other") {
            $addSubject = $request->input('masters_add_other_subject');
            $addSubject = trim($addSubject);
            $sectionName = explode(',', $request->input('masters_examination'))[0];
            $classTypeId = 6;
            $addNewSubject = $this->storeOtherSubject($addSubject, $sectionName, $classTypeId);
            if ($addNewSubject == false) {
                return redirect('registration/form')->with('errorMsg', $addSubject.' subject/department already exist');
            } else {
                $request->masters_subject_degree = $addSubject;
            }
        }

        $image_name = '';
        $slug = Str::slug($request->name_of_the_post) . '-' . 'profile';

        if ($request->hasFile('photo')) {

            $image = $request->file('photo');
            $image_name = CommonController::fileUploaded(
                $slug, false, $image, 'applications', ['width' => '160', 'height' => '160'], $registration->image
            );
        }else{
            $image_name = $registration->image;
        }

        $signature_img = '';
        $slug = Str::slug($request->name_of_the_post) . '-' . 'signature';

        if ($request->hasFile('signature')) {
            $image = $request->file('signature');
            $signature_img = CommonController::fileUploaded(
                $slug, false, $image, 'applications', ['width' => '160', 'height' => '160'], $registration->signature_img
            );
        }else{
            $signature_img = $registration->signature_img;
        }

        // if marital_status Married adjust spouse name
        if ($request->marital_status === 'Married'){
            $request->marital_status = 'Married'.' (Spouse Name: '.ucfirst($request->spouse_name).')';
        }

        if($request->same_as_present_address === 'true'){
            $request->permanent_care_of = $request->present_care_of;
            $request->permanent_village = $request->present_village;
            $request->permanent_district = $request->present_district;
            $request->permanent_upazila = $request->present_upazila;
            $request->permanent_post_office = $request->present_post_office;
            $request->permanent_post_code = $request->present_post_code;
        }

        $registration->update([
            'name_of_the_post' => $request->name_of_the_post,
            'name_of_the_post_bn' => $request->name_of_the_post_bn,
            'applicants_name' => $request->applicants_name,
            'applicants_bn_name_bn' => $request->applicants_name_bn,
            'father_name' => $request->father_name,
            'father_name_bn' => $request->father_name_bn,
            'mother_name' => $request->mother_name,
            'mother_name_bn' => $request->mother_name_bn,
            'date_Of_birth' => $request->date_Of_birth,
            'place_of_birth' => $request->place_of_birth,
            'gender' => $request->gender,
            'nationality' => $request->nationality,
            'national_id' => $request->national_id,
            'birth_registration' => $request->birth_registration,
            'passport_id' => $request->passport_id,
            'religion' => $request->religion,
            'marital_status' => $request->marital_status,
            'image' => $image_name,
            'signature_img' => $signature_img,
            'present_care_of' => $request->present_care_of,
            'present_village' => $request->present_village,
            'present_district' => explode(',', $request->present_district)[0],
            'present_upazila' => $request->present_upazila,
            'present_post_office' => $request->present_post_office,
            'present_post_code' => $request->present_post_code,
            'same_as_present_address' => $request->same_as_present_address ? 'true' : '',
            'permanent_care_of' => $request->permanent_care_of,
            'permanent_village' => $request->permanent_village,
            'permanent_district' => explode(',', $request->permanent_district)[0],
            'permanent_upazila' => $request->permanent_upazila,
            'permanent_post_office' => $request->permanent_post_office,
            'permanent_post_code' => $request->permanent_post_code,
            'mobile_number' => $request->mobile_number,
            'email' => $request->email,
            'ssc_examination' => explode(',', $request->ssc_examination)[0],
            'ssc_roll_no' => $request->ssc_roll_no,
            'ssc_registration_no' => $request->ssc_roll_no,
            'ssc_group_subject' => $request->ssc_group_subject,
            'ssc_board' => $request->ssc_board,
            'ssc_result' => $request->ssc_result,
            'ssc_passing_year' => $request->ssc_passing_year,
            'hsc_examination' => explode(',', $request->hsc_examination)[0],
            'hsc_roll_no' => $request->hsc_roll_no,
            'hsc_registration_no' => $request->hsc_roll_no,
            'hsc_group_subject' => $request->hsc_group_subject,
            'hsc_board' => $request->hsc_board,
            'hsc_result' => $request->hsc_result,
            'hsc_passing_year' => $request->hsc_passing_year,
            'graduation_examination' => explode(',', $request->graduation_examination)[0],
            'graduation_institute' => $request->graduation_institute,
            'graduation_passing_year' => $request->graduation_passing_year,
            'graduation_subject_degree' => $request->graduation_subject_degree,
            'graduation_result' => $request->graduation_result,
            'graduation_course_duration' => $request->graduation_course_duration,
            'masters_examination' => explode(',', $request->masters_examination)[0],
            'masters_institute' => $request->masters_institute,
            'masters_passing_year' => $request->masters_passing_year,
            'masters_subject_degree' => $request->masters_subject_degree,
            'masters_result' => $request->masters_result,
            'masters_course_duration' => $request->masters_course_duration,
        ]);
        if ($request->quota) {
            foreach ($request->quota as $key => $row) {
                $quota = Quota::where('registration_id', $registration->id)
                    ->where('quota', $row)->first();
                if ($quota) {
                    $quota->update([
                        'registration_id' => $registration->id,
                        'quota' => $request->quota[$key],
                    ]);
                } else {
                    Quota::create([
                        'registration_id' => $registration->id,
                        'quota' => $request->quota[$key]
                    ]);
                }
            }
        }

        if ($request->designation) {
            Quota::where('registration_id', $registration->id)->whereNotIn('quota', $request->quota)->delete();
            foreach ($request->experienceRows as $key => $row) {
                $experience = Experience::Where('id', $row)->first();
                if ($experience) {
                    $experience->update([
                        'registration_id' => $registration->id,
                        'designation' => $request->designation[$key],
                        'start_date' => $request->start_date[$key],
                        'responsibilities' => $request->responsibilities[$key],
                        'organization_name' => $request->organization_name[$key],
                        'end_date' => $request->end_date[$key],
                        'till_now' => $request->till_now[$key],
                    ]);
                } else {
                    Experience::create([
                        'registration_id' => $registration->id,
                        'designation' => $request->designation[$key],
                        'start_date' => $request->start_date[$key],
                        'responsibilities' => $request->responsibilities[$key],
                        'organization_name' => $request->organization_name[$key],
                        'end_date' => $request->end_date[$key],
                        'till_now' => $request->till_now[$key],
                    ]);
                }
            }
        }

        if ($registration) {
            Session::forget('key');
            if ($request->input('updateAndPdfField')) {
                $registerIndentifiers = Registration::where('id', $registration->id)
                    ->select('national_id', 'mobile_number', 'hsc_registration_no', 'ssc_registration_no')->first();

                foreach ($registerIndentifiers->toArray() as $registerIndentifier) {
                    if ($registerIndentifiers) {
                        return redirect()->route('pdf.view', $registerIndentifier);
                    }
                }

            }
            return redirect('registration/form')->with('success', 'Registration Update successfully');
        } else {
            return redirect('registration/form')->with('errorMsg', 'Something Wrong');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Registration $registration
     * @return \Illuminate\Http\Response
     */
    public function destroy(Registration $registration)
    {
        //
    }

    public function searchRegister(Request $request, $key = null)
    {
        $key = $request->input('key') ? $request->input('key') : $key;
        if ($key){
            Session::forget('key');
            Session::put('key', $key);
        }else{
            $key = Session::get('key');
        }
        $register = Registration::where('national_id', $key)->orWhere('mobile_number', $key)
            ->orWhere('ssc_registration_no', $key)->orWhere('hsc_registration_no', $key)->first();
        if ($register) {
            $districts = District::all();
            $upazilas = Upazila::all();
            $subjects = Subject::all();
            $boards = Board::all();
            $ssc = Section::where('class_type_id', 3)->get();
            $hsc = Section::where('class_type_id', 4)->get();
            $graduations = Section::where('class_type_id', 5)->get();
            $masters = Section::where('class_type_id', 6)->get();
            $graduation_institutes = Institute::where('class_type_id', 5)->get();
            $master_institutes = Institute::where('class_type_id', 6)->get();

            $quotas = Quota::where('quota_type', 'base_quota')->get();
            $existQuotas = $register->quotas->pluck('quota')->toArray();

            return view('registration-update-form', compact('key', 'existQuotas', 'quotas', 'subjects', 'register', 'districts', 'upazilas', 'ssc', 'hsc', 'graduations', 'masters', 'boards', 'graduation_institutes', 'master_institutes'));
        } else {
            return redirect('registration/form');
        }
    }

    public function deleteExperience(Request $request)
    {
        $id = $request->id;
        $delete = Experience::Where('id', $id)->delete();
        if ($delete) {
            return true;
        }
    }

    // Select permanent_upazila
    public function selectItems(Request $request)
    {
        $data = [];
        if ($request->name === 'present_upazila') {
            $upazilas = Upazila::where('district_id', $request->id)->get();
            foreach ($upazilas as $upazila) {
                array_push($data, ['name' => $upazila->name, 'id' => $upazila->id]);
            }
            return $data;
        }

        if ($request->name === 'permanent_upazila') {
            $upazilas = Upazila::where('district_id', $request->id)->get();
            foreach ($upazilas as $upazila) {
                array_push($data, ['name' => $upazila->name, 'id' => $upazila->id]);
            }
            return $data;
        }

        if ($request->name === 'ssc' || $request->name === 'hsc' || $request->name === 'graduation' || $request->name === 'masters') {
            $subjects = Subject::where('section_id', $request->id)->get();
            foreach ($subjects as $subject) {
                array_push($data, ['name' => $subject->subject_name, 'id' => $subject->id]);
            }
            return $data;
        }
    }

    public function registerSearchAutocomplete(Request $request){
        $key = $request->search;
        $registers = Registration::where('national_id', 'like', "%$key%")
            ->orWhere('birth_registration', 'like', "%$key%")
            ->orWhere('ssc_registration_no', 'like', "%$key%")
            ->orWhere('hsc_registration_no', 'like', "%$key%")->get();

        return view('auto-suggestion-list', compact('registers'));
    }


    public function searchRegisterTest($key)
    {
        if ($key){
            Session::forget('key');
            Session::put('key', $key);
        }else{
            $key = Session::get('key');
        }
        $register = Registration::where('national_id', $key)->orWhere('mobile_number', $key)
            ->orWhere('ssc_registration_no', $key)->orWhere('hsc_registration_no', $key)->first();
        /*if ($register) {
            $districts = District::all();
            $upazilas = Upazila::all();
            $subjects = Subject::all();
            $boards = Board::all();
            $ssc = Section::where('class_type_id', 3)->get();
            $hsc = Section::where('class_type_id', 4)->get();
            $graduations = Section::where('class_type_id', 5)->get();
            $masters = Section::where('class_type_id', 6)->get();
            $graduation_institutes = Institute::where('class_type_id', 5)->get();
            $master_institutes = Institute::where('class_type_id', 6)->get();

            $quotas = Quota::where('quota_type', 'base_quota')->get();
            $existQuotas = $register->quotas->pluck('quota')->toArray();

        } else {
            return redirect('registration/form');
        }*/
        return $register;
    }
}
