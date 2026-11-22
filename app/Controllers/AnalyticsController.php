<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * MUNG CMS V1
 * File: app/Controllers/AnalyticsController.php
 * Author: Firmanda Himawan
 * Description: HTTP endpoint controller that ingests product CTA analytics events for real-time monitoring.
 */

use App\Support\AnalyticsRecorder;
use Throwable;

final class AnalyticsController extends Controller
{
    public function logProductClick(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);

            return;
        }

        header('Content-Type: application/json; charset=utf-8');

        $raw = file_get_contents('php://input');
        $data = [];

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        if ($data === []) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);

            return;
        }

        try {
            $recorder = new AnalyticsRecorder($this->pdo);
            $ok = $recorder->recordProductClick($data);

            if (!$ok) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Unable to record event']);

                return;
            }
        } catch (Throwable $throwable) {
            error_log('Product click API failed: ' . $throwable->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Internal error']);

            return;
        }

        echo json_encode(['status' => 'ok']);
    }

    public function logMarketplaceClick(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);

            return;
        }

        header('Content-Type: application/json; charset=utf-8');

        $raw = file_get_contents('php://input');
        $data = [];

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        if ($data === []) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);

            return;
        }

        try {
            $recorder = new AnalyticsRecorder($this->pdo);
            $ok = $recorder->recordMarketplaceClick($data);

            if (!$ok) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Unable to record event']);

                return;
            }
        } catch (Throwable $throwable) {
            error_log('Marketplace click API failed: ' . $throwable->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Internal error']);

            return;
        }

        echo json_encode(['status' => 'ok']);
    }

    public function logLead(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);

            return;
        }

        header('Content-Type: application/json; charset=utf-8');

        $raw = file_get_contents('php://input');
        $data = [];

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        if ($data === [] && !empty($_POST)) {
            $data = $_POST;
        }

        $name = trim((string) ($data['name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $needs = trim((string) ($data['needs'] ?? ''));

        if ($name === '' || $phone === '') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Nama dan nomor WhatsApp wajib diisi']);

            return;
        }

        try {
            $recorder = new AnalyticsRecorder($this->pdo);
            $ok = $recorder->recordLead([
                'name' => $name,
                'phone' => $phone,
                'needs' => $needs,
                'source' => $data['source'] ?? 'floating_wa',
            ]);

            // Sekaligus catat interaksi klik WhatsApp
            $recorder->recordMarketplaceClick([
                'channel' => 'whatsapp',
                'product_name' => $needs !== '' ? mb_substr($needs, 0, 100) : 'Konsultasi WhatsApp',
                'target_url' => 'https://wa.me/',
                'metadata' => [
                    'lead_name' => $name,
                    'lead_phone' => $phone,
                ],
            ]);

            if (!$ok) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data']);

                return;
            }
        } catch (Throwable $throwable) {
            error_log('Lead tracking API failed: ' . $throwable->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Internal error']);

            return;
        }

        echo json_encode(['status' => 'ok', 'message' => 'Lead berhasil dicatat']);
    }
}

