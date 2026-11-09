<?php

namespace App\Http\Controllers;

use App\Models\LauncherDevice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LauncherDeviceController extends Controller
{
    public function destroy(Request $request, LauncherDevice $device): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $device->user_id === $user->id, 403);

        $device->delete();

        return back()->with('status', 'Cihaz kaldırıldı. Başlatıcıdan yeniden giriş yapabilirsin.');
    }
}
