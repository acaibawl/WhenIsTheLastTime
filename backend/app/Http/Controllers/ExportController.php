<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\History;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export all events and histories as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // ファイル名を生成（日本時間）
        $filename = sprintf(
            'when-is-the-last-time_%s.csv',
            Carbon::now('Asia/Tokyo')->format('Ymd_His')
        );

        Log::info('CSV export started', [
            'user_id' => $user->id,
            'filename' => $filename,
        ]);

        return response()->streamDownload(function () use ($user) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            // BOMを出力（Excel対応）
            fwrite($handle, "\xEF\xBB\xBF");

            // ヘッダー行を出力
            fputcsv($handle, [
                'Event name',
                'Note',
                'Created at',
            ]);

            // ユーザーのすべてのイベントと、それに紐づく履歴をソートして取得
            $events = $user->events()
                ->with(['histories' => function ($query) {
                    $query->orderBy('executed_at', 'asc');
                }])
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($events as $event) {
                // イベントに履歴がある場合、履歴ごとに行を出力
                $histories = $event->histories;

                if ($histories->isEmpty()) {
                    // 履歴がないイベントは空のメモで出力
                    fputcsv($handle, [
                        $event->name,
                        'イベント作成済み',
                        $this->formatDateTime($event->created_at),
                    ]);
                } else {
                    // 履歴ごとに行を出力
                    foreach ($histories as $history) {
                        /** @var History $history */
                        fputcsv($handle, [
                            $event->name,
                            $history->memo ?? '',
                            $this->formatDateTime($history->executed_at),
                        ]);
                    }
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Format datetime for CSV output.
     */
    private function formatDateTime(?\Illuminate\Support\Carbon $dateTime): string
    {
        if ($dateTime === null) {
            return '';
        }

        // タイムゾーン情報付きで出力（UTC +0000 形式）
        return $dateTime->format('Y-m-d H:i:s O');
    }
}
