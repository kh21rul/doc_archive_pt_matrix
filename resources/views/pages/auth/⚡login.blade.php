<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    #[
        Validate(
            'required|email',
            message: [
                'required' => 'Email wajib diisi.',
                'email' => 'Format email tidak valid.',
            ],
        ),
    ]
    public $email = '';

    #[
        Validate(
            'required|min:6',
            message: [
                'required' => 'Password wajib diisi.',
                'min' => 'Password minimal 6 karakter.',
            ],
        ),
    ]
    public $password = '';

    public $remember = false;

    public function login()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();

            // Flash success message
            session()->flash('success', 'Login berhasil!');

            return redirect()->intended('/dashboard');
        }

        $this->addError('email', 'Email atau password yang Anda masukkan salah.');
    }
};
?>

<div class="login-page d-flex flex-column justify-content-center align-items-center min-vh-100">
    <div class="login-container d-flex flex-column align-item-center">
        <div class="login-card">

            <!-- Form Login -->
            <div class="login-body">
                <form wire:submit.prevent="login">
                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="email" id="email" class="form-input @error('email') is-invalid @enderror"
                                wire:model="email" placeholder="Masukkan email Anda" autofocus>
                        </div>
                        @error('email')
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <i class="fas fa-key"></i>
                            </span>
                            <input type="password" id="password"
                                class="form-input @error('password') is-invalid @enderror" wire:model="password"
                                placeholder="Masukkan password Anda">
                        </div>
                        @error('password')
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" class="custom-checkbox" wire:model="remember">
                            <label for="remember" class="remember-label">Ingat saya</label>
                        </div>
                        <a href="#" class="forgot-link">Lupa password?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-login" wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            <i class="fas fa-sign-in-alt"></i> Masuk
                        </span>
                        <span wire:loading>
                            <span class="spinner"></span> Memproses...
                        </span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Copyright -->
        <div class="copyright">
            <p>&copy; {{ date('Y') }} Document Archive. All rights reserved.</p>
        </div>
    </div>
</div>

<style>
    .login-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #c2a25d 0%, #8b7355 100%);
        padding: 20px;
        position: relative;
        overflow: hidden;
    }

    /* Background Pattern */
    .login-page::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image:
            repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255, 255, 255, .05) 35px, rgba(255, 255, 255, .05) 70px);
        pointer-events: none;
    }

    .login-container {
        max-width: 460px;
        width: 100%;
        position: relative;
        z-index: 1;
    }

    .login-card {
        background: white;
        border-radius: 24px;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(40px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Header */
    .login-header {
        background: linear-gradient(135deg, #c2a25d 0%, #a88a4d 100%);
        padding: 50px 30px 40px;
        text-align: center;
        position: relative;
    }

    .logo-circle {
        width: 90px;
        height: 90px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(10px);
    }

    .logo-circle i {
        font-size: 3.5rem;
        color: white;
    }

    .login-title {
        font-size: 2rem;
        font-weight: 700;
        color: white;
        margin: 0 0 10px 0;
        letter-spacing: -0.5px;
    }

    .login-subtitle {
        font-size: 1rem;
        color: rgba(255, 255, 255, 0.9);
        margin: 0;
    }

    /* Body */
    .login-body {
        padding: 40px 35px;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-label {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
    }

    .form-label i {
        color: #c2a25d;
        margin-right: 6px;
    }

    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-icon {
        position: absolute;
        left: 18px;
        color: #c2a25d;
        font-size: 1.1rem;
        z-index: 1;
        pointer-events: none;
    }

    .form-input {
        width: 100%;
        padding: 14px 18px 14px 50px;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: #fafafa;
    }

    .form-input:focus {
        outline: none;
        border-color: #c2a25d;
        background: white;
        box-shadow: 0 0 0 4px rgba(194, 162, 93, 0.1);
    }

    .form-input.is-invalid {
        border-color: #dc3545;
        background: #fff5f5;
    }

    .form-input::placeholder {
        color: #999;
        font-size: 0.9rem;
    }

    .error-message {
        color: #dc3545;
        font-size: 0.85rem;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* Form Options */
    .form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 28px;
    }

    .remember-me {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .custom-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #c2a25d;
    }

    .remember-label {
        font-size: 0.9rem;
        color: #666;
        cursor: pointer;
        margin: 0;
        user-select: none;
    }

    .forgot-link {
        font-size: 0.9rem;
        color: #c2a25d;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .forgot-link:hover {
        color: #a88a4d;
        text-decoration: underline;
    }

    /* Button */
    .btn-login {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #c2a25d 0%, #a88a4d 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-login:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(194, 162, 93, 0.4);
    }

    .btn-login:active:not(:disabled) {
        transform: translateY(0);
    }

    .btn-login:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .spinner {
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        display: inline-block;
        animation: spin 0.6s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Footer */
    .login-footer {
        padding: 25px 35px;
        background: #f8f9fa;
        text-align: center;
        border-top: 1px solid #e0e0e0;
    }

    .login-footer p {
        margin: 0;
        font-size: 0.9rem;
        color: #666;
    }

    .register-link {
        color: #c2a25d;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .register-link:hover {
        color: #a88a4d;
        text-decoration: underline;
    }

    /* Copyright */
    .copyright {
        text-align: center;
        margin-top: 25px;
    }

    .copyright p {
        color: white;
        font-size: 0.85rem;
        margin: 0;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        opacity: 0.95;
    }

    /* Responsive */
    @media (max-width: 576px) {
        .login-card {
            border-radius: 20px;
        }

        .login-header {
            padding: 40px 25px 35px;
        }

        .logo-circle {
            width: 75px;
            height: 75px;
        }

        .logo-circle i {
            font-size: 2.8rem;
        }

        .login-title {
            font-size: 1.6rem;
        }

        .login-body {
            padding: 30px 25px;
        }

        .login-footer {
            padding: 20px 25px;
        }

        .form-options {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
    }

    /* Animation untuk error message */
    .error-message {
        animation: shake 0.4s ease-in-out;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-5px);
        }

        75% {
            transform: translateX(5px);
        }
    }
</style>
