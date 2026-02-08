<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::⚡home')->name('home');
Route::livewire('/login', 'pages::auth.⚡login')->name('login')->middleware('guest');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('/dashboard/divisions', 'pages::dashboard.⚡divisi')->name('dashboard.divisions');
    Route::livewire('/dashboard/documents', 'pages::dashboard.⚡document')->name('dashboard.documents');
    Route::livewire('/dashboard/users', 'pages::dashboard.⚡user')->name('dashboard.users');
    Route::livewire('/dashboard/documentaccess', 'pages::dashboard.⚡document-access')->name('dashboard.documentaccess');
});
