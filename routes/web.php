<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\AccessCodeController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdmissionTrackController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\DownloadController as AdminDownloadController;
use App\Http\Controllers\Admin\FacilityController as AdminFacilityController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\PpdbScheduleController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\RegistrationController as AdminRegistrationController;
use App\Http\Controllers\Admin\RegistrationDocumentController as AdminDocumentController;
use App\Http\Controllers\Admin\RegistrationWaveController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReregistrationController;
use App\Http\Controllers\Admin\SchoolProfileController;
use App\Http\Controllers\Admin\SelectionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\AppIconController;
use App\Http\Controllers\Applicant\AnnouncementController as ApplicantAnnouncementController;
use App\Http\Controllers\Applicant\DashboardController as ApplicantDashboardController;
use App\Http\Controllers\Applicant\DocumentController as ApplicantDocumentController;
use App\Http\Controllers\Applicant\NotificationController;
use App\Http\Controllers\Applicant\ProfileController as ApplicantProfileController;
use App\Http\Controllers\Applicant\ReceiptController;
use App\Http\Controllers\Applicant\SessionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Public\AnnouncementController as PublicAnnouncementController;
use App\Http\Controllers\Public\DownloadController;
use App\Http\Controllers\Public\GalleryController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\NewsController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PpdbInfoController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\Registration\AccountController as RegistrationAccountController;
use App\Http\Controllers\Registration\RegistrationDocumentController;
use App\Http\Controllers\Registration\RegistrationWizardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/profil', [PageController::class, 'profile'])->name('profile');
Route::get('/program', [PageController::class, 'programs'])->name('programs');
Route::get('/fasilitas', [PageController::class, 'facilities'])->name('facilities');
Route::get('/kontak', [PageController::class, 'contact'])->name('contact');

Route::get('/berita', [NewsController::class, 'index'])->name('news.index');
Route::get('/berita/{news:slug}', [NewsController::class, 'show'])->name('news.show');

Route::get('/galeri', [GalleryController::class, 'index'])->name('gallery.index');
Route::get('/galeri/{gallery:slug}', [GalleryController::class, 'show'])->name('gallery.show');

Route::get('/pengumuman', [PublicAnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/pengumuman/{announcement:slug}', [PublicAnnouncementController::class, 'show'])->name('announcements.show');

Route::get('/download', [DownloadController::class, 'index'])->name('downloads.index');
Route::get('/download/{download}', [DownloadController::class, 'download'])->name('downloads.download');

Route::get('/ppdb', [PpdbInfoController::class, 'index'])->name('ppdb.index');
Route::get('/ppdb/jadwal', [PpdbInfoController::class, 'schedule'])->name('ppdb.schedule');
Route::get('/ppdb/persyaratan', [PpdbInfoController::class, 'requirements'])->name('ppdb.requirements');

/*
|--------------------------------------------------------------------------
| PWA
|--------------------------------------------------------------------------
*/

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.service-worker');
Route::get('/offline', [PwaController::class, 'offline'])->name('pwa.offline');

// Favicon, iOS home-screen icon and manifest icons, all derived from the logo
// the school uploaded in the admin settings.
Route::get('/app-icon/{size}.png', [AppIconController::class, 'show'])
    ->whereNumber('size')
    ->name('pwa.icon');

/*
|--------------------------------------------------------------------------
| Registration wizard
|--------------------------------------------------------------------------
|
| No authentication: the draft lives in the visitor's session until the final
| submit hands out a registration number and access code.
|
*/

// no-store: the wizard holds personal data in progress, so neither the browser
// nor the service worker should retain these pages.
Route::prefix('daftar')->name('registration.')->middleware('no-store')->group(function (): void {
    // Step zero is public: opening the account issues the registration number
    // and the access code the rest of the flow authenticates with.
    Route::get('/', [RegistrationAccountController::class, 'create'])->name('start');
    Route::post('/', [RegistrationAccountController::class, 'store'])->name('start.store');

    Route::get('/verifikasi-email/{registration}', [RegistrationAccountController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('email.verify');

    // Everything below is the form itself, which requires a signed-in applicant.
    Route::middleware(['auth', 'active', 'role:applicant'])->group(function (): void {
        Route::get('/lanjutkan', [RegistrationWizardController::class, 'resume'])->name('resume');
        Route::post('/kirim-ulang-verifikasi', [RegistrationAccountController::class, 'resendVerification'])
            ->middleware('throttle:6,1')
            ->name('email.resend');

        Route::get('/biodata', [RegistrationWizardController::class, 'biodata'])->name('biodata');
        Route::post('/biodata', [RegistrationWizardController::class, 'storeBiodata'])->name('biodata.store');

        Route::get('/alamat', [RegistrationWizardController::class, 'address'])->name('address');
        Route::post('/alamat', [RegistrationWizardController::class, 'storeAddress'])->name('address.store');

        Route::get('/orang-tua', [RegistrationWizardController::class, 'parents'])->name('parents');
        Route::post('/orang-tua', [RegistrationWizardController::class, 'storeParents'])->name('parents.store');

        Route::get('/asal-sekolah', [RegistrationWizardController::class, 'previousSchool'])->name('previous-school');
        Route::post('/asal-sekolah', [RegistrationWizardController::class, 'storePreviousSchool'])->name('previous-school.store');

        Route::get('/program', [RegistrationWizardController::class, 'program'])->name('program');
        Route::post('/program', [RegistrationWizardController::class, 'storeProgram'])->name('program.store');

        Route::get('/berkas', [RegistrationDocumentController::class, 'index'])->name('documents');
        Route::post('/berkas/{documentType}', [RegistrationDocumentController::class, 'store'])->name('documents.store');
        Route::delete('/berkas/{document}', [RegistrationDocumentController::class, 'destroy'])->name('documents.destroy');
        Route::get('/berkas/{document}/pratinjau', [RegistrationDocumentController::class, 'preview'])->name('documents.preview');

        Route::get('/review', [RegistrationWizardController::class, 'review'])->name('review');
        Route::post('/kirim', [RegistrationWizardController::class, 'submit'])->name('submit');

        Route::get('/selesai', [RegistrationWizardController::class, 'success'])->name('success');
        Route::get('/selesai/bukti', [RegistrationWizardController::class, 'downloadReceipt'])->name('success.receipt');
    });
});

/*
|--------------------------------------------------------------------------
| Status check + applicant portal
|--------------------------------------------------------------------------
*/

// One sign-in for applicants, verifiers, PPDB admins and super admins.
Route::get('/masuk', [LoginController::class, 'show'])->middleware(['guest', 'no-store'])->name('login');
Route::post('/masuk', [LoginController::class, 'store'])
    ->middleware(['guest', 'no-store', 'throttle:login'])
    ->name('login.store');
Route::post('/keluar', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Kept so the receipt QR code, printed material and old bookmarks still land
// somewhere sensible.
Route::get('/cek-status', fn () => redirect()->route('login'))->name('status.form');

Route::prefix('pendaftar')->name('applicant.')
    ->middleware(['auth', 'active', 'role:applicant', 'no-store'])
    ->group(function (): void {
        Route::get('/dashboard', [ApplicantDashboardController::class, 'index'])->name('dashboard');

        Route::get('/biodata', [ApplicantProfileController::class, 'biodata'])->name('biodata');
        Route::get('/orang-tua', [ApplicantProfileController::class, 'parents'])->name('parents');
        Route::get('/asal-sekolah', [ApplicantProfileController::class, 'previousSchool'])->name('previous-school');
        Route::get('/program', [ApplicantProfileController::class, 'program'])->name('program');
        Route::get('/profil', [ApplicantProfileController::class, 'profile'])->name('profile');

        Route::get('/berkas', [ApplicantDocumentController::class, 'index'])->name('documents.index');
        Route::post('/berkas/{documentType}', [ApplicantDocumentController::class, 'store'])->name('documents.store');
        Route::get('/berkas/{document}/pratinjau', [ApplicantDocumentController::class, 'preview'])->name('documents.preview');
        Route::get('/berkas/{document}/unduh', [ApplicantDocumentController::class, 'download'])->name('documents.download');

        Route::get('/pengumuman', [ApplicantAnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('/pengumuman/{announcement:slug}', [ApplicantAnnouncementController::class, 'show'])->name('announcements.show');

        Route::get('/bukti-pendaftaran', [ReceiptController::class, 'download'])->name('receipt');

        Route::post('/notifikasi/{notification}/baca', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

        Route::post('/keluar', [SessionController::class, 'logout'])->name('logout');
    });

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function (): void {
    // The admin panel no longer has a sign-in of its own; this keeps old links
    // and bookmarks working.
    Route::get('/login', fn () => redirect()->route('login'))->middleware('guest')->name('login');

    // Applicants authenticate here too, so the whole panel is role gated.
    Route::middleware(['auth', 'active', 'role:super_admin,admin_ppdb,verifier', 'no-store'])->group(function (): void {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // --- Manajemen PPDB -------------------------------------------------
        Route::get('/pendaftar', [AdminRegistrationController::class, 'index'])->name('registrations.index');
        Route::get('/pendaftar/{registration}', [AdminRegistrationController::class, 'show'])->name('registrations.show');
        Route::delete('/pendaftar/{registration}', [AdminRegistrationController::class, 'destroy'])->name('registrations.destroy');
        Route::post('/pendaftar/{registration}/pulihkan', [AdminRegistrationController::class, 'restore'])
            ->withTrashed()->name('registrations.restore');

        Route::post('/pendaftar/{registration}/reset-kode', [AccessCodeController::class, 'reset'])->name('registrations.reset-code');
        Route::get('/pendaftar/{registration}/dokumen-pemulihan', [AccessCodeController::class, 'downloadRecovery'])->name('registrations.recovery');
        Route::get('/pendaftar/{registration}/bukti', [AdminRegistrationController::class, 'receipt'])->name('registrations.receipt');

        Route::get('/verifikasi', [VerificationController::class, 'index'])->name('verification.index');
        Route::get('/verifikasi/{registration}', [VerificationController::class, 'show'])->name('verification.show');
        Route::post('/verifikasi/berkas/{document}', [VerificationController::class, 'decide'])->name('verification.decide');
        Route::post('/verifikasi/{registration}/setujui-semua', [VerificationController::class, 'approveAll'])->name('verification.approve-all');
        Route::post('/verifikasi/{registration}/selesai', [VerificationController::class, 'complete'])->name('verification.complete');
        Route::post('/verifikasi/{registration}/buka', [VerificationController::class, 'reopen'])->name('verification.reopen');

        Route::get('/berkas/{document}/pratinjau', [AdminDocumentController::class, 'preview'])->name('documents.preview');
        Route::get('/berkas/{document}/unduh', [AdminDocumentController::class, 'download'])->name('documents.download');

        Route::get('/seleksi', [SelectionController::class, 'index'])->name('selection.index');
        // Declared before /seleksi/{registration} so the literal path is not
        // swallowed by route model binding.
        Route::post('/seleksi/publikasi-massal', [SelectionController::class, 'publishBulk'])->name('selection.publish-bulk');
        Route::post('/seleksi/{registration}', [SelectionController::class, 'store'])->name('selection.store');
        Route::delete('/seleksi/{registration}', [SelectionController::class, 'revoke'])->name('selection.revoke');
        Route::post('/seleksi/{registration}/publikasi', [SelectionController::class, 'publish'])->name('selection.publish');

        Route::get('/daftar-ulang', [ReregistrationController::class, 'index'])->name('reregistration.index');
        Route::post('/daftar-ulang/{registration}', [ReregistrationController::class, 'update'])->name('reregistration.update');

        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/ekspor', [ReportController::class, 'export'])->name('reports.export');

        // --- Konfigurasi PPDB ----------------------------------------------
        Route::middleware('role:super_admin,admin_ppdb')->group(function (): void {
            Route::resource('tahun-ajaran', AcademicYearController::class)
                ->parameters(['tahun-ajaran' => 'academicYear'])->names('academic-years')->except('show');
            Route::post('tahun-ajaran/{academicYear}/aktifkan', [AcademicYearController::class, 'activate'])
                ->name('academic-years.activate');

            Route::resource('gelombang', RegistrationWaveController::class)
                ->parameters(['gelombang' => 'wave'])->names('waves')->except('show');

            Route::resource('jalur', AdmissionTrackController::class)
                ->parameters(['jalur' => 'track'])->names('tracks')->except('show');
            Route::put('jalur/{track}/persyaratan', [AdmissionTrackController::class, 'updateRequirements'])
                ->name('tracks.requirements');

            Route::resource('program', ProgramController::class)
                ->parameters(['program' => 'program'])->names('programs')->except('show');

            Route::resource('persyaratan', DocumentTypeController::class)
                ->parameters(['persyaratan' => 'documentType'])->names('document-types')->except('show');

            Route::resource('jadwal', PpdbScheduleController::class)
                ->parameters(['jadwal' => 'schedule'])->names('schedules')->except('show');

            // --- Konten website --------------------------------------------
            Route::resource('pengumuman', AdminAnnouncementController::class)
                ->parameters(['pengumuman' => 'announcement'])->names('announcements')->except('show');
            Route::post('pengumuman/{announcement}/publikasi', [AdminAnnouncementController::class, 'togglePublish'])
                ->name('announcements.publish');

            Route::resource('berita', AdminNewsController::class)
                ->parameters(['berita' => 'news'])->names('news')->except('show');

            Route::resource('galeri', AdminGalleryController::class)
                ->parameters(['galeri' => 'gallery'])->names('galleries')->except('show');
            Route::post('galeri/{gallery}/gambar', [AdminGalleryController::class, 'storeImage'])->name('galleries.images.store');
            Route::delete('galeri/gambar/{image}', [AdminGalleryController::class, 'destroyImage'])->name('galleries.images.destroy');

            Route::resource('fasilitas', AdminFacilityController::class)
                ->parameters(['fasilitas' => 'facility'])->names('facilities')->except('show');

            Route::resource('unduhan', AdminDownloadController::class)
                ->parameters(['unduhan' => 'download'])->names('downloads')->except('show');

            Route::get('/profil-sekolah', [SchoolProfileController::class, 'index'])->name('school-profile.index');
            Route::put('/profil-sekolah/{profile}', [SchoolProfileController::class, 'update'])->name('school-profile.update');
        });

        // --- Sistem ---------------------------------------------------------
        Route::middleware('role:super_admin')->group(function (): void {
            Route::resource('users', UserController::class)->names('users')->except('show');
            Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
            Route::post('/settings/uji-email', [SettingController::class, 'sendTestMail'])->name('settings.test-mail');
        });

        Route::get('/activity-log', [ActivityLogController::class, 'index'])
            ->middleware('role:super_admin,admin_ppdb')->name('activity-log.index');

        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');
    });
});
