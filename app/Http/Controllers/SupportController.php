<?php

namespace App\Http\Controllers;

use App\Enums\SupportThreadStatus;
use App\Http\Controllers\Auth\Concerns\ResolvesAuthenticatedUser;
use App\Http\Requests\ReplySupportThreadRequest;
use App\Http\Requests\StoreSupportThreadRequest;
use App\Models\SupportThread;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SupportController extends Controller
{
    use ResolvesAuthenticatedUser;

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('support.index', [
            'threads' => $user instanceof User
                ? SupportThread::query()
                    ->where('user_id', $user->id)
                    ->with('latestMessage')
                    ->orderByDesc('last_message_at')
                    ->orderByDesc('id')
                    ->get()
                : new Collection,
        ]);
    }

    public function store(StoreSupportThreadRequest $request): RedirectResponse
    {
        $this->authorize('create', SupportThread::class);

        $user = $this->authenticatedUser($request);

        $thread = SupportThread::query()->create([
            'user_id' => $user->id,
            'subject' => $request->string('subject')->toString(),
            'status' => SupportThreadStatus::Open,
            'unread_by_admin' => false,
            'unread_by_user' => false,
        ]);

        $thread->post($user, $request->string('body')->toString());

        return redirect()
            ->route('support.show', $thread)
            ->with('sent', true);
    }

    public function show(Request $request, SupportThread $thread): View
    {
        $this->authorize('view', $thread);

        $user = $this->authenticatedUser($request);
        $thread->load(['messages.user']);
        $thread->markReadFor($user);

        return view('support.show', [
            'thread' => $thread,
        ]);
    }

    public function reply(ReplySupportThreadRequest $request, SupportThread $thread): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $thread->post($user, $request->string('body')->toString());

        return redirect()
            ->route('support.show', $thread)
            ->with('sent', true);
    }

    public function close(Request $request, SupportThread $thread): RedirectResponse
    {
        $this->authorize('close', $thread);

        $thread->close();

        return redirect()
            ->route('support.show', $thread)
            ->with('closed', true);
    }
}
