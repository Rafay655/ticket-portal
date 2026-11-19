<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
    
   public function updateImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ]);

        $user = $request->user();

        // delete old file if any
        if ($user->image) {
            Storage::disk('public')->delete('profile/'.$user->image);
        }

        // store new file: storage/app/public/profile/xxxx.ext
        $path = $request->file('image')->store('profile', 'public'); // e.g. profile/1739999.png
        $filename = basename($path);

        // save filename in DB
        $user->image = $filename;
        $user->save();

        // absolute URL to return
        $imageUrl = asset('storage/'.$path); // => https://your-app.com/storage/profile/xxx.png

        return response()->json([
            'message'   => 'Profile image updated',
            'image'     => $filename,
            'image_url' => $imageUrl,
        ]);
    }
    
}
