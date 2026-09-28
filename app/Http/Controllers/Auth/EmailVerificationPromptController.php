<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($this->redirectPath($request->user()));
        }
    
        return view('auth.verify-email');
    }
    
    protected function redirectPath($user): string
    {
        return $user->is_admin ? route('posts.index') : '/';
    }
}
