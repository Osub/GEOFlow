<?php

namespace App\Support\GeoFlow;

use App\Models\Article;
use Illuminate\Support\Str;

final class ArticleWorkflow
{
    private const FALLBACK_SLUG_PREFIX = 'article';

    private const FALLBACK_SLUG_HASH_LENGTH = 10;

    private const MAX_SLUG_LENGTH = 500;

    public static function normalizeState(string $status, string $reviewStatus, ?string $publishedAt = null): array
    {
        $allowedStatuses = ['draft', 'published', 'private'];
        $allowedReviewStatuses = ['pending', 'approved', 'rejected', 'auto_approved'];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'draft';
        }

        if (! in_array($reviewStatus, $allowedReviewStatuses, true)) {
            $reviewStatus = 'pending';
        }

        if (in_array($reviewStatus, ['pending', 'rejected'], true)) {
            $status = 'draft';
        }

        if ($status === 'published' && in_array($reviewStatus, ['pending', 'rejected'], true)) {
            $reviewStatus = 'approved';
        }

        if ($status !== 'published' && $reviewStatus === 'auto_approved') {
            $status = 'published';
        }

        if ($status === 'published' && $reviewStatus === 'pending') {
            $reviewStatus = 'approved';
        }

        if ($status === 'published') {
            $publishedAt = $publishedAt ?: date('Y-m-d H:i:s');
        } else {
            $publishedAt = null;
        }

        return [
            'status' => $status,
            'review_status' => $reviewStatus,
            'published_at' => $publishedAt,
        ];
    }

    public static function generateUniqueSlug(string $title, ?int $excludeArticleId = null): string
    {
        $baseSlug = self::buildBaseSlug($title);
        $slug = $baseSlug;
        $counter = 2;

        try {
            while (self::slugExists($slug, $excludeArticleId)) {
                $slug = self::appendSlugSuffix($baseSlug, $counter);
                $counter++;
            }

            return $slug;
        } catch (\Throwable) {
            return self::randomSlug(8);
        }
    }

    private static function buildBaseSlug(string $title): string
    {
        $normalizedTitle = Str::squish($title);
        $slug = Str::slug($normalizedTitle);

        if ($slug === '') {
            $seed = $normalizedTitle !== '' ? $normalizedTitle : self::randomSlug(12);
            $slug = self::FALLBACK_SLUG_PREFIX.'-'.substr(md5($seed), 0, self::FALLBACK_SLUG_HASH_LENGTH);
        }

        return self::limitSlugLength($slug);
    }

    private static function appendSlugSuffix(string $baseSlug, int $counter): string
    {
        $suffix = '-'.$counter;
        $trimmedBaseSlug = self::limitSlugLength($baseSlug, Str::length($suffix));

        return rtrim($trimmedBaseSlug, '-').$suffix;
    }

    private static function limitSlugLength(string $slug, int $reservedLength = 0): string
    {
        $maxLength = max(1, self::MAX_SLUG_LENGTH - $reservedLength);

        if (Str::length($slug) <= $maxLength) {
            return $slug;
        }

        return rtrim(Str::substr($slug, 0, $maxLength), '-');
    }

    private static function slugExists(string $slug, ?int $excludeArticleId = null): bool
    {
        $query = Article::withTrashed()->where('slug', $slug);

        if ($excludeArticleId !== null) {
            $query->where('id', '!=', $excludeArticleId);
        }

        return $query->exists();
    }

    private static function randomSlug(int $length): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $slug = '';
        for ($i = 0; $i < $length; $i++) {
            $slug .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $slug;
    }
}
