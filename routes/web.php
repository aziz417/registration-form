<?php

use App\Http\Controllers\Backend\BoardController;
use App\Http\Controllers\Backend\InstituteController;
use App\Http\Controllers\Backend\SectionController;
use App\Http\Controllers\Backend\SubjectController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Barryvdh\DomPDF\Facade;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Auth::routes(['register' => false]);

Route::group(['middleware' => ['auth']], function (){
    Route::get('/', [HomeController::class, 'frontend'])->name('home');

    Route::get('registration/form', [RegistrationController::class, 'create'])->name('registration.create');

    Route::resource('registrations', RegistrationController::class);
    Route::get('search/register/{register?}', [RegistrationController::class, 'searchRegister'])->name('search.register');
    Route::get('experience/delete', [RegistrationController::class, 'deleteExperience'])->name('experience.delete');
    Route::get('select/items', [RegistrationController::class, 'selectItems'])->name('select.items');
    Route::get('register/search', [RegistrationController::class, 'registerSearchAutocomplete'])->name('register.search.autocomplete');

    Route::get('pdf/{key}', function ($key){
        $register = \App\Registration::with('experiences')->where('national_id', $key)->orWhere('mobile_number', $key)
            ->orWhere('ssc_registration_no', $key)->orWhere('hsc_registration_no', $key)->first();
        if ($register){
            $pdf = PDF::loadView('view_pdf', compact('register'));
            //$applyForm->setIsRemoteEnabled('isRemoteEnabled', true);
            //$applyForm = new Dompdf($applyForm);
            //$profileImage =  asset('uploads/applications/'.$applyForm->profile_photo_one);
            return $pdf->stream('register.pdf');
        }else{
            return redirect('registration/form')->with('warningMsg', 'Register Not Found');
        }
    })->name('pdf.view');
});


// admin section router
Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function (){

    Route::get('dashboard', [HomeController::class, 'index'])->name('admin');
    Route::get('/', [HomeController::class, 'index'])->name('admin');
    Route::resource('boards', BoardController::class)->except(['show']);
    Route::resource('institutes', InstituteController::class)->except(['show']);
    Route::resource('subjects', SubjectController::class)->except(['show']);
    Route::resource('sections', SectionController::class)->except(['show']);
});




