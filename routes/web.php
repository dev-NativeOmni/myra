<?php

use App\Http\Controllers\AcademicCalendarController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassScheduleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\ModuleSettingController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\PlatformInstitutionController;
use App\Http\Controllers\ReportInputController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TahfidzJournalController;
use App\Http\Controllers\TenantGatewayController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Institution Token Gateway Routes (Public)
Route::get('/', [TenantGatewayController::class, 'index'])->name('home');
Route::get('/gateway', [TenantGatewayController::class, 'index'])->name('gateway.index');
Route::post('/gateway/verify', [TenantGatewayController::class, 'verify'])->name('gateway.verify');
Route::post('/gateway/reset', [TenantGatewayController::class, 'reset'])->name('gateway.reset');
Route::get('/portal/{token}', [TenantGatewayController::class, 'direct'])->name('gateway.direct');

// Guest / Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Ends a Super Admin support session; available while acting as an institution's Admin.
    Route::post('/impersonation/stop', [ImpersonationController::class, 'stop'])->name('impersonation.stop');

    // Platform Super Admin: monitors institutions, never edits their data directly.
    Route::middleware('role:super_admin')->prefix('platform')->name('platform.')->group(function () {
        Route::get('/institutions', [PlatformInstitutionController::class, 'index'])->name('institutions.index');
        Route::post('/institutions', [PlatformInstitutionController::class, 'store'])->name('institutions.store');
        Route::post('/institutions/{institution}/toggle', [PlatformInstitutionController::class, 'toggle'])->name('institutions.toggle');
        Route::post('/institutions/{institution}/impersonate/{user}', [ImpersonationController::class, 'start'])->name('institutions.impersonate');
    });

    // Institution Admin: Profil Lembaga, Master Data, User Management, Siklus Laporan, Batch Export, Calendar & Schedules
    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/institution', [InstitutionController::class, 'edit'])->name('institution.edit');
        Route::put('/institution', [InstitutionController::class, 'update'])->name('institution.update');

        // Master Data
        Route::resource('classrooms', ClassroomController::class)->except(['create', 'show', 'edit']);

        // Import/Export routes must be registered before the students resource route,
        // otherwise "export"/"import" would be matched as the {student} route parameter.
        Route::get('/students/export', [StudentController::class, 'export'])->name('students.export');
        Route::get('/students/import/template', [StudentController::class, 'importTemplate'])->name('students.import.template');
        Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
        Route::resource('students', StudentController::class);

        // Import/Export routes must be registered before the users resource route.
        Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
        Route::get('/users/import/template', [UserController::class, 'importTemplate'])->name('users.import.template');
        Route::post('/users/import', [UserController::class, 'import'])->name('users.import');
        Route::resource('users', UserController::class);
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // Jadwal Kelas & Kalender Akademik (Admin)
        Route::get('/class-schedules', [ClassScheduleController::class, 'index'])->name('class-schedules.index');
        Route::post('/class-schedules', [ClassScheduleController::class, 'update'])->name('class-schedules.update');

        Route::get('/academic-calendar', [AcademicCalendarController::class, 'index'])->name('academic-calendar.index');
        Route::post('/academic-calendar', [AcademicCalendarController::class, 'update'])->name('academic-calendar.update');

        // Laporan Bulanan & Ekspor Massal
        Route::resource('reports', MonthlyReportController::class)->except(['edit', 'update']);
        Route::get('/reports-export/batch', [MonthlyReportController::class, 'batchExportForm'])->name('reports.batch-export');
        Route::post('/reports-export/batch', [MonthlyReportController::class, 'batchExport'])->name('reports.batch-export.download');
        Route::post('/reports-publish', [MonthlyReportController::class, 'publishClassroom'])->name('reports.publish-classroom');

        // All-in-One Report Record Edit
        Route::get('/reports/{id}/edit-record', [ReportInputController::class, 'editRecord'])->name('reports.edit-record');
        Route::put('/reports/{id}/update-record', [ReportInputController::class, 'updateRecord'])->name('reports.update-record');

        // Pengaturan Modul Penilaian (Tahfidz, Kesantrian, Akademik, Administrasi)
        Route::get('/module-settings', [ModuleSettingController::class, 'index'])->name('module-settings.index');
        Route::post('/module-settings', [ModuleSettingController::class, 'store'])->name('module-settings.store');
        Route::put('/module-settings/{id}', [ModuleSettingController::class, 'update'])->name('module-settings.update');
        Route::match(['POST', 'PATCH'], '/module-settings/{id}/toggle', [ModuleSettingController::class, 'toggle'])->name('module-settings.toggle');
        Route::delete('/module-settings/{id}', [ModuleSettingController::class, 'destroy'])->name('module-settings.destroy');
        Route::post('/module-settings/reset', [ModuleSettingController::class, 'reset'])->name('module-settings.reset');
    });

    // Preview PDF, Profil Santri, & Dashboard Kelengkapan Laporan for Staff & Admin
    Route::middleware('role:admin,guru,wali_kelas,kesantrian,tu')->group(function () {
        Route::get('/reports/{id}/preview', [MonthlyReportController::class, 'preview'])->name('reports.preview');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::get('/reports-completeness', [MonthlyReportController::class, 'completeness'])->name('reports.completeness');
    });

    // Monitoring & Analitik Lembaga (Admin, Guru, Wali Kelas)
    Route::middleware('role:admin,guru,wali_kelas')->group(function () {
        Route::get('/monitoring', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::post('/monitoring/target', [AnalyticsController::class, 'updateTarget'])->name('analytics.update-target');
    });

    // Modul Tahfidz & Jurnal Harian (Admin, Guru Tahfidz)
    Route::middleware('role:admin,guru')->group(function () {
        Route::get('/tahfidz-journals/spreadsheet', [TahfidzJournalController::class, 'spreadsheet'])->name('tahfidz-journals.spreadsheet');
        Route::post('/tahfidz-journals/batch-store', [TahfidzJournalController::class, 'batchStore'])->name('tahfidz-journals.batch-store');
        Route::post('/tahfidz-journals/bulk-destroy', [TahfidzJournalController::class, 'bulkDestroy'])->name('tahfidz-journals.bulk-destroy');
        Route::resource('tahfidz-journals', TahfidzJournalController::class);
        Route::post('/reports/{id}/sync-tahfidz', [TahfidzJournalController::class, 'syncToReport'])->name('reports.sync-tahfidz');
    });

    // Dedicated Teacher & Staff Modules
    Route::prefix('input')->name('modules.')->group(function () {
        // Unified Spreadsheet Matrix Input (Admin, Guru, Wali Kelas, Kesantrian, TU)
        Route::middleware('role:admin,guru,wali_kelas,kesantrian,tu')->group(function () {
            Route::get('/spreadsheet', [ReportInputController::class, 'spreadsheet'])->name('spreadsheet');
            Route::post('/batch-store', [ReportInputController::class, 'batchStore'])->name('batch-store');
        });

        // Guru Tahfidz
        Route::middleware('role:admin,guru')->group(function () {
            Route::get('/tahfidz', [ReportInputController::class, 'moduleTahfidz'])->name('tahfidz');
            Route::put('/tahfidz/{id}', [ReportInputController::class, 'updateTahfidz'])->name('tahfidz.update');
        });

        // Kesantrian / Wali Asrama
        Route::middleware('role:admin,kesantrian')->group(function () {
            Route::get('/kesantrian', [ReportInputController::class, 'moduleKesantrian'])->name('kesantrian');
            Route::put('/kesantrian/{id}', [ReportInputController::class, 'updateKesantrian'])->name('kesantrian.update');
        });

        // Wali Kelas / Akademik
        Route::middleware('role:admin,wali_kelas')->group(function () {
            Route::get('/akademik', [ReportInputController::class, 'moduleAkademik'])->name('akademik');
            Route::put('/akademik/{id}', [ReportInputController::class, 'updateAkademik'])->name('akademik.update');
        });

        // Tata Usaha / Keuangan
        Route::middleware('role:admin,tu')->group(function () {
            Route::get('/administrasi', [ReportInputController::class, 'moduleAdministrasi'])->name('administrasi');
            Route::put('/administrasi/{id}', [ReportInputController::class, 'updateAdministrasi'])->name('administrasi.update');
        });
    });

    // Wali Murid / Orang Tua Portal
    Route::prefix('parent')->name('parent.')->middleware('role:wali_murid')->group(function () {
        Route::get('/dashboard', [ParentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/reports/{id}/preview', [ParentPortalController::class, 'previewReport'])->name('reports.preview');
    });
});
