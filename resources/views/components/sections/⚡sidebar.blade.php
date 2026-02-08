<?php

use Livewire\Component;

new class extends Component {
    public function logout()
    {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        $this->redirect('/', navigate: true);
    }
};
?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar" :class="{ 'collapsed': collapsed }">
    <div class="sidebar-header">
        <div class="logo-container">
            <i class="fas fa-book-open"></i>
            <span class="logo-text">Doc Archive</span>
        </div>
        <button class="sidebar-toggle" @click="collapsed = !collapsed">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <a wire:navigate href="{{ route('dashboard') }}"
            class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a wire:navigate href="{{ route('dashboard.documents') }}"
            class="nav-item {{ request()->is('dashboard/documents') ? 'active' : '' }}">
            <i class="fas fa-folder-open"></i>
            <span>Semua Dokumen</span>
        </a>
        <a wire:navigate href="{{ route('dashboard.divisions') }}"
            class="nav-item {{ request()->is('dashboard/divisions') ? 'active' : '' }}">
            <i class="fas fa-building"></i>
            <span>Divisi</span>
        </a>
        <a wire:navigate href="{{ route('dashboard.users') }}"
            class="nav-item {{ request()->is('dashboard/users') ? 'active' : '' }}">
            <i class="fas fa-users"></i>
            <span>Kelola User</span>
        </a>
        <a wire:navigate href="{{ route('dashboard.documentaccess') }}"
            class="nav-item {{ request()->is('dashboard/documentaccess') ? 'active' : '' }}">
            <i class="fas fa-lock"></i>
            <span>Kelola Akses Dokumen</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <p class="user-name">{{ Auth::user()->name ?? 'User' }}</p>
                <p class="user-email">{{ Auth::user()->email }}</p>
            </div>
        </div>

        <a type="button" class="btn-view-website mb-2" href="{{ route('home') }}" target="_blank">
            <i class="fas fa-globe"></i>
            <span>Lihat Web</span>
        </a>

        <button type="button" class="btn-logout" wire:click="logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </button>
    </div>
</aside>
