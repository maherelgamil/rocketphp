<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class NotificationController extends Controller
{
    public function __construct(private readonly PanelManager $panels) {}

    public function index(Request $request): Response
    {
        $panel = $this->resolvePanel($request);

        $paginator = $this->userNotifications($request)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('rocket/notifications', [
            'panel' => $panel->toSharedProps(),
            'notifications' => $paginator->through(fn ($n) => $this->formatNotification($n)),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $notifications = $this->userNotifications($request)
            ->whereNull('read_at')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($n) => $this->formatNotification($n));

        return response()->json(['notifications' => $notifications]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $this->userNotifications($request)
            ->where('id', $id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['unread_count' => $this->unreadCount($request)]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->userNotifications($request)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }

    /** @return Builder<DatabaseNotification> */
    private function userNotifications(Request $request): Builder
    {
        $user = $request->user();

        return DatabaseNotification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->getAuthIdentifier());
    }

    private function unreadCount(Request $request): int
    {
        return (int) $this->userNotifications($request)->whereNull('read_at')->count();
    }

    /** @return array<string, mixed> */
    private function formatNotification(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];

        return [
            'id' => $notification->id,
            'title' => (string) ($data['title'] ?? $data['message'] ?? 'Notification'),
            'body' => isset($data['body']) ? (string) $data['body'] : (isset($data['description']) ? (string) $data['description'] : null),
            'icon' => (string) ($data['icon'] ?? 'bell'),
            'url' => isset($data['url']) ? (string) $data['url'] : (isset($data['action_url']) ? (string) $data['action_url'] : null),
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
        ];
    }

    private function resolvePanel(Request $request): Panel
    {
        $panelId = $request->route()?->defaults['panelId'] ?? null;

        if ($panelId === null || ! $this->panels->has($panelId)) {
            throw new NotFoundHttpException('Rocket panel not resolved for this route.');
        }

        return $this->panels->get($panelId);
    }
}
