<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Profile\AvatarService;
use App\Services\Profile\UserDataExporter;
use App\Services\Seo\MetaBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    public function show(Request $request, MetaBuilder $meta): View
    {
        $tab = (string) $request->query('sekme', 'ozet');
        $allowedTabs = ['ozet', 'hesap', 'gizlilik', 'cuzdan', 'oturumlar', 'gecmis'];

        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'ozet';
        }

        return view('profile', [
            'initialTab' => $tab,
            'seo' => $meta->privatePage('Profil', 'Hesap, gizlilik, cüzdan ve oturum yönetimi.'),
        ]);
    }

    public function export(Request $request, UserDataExporter $exporter): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $payload = $exporter->export($user);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ruhscans-verilerim-'.now()->format('Ymd').'.json"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function avatar(Request $request, User $user, AvatarService $avatars): Response
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless($request->user()?->id === $user->id, 403);
        abort_if(blank($user->avatar_path), 404);

        $disk = Storage::disk($avatars->diskName());
        abort_unless($disk->exists($user->avatar_path), 404);

        return response($disk->get($user->avatar_path), 200, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, max-age=1800',
        ]);
    }
}
