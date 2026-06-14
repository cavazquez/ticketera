<?php

use App\Http\Controllers\Client\TicketController as ClientTicketController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Panel\AgentController;
use App\Http\Controllers\Panel\CannedResponseController;
use App\Http\Controllers\Panel\DepartmentController;
use App\Http\Controllers\Panel\KnowledgeBaseController;
use App\Http\Controllers\Panel\SettingsController;
use App\Http\Controllers\Panel\SystemHealthController;
use App\Http\Controllers\Panel\TicketController as PanelTicketController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketAttachmentController;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/help', [HelpController::class, 'index'])->name('help.index');
Route::get('/help/{kbArticle:slug}', [HelpController::class, 'show'])->name('help.show');

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Setting::current()->allow_public_registration,
    ]);
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:cliente'])->prefix('tickets')->name('client.tickets.')->group(function () {
    Route::get('/', [ClientTicketController::class, 'index'])->name('index');
    Route::get('/create', [ClientTicketController::class, 'create'])->name('create');
    Route::post('/', [ClientTicketController::class, 'store'])
        ->middleware('throttle:ticket-creation')
        ->name('store');
    Route::get('/{ticket}', [ClientTicketController::class, 'show'])->name('show');
    Route::post('/{ticket}/reply', [ClientTicketController::class, 'reply'])
        ->middleware('throttle:ticket-replies')
        ->name('reply');
});

Route::middleware(['auth', 'verified', 'role:agente,admin'])->prefix('panel')->name('panel.')->group(function () {
    Route::get('/tickets', [PanelTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [PanelTicketController::class, 'show'])->name('tickets.show');
    Route::patch('/tickets/{ticket}', [PanelTicketController::class, 'update'])->name('tickets.update');
    Route::post('/tickets/{ticket}/reply', [PanelTicketController::class, 'reply'])
        ->middleware('throttle:ticket-replies')
        ->name('tickets.reply');

    Route::middleware('role:admin')->group(function () {
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::patch('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        Route::get('/agents/search', [AgentController::class, 'search'])->name('agents.search');
        Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
        Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
        Route::patch('/agents/{agent}', [AgentController::class, 'update'])->name('agents.update');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-imap', [SettingsController::class, 'testImap'])->name('settings.test-imap');
        Route::post('/settings/test-smtp', [SettingsController::class, 'testSmtp'])->name('settings.test-smtp');

        Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('knowledge-base.index');
        Route::post('/kb-categories', [KnowledgeBaseController::class, 'storeCategory'])->name('kb-categories.store');
        Route::patch('/kb-categories/{kbCategory}', [KnowledgeBaseController::class, 'updateCategory'])->name('kb-categories.update');
        Route::delete('/kb-categories/{kbCategory}', [KnowledgeBaseController::class, 'destroyCategory'])->name('kb-categories.destroy');
        Route::post('/kb-articles', [KnowledgeBaseController::class, 'storeArticle'])->name('kb-articles.store');
        Route::patch('/kb-articles/{kbArticle}', [KnowledgeBaseController::class, 'updateArticle'])->name('kb-articles.update');
        Route::delete('/kb-articles/{kbArticle}', [KnowledgeBaseController::class, 'destroyArticle'])->name('kb-articles.destroy');

        Route::get('/system-health', [SystemHealthController::class, 'index'])->name('system-health.index');

        Route::get('/canned-responses', [CannedResponseController::class, 'index'])->name('canned-responses.index');
        Route::post('/canned-responses', [CannedResponseController::class, 'store'])->name('canned-responses.store');
        Route::patch('/canned-responses/{cannedResponse}', [CannedResponseController::class, 'update'])->name('canned-responses.update');
        Route::delete('/canned-responses/{cannedResponse}', [CannedResponseController::class, 'destroy'])->name('canned-responses.destroy');
    });
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attachments/{attachment}/download', [TicketAttachmentController::class, 'download'])
        ->name('attachments.download');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
