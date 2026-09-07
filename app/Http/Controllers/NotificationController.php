<?php

namespace App\Http\Controllers;

use App\Enums\Billing\SubscriptionFeature;
use App\Models\Billing\Subscription;
use App\Notifications\PostScheduleOverlapNotification;
use App\Notifications\VegetableOutlookAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $feature = SubscriptionFeature::forUser($user);
        $hasAccess = $feature && Subscription::hasAccess($user, $feature);

        $notifications = $user->notifications()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (DatabaseNotification $n) => $this->presentNotification($n, $hasAccess));

        // Not scoped to a single notification type — every unread row across
        // every notification class this user can receive counts toward the
        // badge. Scoping this to one class was the original bug: schedule
        // overlap notifications would insert correctly but never surface in
        // either the list or the count.
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentNotification(DatabaseNotification $n, bool $hasAccess): array
    {
        return match ($n->type) {
            VegetableOutlookAlert::class => [
                'id' => $n->id,
                'kind' => 'outlook_alert',
                'vegetable_id' => $n->data['vegetable_id'],
                'vegetable_name' => $n->data['vegetable_name'],
                'band' => $n->data['band'],
                'message' => $hasAccess
                    ? $n->data['label']
                    : ucfirst($n->data['band']).' expected — subscribe for exact timing.',
                'detail_locked' => ! $hasAccess,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at->diffForHumans(),
            ],
            PostScheduleOverlapNotification::class => [
                'id' => $n->id,
                'kind' => 'schedule_overlap',
                'vegetable_id' => $n->data['vegetable_id'],
                'vegetable_name' => $n->data['vegetable_name'],
                'quantity_kg' => $n->data['quantity_kg'] ?? null,
                'message' => $n->data['message'],
                'url' => $n->data['url'] ?? null,
                'detail_locked' => false,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at->diffForHumans(),
            ],
            default => [
                'id' => $n->id,
                'kind' => 'unknown',
                'message' => $n->data['message'] ?? 'New notification.',
                'detail_locked' => false,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at->diffForHumans(),
            ],
        };
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->where('id', $id)->update(['read_at' => now()]);

        return response()->json(['status' => 'ok']);
    }
}
