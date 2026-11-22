<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use PDOException;

abstract class Controller
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected function render(string $template, array $data = [], array $layout = []): void
    {
        $data['siteSettings'] = $data['siteSettings'] ?? $this->siteSettings();
        render($template, $data, $layout);
    }

    protected function siteSettings(): array
    {
        static $cached;

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $statement = $this->pdo->query('SELECT * FROM site_settings ORDER BY id ASC LIMIT 1');
            $settings = $statement ? $statement->fetch(PDO::FETCH_ASSOC) : false;
            $cached = $settings ?: [];
        } catch (PDOException $exception) {
            error_log('Site settings fetch failed: ' . $exception->getMessage());
            $cached = [];
        }

        return $cached;
    }

    protected function fetchSeoMeta(string $ownerType, int $ownerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT meta_title, meta_description, meta_keywords, og_image_url
             FROM seo_meta
             WHERE owner_type = :owner_type AND owner_id = :owner_id
             LIMIT 1'
        );

        $statement->execute([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
        ]);

        return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
